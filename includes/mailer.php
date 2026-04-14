<?php
/**
 * CampusCare — Mail Helper
 * 
 * Centralised mail-sending functions using PHPMailer.
 * Requires config/mail.php to be loaded first.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an email via SMTP.
 *
 * @param  string $to        Recipient email address
 * @param  string $subject   Email subject
 * @param  string $htmlBody  HTML body content
 * @return array             ['success' => bool, 'error' => string|null]
 */
function sendMail($to, $subject, $htmlBody)
{
    $mail = new PHPMailer(true);

    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port = MAIL_PORT;

        // Sender & recipient
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($to);

        // Content
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $mail->send();
        return ['success' => true, 'error' => null];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

/**
 * Generate a cryptographically-secure numeric OTP.
 *
 * @param  int $length  Number of digits (default from config)
 * @return string       Zero-padded OTP string
 */
function generateOTP($length = null)
{
    $length = $length ?? OTP_LENGTH;
    $min = (int) str_pad('1', $length, '0');   // e.g. 100000
    $max = (int) str_pad('', $length, '9');     // e.g. 999999
    return str_pad((string) random_int($min, $max), $length, '0', STR_PAD_LEFT);
}

/**
 * Send a password-reset OTP email.
 *
 * @param  string $toEmail   Recipient email
 * @param  string $otpCode   The plain OTP code
 * @param  string $userName  Display name of the user (for greeting)
 * @return array             ['success' => bool, 'error' => string|null]
 */
function sendOTP($toEmail, $otpCode, $userName)
{
    $expiryMinutes = OTP_EXPIRY_MINUTES;
    $appName = APP_NAME ?? 'CampusCare';

    // Logo hosted on public GitHub — loads correctly in all real email clients
    $logoUrl = 'https://raw.githubusercontent.com/Keith11-1313/CampusCare/main/assets/logo-main-w.png';

    // Split OTP into individual digit boxes for GCash-style display
    $otpDigits = str_split(htmlspecialchars($otpCode));
    $otpBoxes = '';
    foreach ($otpDigits as $digit) {
        $otpBoxes .= '<td style="padding:0 4px;">
            <div style="
                width:44px; height:54px;
                line-height:54px; text-align:center;
                font-size:28px; font-weight:800; font-family:monospace;
                color:#003d6b;
                background:#f0f7ff;
                border:2px solid #005a9c;
                border-radius:10px;
                display:inline-block;
            ">' . $digit . '</div>
        </td>';
    }

    $html = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Password Reset OTP</title>
    </head>
    <body style="margin:0;padding:0;background-color:#eef2f7;font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;">

        <table width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f7;padding:48px 0;">
            <tr><td align="center">

                <!-- Card -->
                <table width="520" cellpadding="0" cellspacing="0"
                    style="background:#ffffff;border-radius:16px;overflow:hidden;
                           box-shadow:0 4px 24px rgba(0,61,107,0.10);max-width:520px;">

                    <!-- ══ TOP BRAND BAR ══ -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#003d6b 0%,#005a9c 60%,#2d89cf 100%);
                                   padding:0;text-align:left;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <!-- Logo / Name -->
                                    <td style="padding:22px 32px;">
                                        <table cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding-right:12px;vertical-align:middle;">
                                                    <!-- CampusCare logo -->
                                                    <img src="' . $logoUrl . '"
                                                         alt="CampusCare"
                                                         width="36" height="36"
                                                         style="display:block;width:36px;height:36px;object-fit:contain;">
                                                </td>
                                                <td style="vertical-align:middle;">
                                                    <span style="color:#ffffff;font-size:20px;font-weight:800;
                                                                 letter-spacing:0.4px;line-height:1;">Campus</span><span
                                                          style="color:rgba(255,255,255,0.75);font-size:20px;
                                                                 font-weight:400;">Care</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <!-- Decorative right shape -->
                                    <td style="padding:0;text-align:right;vertical-align:bottom;width:100px;">
                                        <div style="width:80px;height:80px;background:rgba(255,255,255,0.08);
                                                    border-radius:50%;margin-left:auto;transform:translate(20px,-10px);"></div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- ══ HEADING BAND ══ -->
                    <tr>
                        <td style="background:#003d6b;padding:12px 32px 20px;text-align:left;">
                            <p style="margin:0;color:#ffffff;font-size:17px;font-weight:700;letter-spacing:0.3px;">
                                Password Reset Verification Code
                            </p>
                        </td>
                    </tr>

                    <!-- ══ DIVIDER LINE ══ -->
                    <tr>
                        <td style="height:3px;background:linear-gradient(90deg,#005a9c,#2d89cf,#78c1f3);"></td>
                    </tr>

                    <!-- ══ BODY ══ -->
                    <tr>
                        <td style="padding:32px 32px 24px;">

                            <!-- Greeting -->
                            <p style="margin:0 0 16px;color:#1a2e44;font-size:15px;line-height:1.6;">
                                Hello <strong style="color:#003d6b;">' . htmlspecialchars($userName) . '</strong>,
                            </p>
                            <p style="margin:0 0 28px;color:#4a5568;font-size:14px;line-height:1.7;">
                                This 6-digit code is to reset your ' . htmlspecialchars($appName) . ' account password.
                                Please copy and enter it in the app.
                            </p>

                            <!-- ══ OTP DIGIT BOXES ══ -->
                            <table cellpadding="0" cellspacing="0" style="margin:0 auto 28px;">
                                <tr>' . $otpBoxes . '</tr>
                            </table>

                            <!-- Expiry -->
                            <p style="margin:0 0 24px;color:#4a5568;font-size:13px;line-height:1.6;text-align:center;">
                                This code expires in&nbsp;<strong style="color:#003d6b;">' . $expiryMinutes . ' minutes</strong>.
                            </p>

                            <!-- Divider -->
                            <hr style="border:none;border-top:1px solid #e2e8f0;margin:0 0 20px;">

                            <!-- Disclaimer -->
                            <p style="margin:0;color:#718096;font-size:12px;line-height:1.6;">
                                If you did not request to reset your password,
                                <strong>please ignore this email.</strong>
                                Your account remains secure.
                            </p>

                        </td>
                    </tr>

                    <!-- ══ FOOTER ══ -->
                    <tr>
                        <td style="background:#f7fafc;padding:20px 32px;border-top:1px solid #e2e8f0;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <!-- Brand repeat -->
                                    <td style="vertical-align:middle;">
                                        <table cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding-right:8px;vertical-align:middle;">
                                                    <!-- CampusCare logo (footer) -->
                                                    <img src="' . $logoUrl . '"
                                                         alt="CampusCare"
                                                         width="26" height="26"
                                                         style="display:block;width:26px;height:26px;object-fit:contain;
                                                                background:#003d6b;border-radius:6px;">
                                                </td>
                                                <td style="vertical-align:middle;">
                                                    <span style="color:#003d6b;font-size:13px;font-weight:700;">Campus</span><span
                                                          style="color:#718096;font-size:13px;">Care</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <!-- Copyright -->
                                    <td style="text-align:right;vertical-align:middle;">
                                        <p style="margin:0;color:#a0aec0;font-size:11px;">
                                            &copy; ' . date('Y') . ' ' . htmlspecialchars($appName) . '. All rights reserved.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <!-- Legal note -->
                            <p style="margin:12px 0 0;color:#a0aec0;font-size:10px;line-height:1.5;text-align:center;">
                                This email contains confidential information for the named recipient only.
                                If you are not the intended recipient, please notify us immediately and delete this email.
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- /Card -->

            </td></tr>
        </table>

    </body>
    </html>';

    return sendMail($toEmail, "$appName - Password Reset Code", $html);
}

/**
 * Mask an email address for safe display.
 * e.g. "jerald@gmail.com" → "j***d@gmail.com"
 *
 * @param  string $email
 * @return string
 */
function maskEmail($email)
{
    if (empty($email) || strpos($email, '@') === false) {
        return '***@***.***';
    }

    list($local, $domain) = explode('@', $email);
    $len = strlen($local);

    if ($len <= 2) {
        $masked = $local[0] . str_repeat('*', max(1, $len - 1));
    } else {
        $masked = $local[0] . str_repeat('*', $len - 2) . $local[$len - 1];
    }

    return $masked . '@' . $domain;
}
