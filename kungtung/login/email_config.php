<?php
// email_config.php - SMTP credentials for sending OTP emails
//
// Using Gmail SMTP as the example (free, works well for testing/small volume).
//
// HOW TO GET A GMAIL APP PASSWORD:
// 1. Turn on 2-Step Verification on your Google account: https://myaccount.google.com/security
// 2. Go to https://myaccount.google.com/apppasswords
// 3. Create an app password (choose "Mail" as the app) - Google gives you a
//    16-character password. Use THAT here, not your normal Gmail password.
//
// You can swap these for any SMTP provider (Outlook, Zoho, SendGrid, Mailgun, etc.)
// by changing the host/port/username/password below.

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'trbverse@gmail.com');       // your Gmail address
define('SMTP_PASSWORD', 'ggkm ogip bjbl xtca');    // Gmail App Password (not your login password)
define('SMTP_FROM_EMAIL', 'trbverse@gmail.com');
define('SMTP_FROM_NAME', 'Kung Tung Liquor Shop');

?>
