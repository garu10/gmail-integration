<?php
require_once "vendor/autoload.php";

class EmailSanitizer {
  private $purifier;
  private $textPurifier;

  public function __construct(){
    $this->initializePurifiers();
  }

  private function initializePurifiers() {
    // Standard HTML + CSS purifier
    $config = HTMLPurifier_Config::createDefault();
    $config->set('HTML.Allowed', 'p,br,div,span,strong,b,em,i,u,a[href],img[src|alt],table,tr,td');
    $config->set('CSS.AllowedProperties', ['color', 'font-size', 'font-weight', 'text-align']);
    $config->set('HTML.Nofollow', true);
    $config->set('HTML.TargetBlank', true);
    $this->purifier = new HTMLPurifier($config);

    // Text-only purifier
    $textConfig = HTMLPurifier_Config::createDefault();
    $textConfig->set('HTML.Allowed', '');
    $this->textPurifier = new HTMLPurifier($textConfig);
  }

  public function sanitizeHTML($html) {
    return $this->purifier->purify($html);
  }

  public function sanitizeText($html) {
    return $this->textPurifier->purify($html);
  }
}

