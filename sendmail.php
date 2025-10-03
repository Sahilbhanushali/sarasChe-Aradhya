<?php
// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
error_reporting(E_ALL);

// Return JSON
header('Content-Type: application/json');

// Include Composer autoload
require __DIR__ . '/vendor/autoload.php'; 

$config = require __DIR__ . '/config.php';

// Only accept POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

// Honeypot check
if (!empty($_POST['website'] ?? '')) {
    echo json_encode(["success" => false, "message" => "Spam detected."]);
    exit;
}

// Collect & sanitize inputs
$name = htmlspecialchars(trim($_POST["name"] ?? ''));
$email = htmlspecialchars(trim($_POST["email"] ?? ''));
$tel = htmlspecialchars(trim($_POST["tel"] ?? ''));
$budget = htmlspecialchars(trim($_POST["budget"] ?? ''));
$message = htmlspecialchars(trim($_POST["message"] ?? ''));

// Validation
$errors = [];
if (!$name) $errors[] = "Name is required.";
if (!$email) $errors[] = "Email is required.";
if (!$tel) $errors[] = "Phone is required.";
if (!$budget) $errors[] = "Budget is required.";
if (!$message) $errors[] = "Message is required.";

if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email address.";
if ($tel && !preg_match('/^\d{10}$/', $tel)) $errors[] = "Phone must be 10 digits.";
if ($budget && !preg_match('/^\d+$/', $budget)) $errors[] = "Budget must be numbers only.";
if ($message && strlen($message) < 10) $errors[] = "Message must be at least 10 characters.";

if (!empty($errors)) {
    echo json_encode(["success" => false, "message" => implode(" ", $errors)]);
    exit;
}

// Use PHPMailer
$mail = new PHPMailer\PHPMailer\PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $config['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp_user'];
    $mail->Password   = $config['smtp_pass'];
    $mail->SMTPSecure = $config['smtp_secure'] ?? 'tls';
    $mail->Port       = $config['smtp_port'];

    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addReplyTo($email, $name);
    $mail->addAddress($config['from_email']); // recipient

    $mail->isHTML(true);
    $mail->Subject = "New Contact Form Submission - $name";
    $mail->Body    = "
        <strong>Name:</strong> $name <br>
        <strong>Email:</strong> $email <br>
        <strong>Phone:</strong> $tel <br>
        <strong>Budget:</strong> $budget <br>
        <strong>Message:</strong> $message
    ";

    $mail->send();
    echo json_encode(["success" => true, "message" => "Message sent successfully!"]);

} catch (PHPMailer\PHPMailer\Exception $e) {
    error_log("PHPMailer Error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Failed to send message. Check server logs."
    ]);
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "An unexpected error occurred. Check server logs."
    ]);
}
