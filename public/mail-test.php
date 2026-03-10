<?php
/**
 * Mail Diagnostic Script — DELETE AFTER TESTING!
 *
 * Usage: https://your-domain.cz/mail-test.php?to=your@email.cz
 */

header('Content-Type: text/plain; charset=UTF-8');

echo "=== BikeSwap Mail Diagnostic ===\n\n";

echo "PHP Version: " . PHP_VERSION . "\n";
echo "Hostname: " . gethostname() . "\n";
echo "sendmail_path: " . ini_get('sendmail_path') . "\n";
echo "SMTP: " . ini_get('SMTP') . "\n";
echo "smtp_port: " . ini_get('smtp_port') . "\n\n";

// Check if sendmail/postfix binary exists
$sendmailPaths = ['/usr/sbin/sendmail', '/usr/lib/sendmail', '/usr/bin/msmtp'];
foreach ($sendmailPaths as $path) {
    echo "Exists $path: " . (file_exists($path) ? 'YES' : 'no') . "\n";
}
echo "\n";

$to = $_GET['to'] ?? '';
if ($to === '') {
    echo "To send a test email, add ?to=your@email.cz to the URL.\n";
    exit;
}

if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid email address: $to\n";
    exit;
}

$subject = 'BikeSwap - Test mail';
$body = "This is a test email from BikeSwap.\n\nTimestamp: " . date('Y-m-d H:i:s') . "\nHostname: " . gethostname();
$headers = "From: BikeSwap <noreply@" . gethostname() . ">\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n"
         . "X-Mailer: BikeSwap-MailTest/1.0";

echo "Sending to: $to\n";
echo "From: noreply@" . gethostname() . "\n\n";

$result = mail($to, $subject, $body, $headers);

echo "mail() returned: " . ($result ? 'TRUE (accepted for delivery)' : 'FALSE (rejected)') . "\n\n";

if ($result) {
    echo "Mail was accepted by the local MTA.\n";
    echo "Check your inbox (and spam folder) within a few minutes.\n";
    echo "If it doesn't arrive, the server may lack proper DNS/SPF records.\n";
} else {
    echo "Mail was rejected. Postfix may not be running or PHP lacks permission.\n";
    echo "Try asking your server admin about mail configuration.\n";
}

echo "\n=== DELETE THIS FILE AFTER TESTING ===\n";
