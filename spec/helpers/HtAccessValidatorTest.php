<?php
DEFINE("APP_DIR", str_replace("/spec", "", dirname(__DIR__)));

require_once APP_DIR . "/helpers/HtAccessValidator.php";

class HtAccessValidatorTests extends \PHPUnit\Framework\TestCase {
  private $htAccessValidator;
  
  protected function setUp()
  {
    $this->htAccessValidator = new HtAccessValidator('/spec/fixtures/.htaccess');
  }
 
  public function testHtAccessNumberErrors() {
     $errors =  $this->htAccessValidator->GetErrors();
     $this->assertEquals(290, count($errors));
  }
}
?>