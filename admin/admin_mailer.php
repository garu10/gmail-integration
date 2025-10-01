<?php
require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Mailer{
  private $mail;
  public function __construct($config){
    $this->mail = new PHPMailer(true);
    // $this->mail->SMTPDebug = SMTP::DEBUG_SERVER; 
    $this->mail->isSMTP();
    $this->mail->SMTPAuth = true;
    $this->mail->Host = $config['host'];
    $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $this->mail->Port = $config['port'];
    $this->mail->Username = $config['username'];
    $this->mail->Password = $config['password'];
  }

  public function setSender($email, $name){
    $this->mail->setFrom($email, $name);
  }

  public function addRecipient($email, $name){
    $this->mail->addAddress($email, $name);
  }

  public function addAttachment($attachment){
    if ($attachment && isset($attachment['error']) && $attachment['error'] == UPLOAD_ERR_OK){
      $maxSize = 3 * 1024 * 1024; // maximum of 3MB
      if ($attachment['size'] > $maxSize) {
        throw new Exception('Attachment size exceeds limit');
      }
      $this->mail->addAttachment($attachment['tmp_name'], $attachment['name']);
    } elseif ($attachment && isset($attachment['error']) && $attachment['error'] != UPLOAD_ERR_NO_FILE) {
      throw new Exception('Error uploading attachment');
    }
  }

  public function setSubject($subject){
    $this->mail->Subject = $subject;
  }

  public function setBody($body){
    $this->mail->Body = $body;
  }

  public function send(){
    $this->mail->send();
  }
}