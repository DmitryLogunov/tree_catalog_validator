<?php
require_once "./config/config.php";
require_once "./helpers/HtAccessValidator.php";
require_once "./helpers/CatalogValidator.php";
require_once "./models/Catalog.php";

$dbSettings = array('DB_HOST' => DB_HOST, 'DB_USER' => DB_USER, 'DB_PASSWORD' => DB_PASSWORD, 'DB_NAME' => DB_NAME);
$dbSctructure = array('CATALOG_SECTIONS_TABLE_NAME' => CATALOG_SECTIONS_TABLE_NAME, 
                      'CATALOG_ITEMS_TABLE_NAME' => CATALOG_ITEMS_TABLE_NAME,
                      'ROOT_SECTION_ID' =>  ROOT_SECTION_ID);

$htAccessValidator = new HtAccessValidator($dbSettings, $dbSctructure);
$catalogValidator = new CatalogValidator($dbSettings, $dbSctructure, $htAccessValidator->GetRedirectsSourcesIDs('string'));

//print_r($htAccessValidator->GetErrors('MULTIPLY_SOURCES'));
//print_r($htAccessValidator->GetErrors('HIDDEN_CATALOG_SECTION'));
//print_r($htAccessValidator->GetErrors('TARGET_REDIRECT_CATALOG_SECTION_NOT_EXISTS'));
//print_r($htAccessValidator->GetErrors());

print_r($catalogValidator->GetErrors());

?>