<?php
// password.php - Step 4: Set password
session_start();

if (empty($_SESSION['email_verified']) || empty($_SESSION['details_done'])) {
    header("Location: index.php");
    exit;
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Password - Kung Tung Liquor Shop</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .password-rules {
            margin: 8px 0 18px;
            padding: 12px;
            background: #f7f7f7;
            border-radius: 8px;
            font-size: 14px;
        }

        .password-rules div {
            margin: 5px 0;
            color: #777;
        }

        .password-rules .valid {
            color: green;
        }

        .password-rules .invalid {
            color: #d33;
        }

        .password-rules span {
            display: inline-block;
            width: 18px;
        }

        .match-message {
            font-size: 13px;
            margin-top: -12px;
            margin-bottom: 15px;
        }

        .match-message.valid {
            color: green;
        }

        .match-message.invalid {
            color: #d33;
        }
    </style>
</head>

<body>

<div class="card">

    <h1>Create a Password</h1>
    <p class="sub">Last step — secure your account</p>

    <div class="steps">
        <span class="done">1</span>
        <span class="done">2</span>
        <span class="done">3</span>
        <span class="active">4</span>
    </div>

    <?php if ($error): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="process_password.php" method="POST" id="passwordForm">

        <label for="password">Password</label>

        <input
            type="password"
            id="password"
            name="password"
            minlength="8"
            required
            autofocus
            autocomplete="new-password"
        >

        <!-- Password requirements -->
        <div class="password-rules">

            <div id="length">
                <span>✗</span> At least 8 characters
            </div>

            <div id="uppercase">
                <span>✗</span> At least 1 uppercase letter
            </div>

            <div id="lowercase">
                <span>✗</span> At least 1 lowercase letter
            </div>

            <div id="number">
                <span>✗</span> At least 1 number
            </div>

        </div>

        <label for="confirm_password">Confirm Password</label>

        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            minlength="8"
            required
            autocomplete="new-password"
        >

        <div id="matchMessage" class="match-message"></div>

        <button type="submit">Create Account</button>

    </form>

    <p class="notice">
        Use at least 8 characters with uppercase, lowercase letters and numbers.
    </p>

</div>


<script>

const password = document.getElementById("password");
const confirmPassword = document.getElementById("confirm_password");

const lengthRule = document.getElementById("length");
const uppercaseRule = document.getElementById("uppercase");
const lowercaseRule = document.getElementById("lowercase");
const numberRule = document.getElementById("number");

const matchMessage = document.getElementById("matchMessage");


// Update password requirements
password.addEventListener("input", function () {

    const value = password.value;

    // Minimum 8 characters
    updateRule(
        lengthRule,
        value.length >= 8
    );

    // Uppercase
    updateRule(
        uppercaseRule,
        /[A-Z]/.test(value)
    );

    // Lowercase
    updateRule(
        lowercaseRule,
        /[a-z]/.test(value)
    );

    // Number
    updateRule(
        numberRule,
        /[0-9]/.test(value)
    );

    checkPasswordMatch();
});


// Check confirm password
confirmPassword.addEventListener("input", checkPasswordMatch);


function updateRule(element, valid) {

    const icon = element.querySelector("span");

    if (valid) {

        element.classList.add("valid");
        element.classList.remove("invalid");

        icon.textContent = "✓";

    } else {

        element.classList.add("invalid");
        element.classList.remove("valid");

        icon.textContent = "✗";
    }
}


// Check passwords match
function checkPasswordMatch() {

    const pass = password.value;
    const confirm = confirmPassword.value;

    if (confirm === "") {

        matchMessage.textContent = "";
        matchMessage.className = "match-message";

        return;
    }

    if (pass === confirm) {

        matchMessage.textContent = "✓ Passwords match";
        matchMessage.className = "match-message valid";

    } else {

        matchMessage.textContent = "✗ Passwords do not match";
        matchMessage.className = "match-message invalid";
    }
}


// Final validation before submitting
document.getElementById("passwordForm").addEventListener("submit", function (e) {

    const value = password.value;
    const confirm = confirmPassword.value;

    const validLength = value.length >= 8;
    const validUppercase = /[A-Z]/.test(value);
    const validLowercase = /[a-z]/.test(value);
    const validNumber = /[0-9]/.test(value);
    const passwordsMatch = value === confirm;

    if (
        !validLength ||
        !validUppercase ||
        !validLowercase ||
        !validNumber ||
        !passwordsMatch
    ) {

        e.preventDefault();

        alert(
            "Please create a valid password and make sure both passwords match."
        );

        return;
    }

});

</script>

</body>
</html>