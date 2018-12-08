<?php
require_once dirname(__DIR__)."/models/Catalog.php";
require_once "Validator.php";

class HtAccessValidator extends Validator {

  private $catalog, $catalogSectionsIDs, $pathToHtaccess, $redirectsMap, $redirectsFrom, 
          $redirectsTo, $redirectsFromTo, $redirectsToFrom, 
          $doubleLines, $cyclingRedirects;
  
  /**
   * Constructor HtAccessValidator
   *
   * @param [string] $pathToHtaccess
   * @return void
   */
  function HtAccessValidator($pathToHtaccess = null, $dbSettings = null, $dbStructure = null) {
    Validator::__construct();

    if($dbSettings && $dbStructure) $this->catalog = new Catalog($dbSettings, $dbStructure);

    $this->pathToHtaccess = '/data/.htaccess';
    if ($pathToHtaccess) $this->pathToHtaccess = $pathToHtaccess;    
    
    $htaccessData = file(dirname(__DIR__).$this->pathToHtaccess);
    if (!$htaccessData || count($htaccessData) == 0 ) exit;
 
    $this->redirectsMap = array(); 
    $this->redirectsFromTo = array();
    $this->redirectsToFrom = array();
    $this->redirectsFrom = array();
    $this->redirectsTo = array();
    $this->doubleLines = array();
    $this->cyclingRedirects = array();

    foreach ($htaccessData as $row) {
      if (preg_match('/^RewriteRule \^catalog\/(\d){2,5}\$\t\/catalog\/(\d){2,5}\t\[R=301,L\]/', $row)) {
        $matches = array();
        preg_match_all('/(\d){2,5}/', $row, $matches);
  
        if (!$matches[0]) continue;
  
        $catalogIDFrom = $matches[0][0];
        $catalogIDTo = $matches[0][1];
  
        array_push($this->redirectsMap, array('from' => $catalogIDFrom, 'to' => $catalogIDTo));
        array_push($this->redirectsFrom, $catalogIDFrom);
        array_push($this->redirectsTo, $catalogIDTo);
        $this->redirectsFromTo[$catalogIDFrom] = $catalogIDTo;
        $this->redirectsToFrom[$catalogIDTo] = $catalogIDFrom;
      }    
    }

    $this->validate();
  }

  /**
   * Getter $this->redirectsMap
   *
   * @return Array
   */
  function GetRedirectsMap() {
    return $this->redirectsMap;
  }  
  
  /**
   *  Returns IDs of redirects sources sections
   *
   * @return void
   */
   function GetRedirectsSourcesIDs($format = null) {
     if(!$format || $format != 'string') return array_unique($this->redirectsFrom);
     return join(', ', array_unique($this->redirectsFrom));
   }
   
   
   private

  /**
   * Do all validation checks
   *
   * @return void
   */
  function validate() {    
    $this->checkZeroRedirects();
    $this->checkMultiplyTargets();
    $this->checkMultiplySources();
    $this->checkCyclingRedirects();
    $this->checkChainRedirects();
    $this->checkIsThereHiddenCatalogSections();
    $this->checkIsThereTargetRedirectSectionsWhichNotExist();
  }

  /**
   * Checks zero rdirects ( a => a)
   *
   * @return void
   */
  function checkZeroRedirects() {
    foreach($this->redirectsMap as $index => $redirect) {
      if($redirect['from'] == $redirect['to']) {
        $info = $redirect['from'].' => '.$redirect['to'];
        $this->AddError('Zero redirect', 'ZERO_REDIRECT', $info);
      }
    }
  }

  /**
   * Checks chain rdirects ( a => b, b => c)
   *
   * @return void
   */
  function checkChainRedirects() {
    foreach($this->redirectsTo as $toID) {
      if(array_search($toID, $this->redirectsFrom)) {
        $info = $this->redirectsToFrom[$toID].' => '.$toID. ', '.$toID. ' => '.$this->redirectsFromTo[$toID];        
        $this->AddError('Chain redirect', 'CHAIN_REDIRECT', $info);                   
      }
    }
  }

  /**
   * Checks cycling redirects using recursion ( a => b => ... => a)
   *
   * @return void
   */
  function checkCyclingRedirects() {
    foreach($this->redirectsMap as $index => $redirect) {
      $cyclingRedirectsChain = array();
      array_push($cyclingRedirectsChain, $redirect['from']);

      if (!$this->checkCycling($redirect['to'], $redirect['from'], $cyclingRedirectsChain)) continue;

      $info = $index.': '; 
      foreach($cyclingRedirectsChain as $step) {
        $separator = $info != $index.': ' ? ' => ' : '';
        $info .= $separator.$step;
      }  

      $redirectsSteps = array_unique($cyclingRedirectsChain);
      sort($redirectsSteps);
      $redirectsStepsHash =  base64_encode(json_encode($redirectsSteps));

      if (!(array_search($redirectsStepsHash, $this->cyclingRedirects) > -1)) {
        array_push($this->cyclingRedirects, $redirectsStepsHash); 
        $this->AddError('Cycling redirect', 'CYCLING_REDIRECT', $info);        
      }           
    }
  }

