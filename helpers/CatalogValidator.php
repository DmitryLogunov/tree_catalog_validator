<?php
require_once dirname(__DIR__)."/models/Catalog.php";
require_once "Validator.php";

class CatalogValidator extends Validator {
  private $catalog, $redirectSourcesIDs;
  
 /**
   * Constructor CatalogValidator
   *
   * @param [Array] $dbSettings
   * @return void
   */
  function CatalogValidator($dbSettings = null, $dbStructure = null, $redirectSourcesIDs = null) {
    Validator::__construct();
 
    if(!$redirectSourcesIDs) $this->redirectSourcesIDs = $redirectSourcesIDs;

    $this->catalog = new Catalog($dbSettings, $dbStructure); 
    
    $this->validate();
  }

  private

/**
   * Do all validation checks
   *
   * @return void
   */
  function validate() {    
    $this->checkIsThereLostSections();
    $this->checkIsThereLostItems();
    $this->checkIsThereEndingSectionsWithoutItems($this->redirectSourcesIDs);
  }  

  /**
   * Checks if there are lost section which meet the conditions: 
   * the section has child and has no parent and it's not the root section 
   * (child_id != root_section_id && child_id != NULL && parent_section is not exist) 
   *
   * @return void
   */
  function checkIsThereLostSections() {
    $lostSectionsIds = $this->catalog->GetLostSectionsIds();
    foreach($lostSectionsIds as $lostSectionID) {
      $childsIDs = $this->catalog->GetAllChildSectionsIds($lostSectionID);
      $parentID = $this->catalog->GetParentId($lostSectionID);
      
      $info = "Lost section. ID: " . $lostSectionID 
              . "; Parent section ID (is not exist): " . $parentID
              . "; Child sections IDs: " . join(", ", $childsIDs); 
      $this->AddError('Lost section', 'LOST_SECTION', $info);       
    }
  }

  /**
   * Checks if there are lost items which have not exist parent sections 
   *
   * @return void
   */
  function checkIsThereLostItems() {
    $lostItems = $this->catalog->GetLostItems();
    foreach($lostItems as $lostItem) {     
      $info = "Not exists section ID: " . $lostItem['parent_id'] 
              . "; The nubmer of lost models: " . $lostItem['num_items']
              . "; Lost models IDs: " . $lostItem['items_ids']; 
      $this->AddError('Lost models', 'LOST_MODELS', $info);       
    }
  }  

  /**
   * Checks if there are ending sections without items  
   *
   * @return void
   */
  function checkIsThereEndingSectionsWithoutItems($exceptedIds = null) {
    $childlessEndingSectionsIDs = $this->catalog->GetEndingSectionsWithoutItemsIds($exceptedIds);

    foreach($childlessEndingSectionsIDs as $childlessEndingSectionsID) {     
      $info = "Ending section without models, ID: " . $childlessEndingSectionsID; 
      $this->AddError('Ending section without models', 'ENDING_SECTION_WITHOUT_MODELS', $info);       
    }
  }   
}