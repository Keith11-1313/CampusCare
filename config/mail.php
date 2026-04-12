<?php
/**
 * CampusCare — Mail (SMTP) Configuration
 * 
 * Fill in your SMTP credentials below.
 * For Gmail: enable 2-Step Verification, then generate an App Password at
 *   https://myaccount.google.com/apppasswords
 */

// SMTP server settings
define('MAIL_HOST', 'smtp.gmail.com');          // SMTP host
define('MAIL_PORT', 587);                        // SMTP port (587 = TLS)
define('MAIL_ENCRYPTION', 'tls');                // 'tls' or 'ssl'

// SMTP authentication
define('MAIL_USERNAME', 'noreply.campuscare.bsis2a@gmail.com'); // ← Replace with your Gmail address
define('MAIL_PASSWORD', 'rwvs irda hyfc asdj');     // ← Replace with your Gmail App Password

// Sender identity
define('MAIL_FROM_ADDRESS', 'noreply.campuscare.bsis2a@gmail.com'); // ← Same as MAIL_USERNAME
define('MAIL_FROM_NAME', 'CampusCare');

// OTP settings
define('OTP_LENGTH', 6);            // Number of digits
define('OTP_EXPIRY_MINUTES', 10);   // Minutes until OTP expires
define('OTP_MAX_ATTEMPTS', 5);      // Max verification attempts before lockout
define('OTP_RESEND_COOLDOWN', 30);  // Seconds before user can resend OTP
