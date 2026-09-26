<?php

session_start();
include("../config/database.php");
require_once("../login/email_service.php");

header("Content-Type: application/json");

/* ============================================================
   REQUEST VALIDATION
============================================================ */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method. POST required."
    ]);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = trim($_POST['status'] ?? '');

$allowedStatuses = [
    "Pending",
    "Processing",
    "Out for Delivery",
    "Completed",
    "Cancelled"
];

if ($id <= 0 || !in_array($status, $allowedStatuses, true)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid order ID or status."
    ]);
    exit;
}

/* ============================================================
   GET ORDER
============================================================ */

$orderStmt = mysqli_prepare($conn, "
    SELECT id, status, order_number, full_name, email
    FROM orders
    WHERE id = ?
    LIMIT 1
");

if (!$orderStmt) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($orderStmt, "i", $id);
mysqli_stmt_execute($orderStmt);
$orderResult = mysqli_stmt_get_result($orderStmt);

if (mysqli_num_rows($orderResult) === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Order not found."
    ]);
    exit;
}

$order = mysqli_fetch_assoc($orderResult);
$oldStatus = $order['status'];

/* ============================================================
   SAME STATUS CHECK
============================================================ */

if ($oldStatus === $status) {
    echo json_encode([
        "success" => false,
        "message" => "The order is already marked as {$status}."
    ]);
    exit;
}

/* ============================================================
   PREVENT INVALID FINAL-STATUS CHANGES
============================================================ */

if ($oldStatus === "Cancelled" || $oldStatus === "Completed") {
    echo json_encode([
        "success" => false,
        "message" => "A {$oldStatus} order cannot be moved to another status."
    ]);
    exit;
}

/* ============================================================
   TRANSACTION
============================================================ */

mysqli_begin_transaction($conn);

try {
    /* ========================================================
       UPDATE STATUS
    ======================================================== */
    
    $updateStmt = mysqli_prepare($conn, "
        UPDATE orders
        SET status = ?, updated_at = NOW()
        WHERE id = ?
    ");

    if (!$updateStmt) {
        throw new Exception("Failed to prepare update statement.");
    }

    mysqli_stmt_bind_param($updateStmt, "si", $status, $id);
    
    if (!mysqli_stmt_execute($updateStmt)) {
        throw new Exception("Failed to update order status: " . mysqli_error($conn));
    }

    /* ========================================================
       RESTORE STOCK WHEN CANCELLED
    ======================================================== */

    if ($status === "Cancelled" && $oldStatus !== "Cancelled") {
        
        $itemsStmt = mysqli_prepare($conn, "
            SELECT variant_id, quantity
            FROM order_items
            WHERE order_id = ?
        ");

        if (!$itemsStmt) {
            throw new Exception("Failed to prepare items statement.");
        }

        mysqli_stmt_bind_param($itemsStmt, "i", $id);
        mysqli_stmt_execute($itemsStmt);
        $itemsResult = mysqli_stmt_get_result($itemsStmt);

        $stockStmt = mysqli_prepare($conn, "
            UPDATE product_variants
            SET stock = stock + ?
            WHERE id = ?
        ");

        if (!$stockStmt) {
            throw new Exception("Failed to prepare stock update statement.");
        }

        while ($item = mysqli_fetch_assoc($itemsResult)) {
            $variantId = (int)$item['variant_id'];
            $quantity = (int)$item['quantity'];

            // Only update stock if variant_id exists
            if ($variantId > 0 && $quantity > 0) {
                mysqli_stmt_bind_param($stockStmt, "ii", $quantity, $variantId);
                
                if (!mysqli_stmt_execute($stockStmt)) {
                    throw new Exception("Failed to restore stock for variant ID: {$variantId}");
                }
            }
        }
    }

    /* ========================================================
       COMMIT
    ======================================================== */

    mysqli_commit($conn);

    /* ========================================================
       SEND EMAIL (Async - don't fail if email fails)
    ======================================================== */

    if (!empty($order['email'])) {
        try {
            // Get order items for email
            $itemStmt = mysqli_prepare($conn, "
                SELECT product_name, volume, quantity
                FROM order_items
                WHERE order_id = ?
            ");

            if ($itemStmt) {
                mysqli_stmt_bind_param($itemStmt, "i", $id);
                mysqli_stmt_execute($itemStmt);
                $itemsResult = mysqli_stmt_get_result($itemStmt);
                
                $orderItems = [];
                while ($item = mysqli_fetch_assoc($itemsResult)) {
                    $orderItems[] = $item;
                }

                // Send email (don't throw if fails)
                try {
                    sendOrderStatusEmail(
                        $order['email'],
                        $order['order_number'],
                        $order['full_name'],
                        $status,
                        $orderItems
                    );
                } catch (Throwable $mailError) {
                    // Log but don't fail the status update
                    error_log("Order status email failed: " . $mailError->getMessage());
                }
            }
        } catch (Throwable $e) {
            // Log but don't fail the status update
            error_log("Order items fetch for email failed: " . $e->getMessage());
        }
    }

    /* ========================================================
       SUCCESS RESPONSE
    ======================================================== */

    echo json_encode([
        "success" => true,
        "message" => "Order status changed to {$status}.",
        "status" => $status,
        "old_status" => $oldStatus
    ]);

} catch (Throwable $e) {
    /* ========================================================
       ROLLBACK ON ERROR
    ======================================================== */
    
    mysqli_rollback($conn);
    
    error_log("Order status update failed: " . $e->getMessage());
    
    echo json_encode([
        "success" => false,
        "message" => "Unable to update order status. " . $e->getMessage()
    ]);
}