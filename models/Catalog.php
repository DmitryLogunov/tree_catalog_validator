<?php
require_once dirname(__DIR__)."/lib/DBClient.php";

class Catalog extends DBClient {
  
  private $catalog_table, $items_table, $root_section_id;

 /**
  * Constructor of Catalog model
  *
  * @param [type] $dbSettings
  * @return void
  */
  function Catalog($dbSettings, $dbStructure) {  
    if(!$dbSettings) throw new Exception('There are no defined all required DB settings parameters');
    if(!$dbStructure) throw new Exception('There are no defined all required DB structure parameters');

    parent::__construct($dbSettings);   
    
    $this->catalog_table = $dbStructure['CATALOG_SECTIONS_TABLE_NAME'];
    $this->items_table = $dbStructure['CATALOG_ITEMS_TABLE_NAME'];
    $this->root_section_id = $dbStructure['ROOT_SECTION_ID'];
  }

  /**
   * Gets IDs of catalog sections
   *
   * @return Array
   */
  function GetSectionsIDs() {
    return array_map(function($row) { return  $row['id']; } , $this->Select('SELECT id from ' . $this->catalog_table));
  }

  /**
   * Returns array of ending catalog sections IDs (child_id == NULL)
   *
   * @return Array
   */
  function GetEndingSectionsIds() {
    $query = "SELECT section.id 
              FROM " . $this->catalog_table . " as section 
              LEFT JOIN " . $this->catalog_table . " as child_section ON section.id = child_section.id_1
              WHERE child_section.id IS NULL";
    return array_map(function($row) { return  $row['id']; } , $this->Select($query));            
  }

  /**
   * Returns array of interjacent catalog sections IDs (child_id != NULL)
   *
   * @return Array
   */
  function GetInterjacentSectionsIds() {
    $query = "SELECT section.id 
              FROM " . $this->catalog_table . " as section 
              LEFT JOIN " . $this->catalog_table . " as child_section ON section.id = child_section.id_1
              WHERE child_section.id IS NOT NULL";
    return array_map(function($row) { return  $row['id']; } , $this->Select($query));            
  }

  /**
   * Gets lost (hidden) sections catalog Ids which meet the conditions: 
   * the section has child and has no parent and it's not the root section 
   * (child_id != root_section_id && child_id != NULL && parent_section is not exist) 
   * 
   * @return Array
   */
  function GetLostSectionsIds() {
    $query = "SELECT section.id 
              FROM " . $this->catalog_table . " as section
              LEFT JOIN " . $this->catalog_table . " as child_section ON section.id = child_section.id_1
              LEFT JOIN " . $this->catalog_table . " as parent_section ON section.id_1 = parent_section.id
              WHERE section.id != '" . $this->root_section_id . "' AND child_section.id IS NOT NULL 
              AND  parent_section.id IS NULL";

    return array_map(function($row) { return  $row['id']; } , $this->Select($query));               
  }

  /**
   * Returns child sections Ids
   *
   * @param [string] $id
   * @return string
   */
  function GetAllChildSectionsIds($id) {
    $query = "SELECT section.id 
              FROM " . $this->catalog_table . " as section
              WHERE section.id_1 = '" . $id . "'";

    return array_map(function($row) { return  $row['id']; } , $this->Select($query));                 
  }

  /**
   * Returns parent section id
   *
   * @param [string] $id
   * @return string
   */
  function GetParentId($id) {
    $query = "SELECT section.id_1 
              FROM " . $this->catalog_table . " as section
              WHERE section.id = '" . $id . "'";
    
    return array_map(function($row) { return  $row['id_1']; } , $this->Select($query))[0];
  } 
   
  /**
   * Returns lost items (if parent sections don't exist)
   *
   * @return void
   */
  function GetLostItems() {
    $query = "SELECT item.id_m as parent_id, 
                     GROUP_CONCAT(item.id) as items_ids,
                     COUNT(item.id) as num_items
              FROM " . $this->items_table . " as item
              LEFT JOIN " . $this->catalog_table . " as section ON item.id_m = section.id
              WHERE section.id IS NULL
              GROUP BY item.id_m";
    
    return  array_map(function($row) { 
        return  Array( 'parent_id' => $row['parent_id'], 
                       'items_ids' => $row['items_ids'],
                       'num_items' => $row['num_items'] ); 
      } , $this->Select($query)); ;         
  }

  function GetEndingSectionsWithoutItemsIds($exceptedIds = null) {
    $exceptedIdsCondition = $exceptedIds && $exceptedIds != "" ? "AND section.id NOT IN (" . $exceptIds . ")" : "";
    $query = "SELECT section.id
              FROM " . $this->catalog_table . " as section
              LEFT JOIN " . $this->catalog_table . " as child_section ON section.id = child_section.id_1
              LEFT JOIN " . $this->catalog_table . " as parent_section ON section.id_1 =  parent_section.id
              LEFT JOIN " . $this->items_table . " as item ON item.id_m = section.id
              WHERE child_section.id IS NULL AND item.id IS NULL AND parent_section.id IS NOT NULL " . $exceptedIdsCondition;
    
    return array_map(function($row) { return  $row['id']; } , $this->Select($query));          
  }
}

?>