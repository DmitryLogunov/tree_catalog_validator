<?php
class DBClient {

  private $dbConnection;

  /**
   * Database client constructor: check DB settings, connects DB server and selects DB
   *
   * @param [type] $dbSettings
   * @return void
   */
  function DBClient($dbSettings) {
    if(!isset($dbSettings['DB_HOST']) || !isset($dbSettings['DB_USER']) || 
       !isset($dbSettings['DB_PASSWORD']) || !isset($dbSettings['DB_NAME'])) {
          throw new Exception('There are no defined all required DB settings parameters');
    }

    $this->dbConnection = mysqli_connect($dbSettings['DB_HOST'], $dbSettings['DB_USER'], $dbSettings['DB_PASSWORD']);
    
    if(!$this->dbConnection) throw new Exception('Couldn\'t connect to MySQL server');

    if(!@mysqli_select_db($this->dbConnection, $dbSettings['DB_NAME'])) {
      throw new Exception('Couldn\'t select MySQL database');
    }
    
    mysqli_query($this->dbConnection, "SET NAMES 'utf8'");
  }

  /**
   * Sends query and returns selection
   *
   * @param [string] $query
   * @return array
   */
  function Select($query) {
    $selection = array();
    
    $q = mysqli_query( $this->dbConnection, $query);
    while(!empty($row = mysqli_fetch_array($q))) $selection[] = $row;

    return $selection; 
  }
}

?>