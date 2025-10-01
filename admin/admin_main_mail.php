<?php
require_once __DIR__ . '/admin_mailer.php';

try {
  $config = include(__DIR__ . '/config/mailer.config.php'); 
  
  $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
  $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
  $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
  $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING); 
  
  $attachment = isset($_FILES['attachment']) ? $_FILES['attachment'] : null; 

  $mailer = new Mailer($config);

  $mailer->setSender("sintadriveph@gmail.com", "SintaDrive"); 
  $mailer->addRecipient($email, $name);
  $mailer->addAttachment($attachment);
  $mailer->setSubject($subject);
  $mailer->setBody($message);
  
  $mailer->send();
  
  header("Location: admin_mailsent.php");
  exit; 

} catch(Exception $e) {
  echo "Message not Sent. PHPMAILER ERROR: " . $e->getMessage();
}

?>