  /**
   * One iteration of checking cycling redirects 
   *
   * @param [strinh] $toID
   * @param [string] $originalFromID
   * @return bool
   */
  function checkCycling($toID, $originalFromID, &$cyclingChain) {
    array_push($cyclingChain, $toID);

    $keysFromIDEqualToID = array_keys($this->redirectsFrom, $toID);

    if(count($keysFromIDEqualToID) == 0) return false;

    foreach($keysFromIDEqualToID as $indexFromIDEqualToID) {   
      if ($this->redirectsTo[$indexFromIDEqualToID] == $originalFromID) {
        array_push($cyclingChain, $this->redirectsTo[$indexFromIDEqualToID]);
        return true;  
      }
      return $this->checkCycling($this->redirectsTo[$indexFromIDEqualToID], $originalFromID, $cyclingChain);
    }
  }
   /**
   *  Checks multiply targets ID in redirects (a => b, c => b) & doubles (a => b, a => b)
   *
   * @return void
   */
  function checkMultiplyTargets() {
    foreach(array_unique($this->redirectsTo) as $toID) {
      $keysToID = array_keys($this->redirectsTo, $toID);
      
      if(count($keysToID) > 1) {
        $fromIDsForMultiplyTargets = array();

        $info = ''; $infoForDoubles = '';
        foreach($keysToID as $index) {
          /* For checking if lines are double */
          array_push($fromIDsForMultiplyTargets, $this->redirectsFrom[$index]); 

          $separator = $info != '' ? ', ' : '';
          $info .=  $separator.$this->redirectsFrom[$index]. ' => '.$toID;
          $infoForDoubles .=  $separator.$index.': '.$this->redirectsFrom[$index]. ' => '.$toID;
        }

        switch(count(array_unique($fromIDsForMultiplyTargets))) {
          case 1:        
            if(!(array_search($fromIDsForMultiplyTargets[0], $this->doubleLines) > -1)) {
              array_push($this->doubleLines, $fromIDsForMultiplyTargets[0]);              
              $errorInfo =  array('Error' => 'Double lines', 'code' => 'DOUBLE_LINES', 'info' => $infoForDoubles);
            }
            break;
          default:          
            $errorInfo =  array('Error' => 'Multiply targets', 'code' => 'MULTIPLY_TARGETS', 'info' => $info);    
        }    

        if(isset($errorInfo)) $this->AddError($errorInfo['Error'], $errorInfo['code'], $errorInfo['info']);
      }
    }
  } 
  
  /**
   * Checks multiply sources ID in redirects (a => b, a => c) & doubles (a => b, a => b)
   *
   * @return void
   */
  function checkMultiplySources() {
    foreach(array_unique($this->redirectsFrom) as $fromID) {
      $keysFromID = array_keys($this->redirectsFrom, $fromID);
      
      if(count($keysFromID) > 1) {
        $toIDsForMultiplySources = array();

        $info = ''; $infoForDoubles = '';
        foreach($keysFromID as $index) {
           /* For checking if lines are double */
           array_push($toIDsForMultiplySources, $this->redirectsTo[$index]); 

          $separator = $info != '' ? ', ' : '';
          $info .=  $separator.$fromID. ' => '.$this->redirectsTo[$index];
          $infoForDoubles .=  $separator.$index.': '.$fromID. ' => '.$this->redirectsTo[$index];
        }

        switch(count(array_unique($toIDsForMultiplySources))) {
          case 1: 
            if(!(array_search($fromID, $this->doubleLines) > -1)) {
              array_push($this->doubleLines, $fromID);          
              $errorInfo =  array('Error' => 'Double lines', 'code' => 'DOUBLE_LINES', 'info' => $infoForDoubles);
            }
            break;
          default:          
            $errorInfo =  array('Error' => 'Multiply sources', 'code' => 'MULTIPLY_SOURCES', 'info' => $info);    
        } 

        if(isset($errorInfo)) $this->AddError($errorInfo['Error'], $errorInfo['code'], $errorInfo['info']);
      }
    }    
  } 
  
  /**
   * Search intersections of Catalog sections IDs and htAccess redirects source IDs
   * If there is intersections then these sections will be hidden
   *
   * @return void
   */
  function checkIsThereHiddenCatalogSections() {
    if(!$this->catalog) return;

    if(!$this->catalogSectionsIDs) $this->catalogSectionsIDs = $this->catalog->GetSectionsIDs();

    foreach($this->catalogSectionsIDs as $sectionID) {
      if(array_search($sectionID, array_unique($this->redirectsFrom)) > -1) {
        $errorInfo =  array('Error' => 'Sections catalog is hidden (it\'s in .htaccess redirects)', 
                            'code' => 'HIDDEN_CATALOG_SECTION', 
                            'info' => 'ID: ' . $sectionID);
        $this->AddError($errorInfo['Error'], $errorInfo['code'], $errorInfo['info']);;
      }
    }
  }

    /**
   * Search target redirect catalog sections which doesn't exist
   *
   * @return void
   */
  function checkIsThereTargetRedirectSectionsWhichNotExist() {
    if(!$this->catalog) return;

    if(!$this->catalogSectionsIDs) $this->catalogSectionsIDs = $this->catalog->GetSectionsIDs();

    foreach(array_unique($this->redirectsTo) as $targetSectionID) {
      if( array_search($targetSectionID, $this->catalogSectionsIDs) > -1 || 
          array_search($targetSectionID, array_unique($this->redirectsFrom)) > -1) continue;

      $info = 'ID: ' . $targetSectionID . ' ( ' . $this->redirectsToFrom[$targetSectionID] . ' => ' . $targetSectionID. ' )';
      $errorInfo =  array('Error' => 'Target redirect catalog section doesn\'t exist', 
                          'code' => 'TARGET_REDIRECT_CATALOG_SECTION_NOT_EXISTS', 
                          'info' => $info);
      $this->AddError($errorInfo['Error'], $errorInfo['code'], $errorInfo['info']);      
    }
  }

}

?>