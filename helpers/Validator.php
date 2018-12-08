<?php
class Validator {

  private $validateErrors;
  
  /**
   * Constructor HtAccessValidator
   *
   * @param [string] $pathToHtaccess
   * @return void
   */
  function Validator() {    
    $this->validateErrors = array();    
  }

  /**
   *
   * @return void
   */
  function GetErrors($type = 'all') { 
    if($type == 'all') return  $this->validateErrors;
    
    $filteredErrors = array();
    foreach($this->validateErrors as $error) {
      if($error['code'] == $type) {
        array_push($filteredErrors, $error);
      }
    }
    
    return $filteredErrors;
  }  

  /**
   * Adds new error to errors set ($this->validateErrors)
   *
   * @param [string] $errorDescription
   * @param [string] $code
   * @param [string] $info
   * @return void
   */
  function AddError($errorDescription, $code, $info) {
    array_push($this->validateErrors, 
               array('Error' => $errorDescription, 'code' => $code, 'info' => $info));
  }
}

?>