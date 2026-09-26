<?php
// email_service.php - Sends real OTP emails via PHPMailer + SMTP
require_once 'email_config.php';
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends an email via SMTP.
 *
 * @param string $to      Recipient email address
 * @param string $subject Email subject
 * @param string $body    Plain-text email body
 * @return array ['success' => bool, 'error' => string|null]
 */
function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);

        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return ['success' => true, 'error' => null];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

/**
 * Sends a 6-digit OTP to the given email address.
 *
 * @param string $to  Recipient email address
 * @param string $otp The 6-digit code
 * @return array ['success' => bool, 'error' => string|null]
 */
function sendOtpEmail($to, $otp) {
    $subject = "Your Kung Tung Liquor Shop verification code";
    $body = "Your verification code is: $otp\n\n"
          . "This code expires in 5 minutes.\n"
          . "If you did not request this, you can safely ignore this email.";
    return sendEmail($to, $subject, $body);
}

/**
 * Order confirmation email (sent right after checkout).
 */
function sendOrderConfirmationEmail($to, $orderNumber, $fullName, $items, $subtotal, $shipping, $total) {
    $subject = "Order Confirmed - $orderNumber";

    $lines = "";
    foreach ($items as $item) {
        $volume = !empty($item['volume']) ? " ({$item['volume']})" : "";
        $lines .= "- {$item['name']}{$volume} x{$item['quantity']} @ Rs. " . number_format($item['price'], 2) . "\n";
    }

    $body = "Hi $fullName,\n\n"
          . "Thanks for your order! Here's your summary:\n\n"
          . "Order No: $orderNumber\n\n"
          . $lines . "\n"
          . "Subtotal: Rs. " . number_format($subtotal, 2) . "\n"
          . "Shipping: Rs. " . number_format($shipping, 2) . "\n"
          . "Total: Rs. " . number_format($total, 2) . "\n\n"
          . "Payment Method: Cash on Delivery\n"
          . "We'll email you again as your order status changes.\n\n"
          . "- Kung Tung Liquor Shop";

    return sendEmail($to, $subject, $body);
}

/**
 * Order status update email (sent whenever admin changes status).
 */
function sendOrderStatusEmail($to, $orderNumber, $fullName, $status, $items = []) {
    $subject = "Order $orderNumber - Status: $status";

    $lines = "";
    foreach ($items as $item) {
        $volume = !empty($item['volume']) ? " ({$item['volume']})" : "";
        $lines .= "- {$item['product_name']}{$volume} x{$item['quantity']}\n";
    }

    $body = "Hi $fullName,\n\n"
          . "Your order $orderNumber status has been updated to: $status\n\n"
          . ($lines !== "" ? "Order Items:\n$lines\n" : "")
          . "- Kung Tung Liquor Shop";

    return sendEmail($to, $subject, $body);
}
?>
