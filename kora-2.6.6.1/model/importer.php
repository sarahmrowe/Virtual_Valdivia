<?php 
/**
Copyright (2008) Matrix: Michigan State University

This file is part of KORA.

KORA is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

KORA is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>. */

// Initial Version: Meghan McNeil, 2009
// Refactor: Joe Deming, 2013

class Importer {
	
	protected $pid = 0;
	protected $sid = 0;

	protected $consistentDataSet = array();
	protected $specificDataSet = array();
	
	protected $keyfield = '';
	
	protected $existingTagnames = array();
	protected $controlMapping = array();
	protected $unmappedControls = array();

	protected $autoMappingArray = array( "All File Controls"=>array('ImageControl','FileControl') );
	public $associationArray = array();
	
	public function Importer($pid,$sid,$uploadedFiles = false) {
		$this->uploadedFiles = $uploadedFiles;
		$this->pid = $pid;
		$this->sid = $sid;
	}
	
	/***************************************************
	 * 			  START PROTECTED FUNCTIONS			   *
	 ***************************************************/
	
	/**
	 * Adds a tag name to existingTagnames and if a control matches the tagname it's added to controlMapping
	 */
	protected function addTagName($tagname) { 
		global $db;
		
		if (!in_array($tagname,$this->existingTagnames)) {
			$query = "SELECT name FROM p".$this->pid.'Control WHERE name="'.$tagname.'" AND schemeid='.$this->sid." LIMIT 1";
			$result = $db->query($query);
			$data = $result->fetch_assoc();
			
			if ($data['name']) {
				$this->controlMapping[$tagname] = $data['name'];
			}
			
			array_push($this->existingTagnames,$tagname);
		}
	}
	
	/**
	 * Checks to see if a mapping was previously defined
	 * @param $tag 
	 * 		tagname string
	 * @return $defaultMapping 
	 * 		control name that if could be mapped too
	 */
	protected function checkPreviousMapping($tag) {
		$defaultMapping = false;
		
		//if the mapping was already selected or maps up with a control name, use that control as default
		if (isset($_SESSION['controlMapping_'.$this->sid][$tag]) /*&& $this->controlNameExists($_SESSION['controlMapping_'.$this->sid][$tag])*/) {
			$defaultMapping = $_SESSION['controlMapping_'.$this->sid][$tag];
		} else if (isset($this->controlMapping[$tag])) {
			$defaultMapping = $this->controlMapping[$tag];
		}
		
		if($defaultMapping) {
			$controlData = explode('->',$this->GetControlType($defaultMapping));
			
			if($controlData[1] == "AssociatorControl") {
				$c = Manager::GetControl($this->pid,$controlData[0]);
				$allowedSchemes = $c->GetAllowedAssociations();
				$schemeNames = $this->GetSchemeNames(array($allowedSchemes[0]));
				array_push($this->associationArray,array($this->sid,$tag,$allowedSchemes[0],implode('/',$schemeNames[$allowedSchemes[0]])));
			}
			
			$this->removeFromUnmappedControls($defaultMapping);
			
			//if the defaultmapping is in the autoMapping array, remove the other controls
			if (in_array( $defaultMapping,array_keys($this->autoMappingArray) )) {
				$this->removeControlsByControlType($defaultMapping);
			}
		}
		
		return $defaultMapping;
	}
	
	/**
	 * Get all the controls from the selected scheme
	 */
	protected function getAllControls() {
		global $db;
		
		$query = "SELECT cid,name FROM p".$this->pid."Control WHERE schemeid=".$this->sid;
		$result = $db->query($query);
		
		$returnValues = array();
		while ($data = $result->fetch_assoc()) {
			$returnValues[$data['cid']] = $data['name'];
		}
		return $returnValues;
	}
	
	/**
	 * Returns the control name based on control Id
	 */
	protected function getControlNameById($cid) {
		global $db;
		
		$query = "SELECT name FROM p".$this->pid."Control WHERE cid=".$cid." AND schemeid=".$this->sid." LIMIT 1";
		$result = $db->query($query);
		$data = $result->fetch_assoc();
		
		return $data['name'];
	}
	
	/**
	 * Decifers whether or not to save values as xml or a string
	 * Recognizes:
	 * <BaseTag>
	 * 		<page>page1.jpg</page>
	 * 		<page>page2.jpg</page>
	 * 		<page>page3.jpg</page>
	 * </BaseTag>
	 * <AnotherTag>a value</AnotherTag>
	 */
	protected function parseValues($value) {
		
//		if ($value->children()){
        //attributes are invisible to a foreach loop, but they still count as children.
        if (count($value->children()) > count($value->attributes())) {
			return $value->asXML();
		} else {
			return (string) $value;
		}
	}
	
	/**
	 * Remove control names from unmapped control based on a general mapping
	 * @param $key general mapping (ie. All File Controls)
	 */
	protected function removeControlsByControlType($key) {
		global $db;
		
		$query = "SELECT name FROM p".$this->pid."Control WHERE type IN ('".implode("','",$this->autoMappingArray[$key])."')";
		$result = $db->query($query);
		
		while($data = $result->fetch_assoc()) {
			$this->removeFromUnmappedControls($data['name']);
		}
	}
	
	/**
	 * Remove control names from unmappedControls array
	 * @param $value to remove from array
	 */
	protected function removeFromUnmappedControls($value) {
		//if defaultMapping is in unmappedControls, remove it
		$key = array_search($value,$this->unmappedControls);
		if ($key >= 0) {
			unset($this->unmappedControls[$key]);
		}
	}
	
	/**
	 * Returns an array of all the controls that were not mapped to a tag name
	 */
	protected function setUnmappedControls() {
		global $db;
		$query = "SELECT cid,name FROM p".$this->pid."Control WHERE schemeid=".$this->sid;
		$sqlRestrictions = array();
		foreach ($this->controlMapping as $mappedControl) {
			array_push($sqlRestrictions, "name != '$mappedControl' ");
		}
		if (!empty($sqlRestrictions)) {
			$query .= " AND ".implode(' AND ',$sqlRestrictions);
		}
		$query .= " ORDER BY name";
		$result = $db->query($query);
		
		$this->unmappedControls[-1] = ' -- Ignore -- ';
		while ($data = $result->fetch_assoc()) {
			$this->unmappedControls[$data['cid']] = $data['name'];
		}
		
		// this removed to prevent "All File Controls" from showing up in 
		// the mapping table.  The "All File Controls" functionality breaks 
		// when there is more than one file/image control in a scheme.
//	    if ($this->uploadedFiles) {
//            array_push($this->unmappedControls,"All File Controls");
//        }
	}
	
	
	/***************************************************
	 * 			   START PUBLIC FUNTIONS			   *
	 ***************************************************/
	
	/**
	 * Create mapping table for user to decide how to map the tagnames to control names
	 */
	public function drawControlMappingTable($drawButtons = true) {
		//initilize data to create mapping table
		$this->setUnmappedControls();
		$disableContinue = "";
		

		 
		//start drawing mapping table
		print "Please match each XML tag name with the corresponding control in your scheme. <br/>";
		//print "<strong>File and image controls should map to \"All File Controls\"</strong>, not the actual control name.<br/><br/>";
		print "If the XML was exported from KORA, the \"id\" tag should be set to \"Ignore\".<br/><br/>";  
		
		//if there is a keyfield, print it out
		if (!empty($this->keyfield))
			print "Keyfield:".$this->keyfield."</br></br>";
		
		print '<table class="importer_table_keys" border=1>';
		print "<tr><td>XML Tag Name</td><td>Scheme Control Name</td><td></td></tr>";
		for ($i=0 ; $i<sizeof($this->existingTagnames) ; ++$i) {
			$tag = $this->existingTagnames[$i];
			print "<tr>";
			print '<td id="tagCell_'.$this->sid.'_'.$i.'" class="tagname">'.$tag.'</td><td id="controlCell_'.$this->sid.'_'.$i.'">';
			
			$defaultMapping = $this->checkPreviousMapping($tag);
			
			//print defaultMapping otherwise display select box with unmapped Controls
			if ($defaultMapping) {
				print $defaultMapping.'</td>';
				print '<td id="action_'.$this->sid.'_'.$i.'"><a onclick="MappingManager.setUnmappedControls(\''.implode("///",$this->unmappedControls).'\');MappingManager.removeMapping('.$this->sid.','.$i.');">Edit</a>';
			}
			else {
				//if a select box is needed for the control mapping, disable the continue button
				$disableContinue = 'disabled="true"';
				
				print '<select id="tagnameSelect_'.$this->sid.'_'.$i.'" name="'.$tag.'" class="tagnameSelect" onselect="alert(\'changed\');">';
				foreach ($this->unmappedControls as $control) {
					print '<option value="'.$control.'">'.$control.'</option>';
				}
				print '</select></td><td id="action_'.$this->sid.'_'.$i.'"><a onclick="MappingManager.addMapping('.$this->sid.','.$i.');">OK</a></td>';
				
			}
			//unset defaultMapping so it doesn't display for the next tag
			unset($defaultMapping);
			print "</td></tr>";
		}
		print "</table>";
		
		
		
		if ($drawButtons) {
			echo "<br/><p style='color:red'>";
			echo gettext("Please be aware that large record sets will take quite some time.");
			echo "</p>";
			print '<input type="button" id="continueButton" onclick="MappingManager.submit();" value="Import" '.$disableContinue.'/>';
			print '<input type="button" id="cancelButton" value="Cancel" onclick="cancelIngestion();" />';
			print '<img src="images/indicator.gif" id="indicator" alt="Loading..." style="border:none;display:none;"/>';
			print "<div class='kora_import_progress'></div>";
		}
		
		
	}
	
	/**
	 * Returns data from the XML file
	 */
	public function getRecordData() {
		// specific data should ALWAYS be first so that the specific 
		// data will override consistent data
		return $this->specificDataSet+$this->consistentDataSet;
	}
	
	/**
	 * Loads data from XML that is consistant across all records
	 */
	public function loadConsistentData($data) {
		if (!empty($data)) {
			foreach ($data->children() as $tagName=>$value) {
				$tagName = str_replace('_',' ',$tagName);
    			//  $value->attributes() returns a SimpleXMLElement object with one element, an array of attributes 
                foreach((array)$value->attributes() as $attributeArray){
                    $this->specificDataSet[$tagName]['_attributes']=$attributeArray;                        
                }
				
				$this->consistentDataSet[$tagName][] = $this->parseValues($value);
				$this->addTagName($tagName);
			}
		}
	}
	
	/**
	 * Load data from XML that is specific to a single record
	 */
	public function loadSpecificData($data) {
		$this->specificDataSet = array();
		if (!empty($data)) {
			foreach ($data->children() as $tagName=>$value) {
				$tagName = str_replace('_',' ',$tagName);
	       		//  $value->attributes() returns a SimpleXMLElement object with one element, an array of attributes 
                foreach((array)$value->attributes() as $attributeArray){
                    $this->specificDataSet[$tagName]['_attributes']=$attributeArray;                        
                }					
				$this->specificDataSet[$tagName][] = $this->parseValues($value);
				$this->addTagName($tagName);
			}
		}
	}
	
	/**
	 * Set keyfield
	 */
	public function setKeyfield($key)
	{
		$this->keyfield = $key;
	}
	
	/**
	* ALL THIS IS FROM OLD CONTROLDATA FILE *
	                                       **/
	                                       
       /**
       * Adds an additional mapping table for associations
       * @param $fromSid -- scheme id from the original scheme
       * @param $fromName -- control name of the assocator 
       * @param $toSid -- schemeid to associate to $fromName
       * @param $toName -- name of project/scheme of $toSid
       */
       public function AddNewMappingTable($fromSid,$fromName,$toSid,$toName) {
	       global $db;
	       
	       $drawAdditionalTable = false;
	       
	       //$fileUploaded = (isset($_FILES['zipFolder']) && $_FILES['zipFolder']['error'] != 4) ? true : false;
	       
	       //if ($toSid != $this->sid) { Manager::PrintErrDiv('Cannot AddNewMapTable, requested SID does not match Importer.'); return false; }
	       
	       for ($i=0 ; $i<sizeof($_SESSION['xmlRecordData'][$fromSid]) ; ++$i) {
		       //store data in $record to easily read 
		       $record = $_SESSION['xmlRecordData'][$fromSid][$i];

		       if(isset($record[$fromName]) && !empty($record[$fromName])) {
			       $isXml = false;
			       $recordArray = array();	
			       foreach ($record[$fromName] as $assoc) {
				       if (preg_match('/^[0-9A-F]+-[0-9A-F]+-[0-9A-F]+$/',$assoc)) {
					       $recordArray[] = $assoc;
				       } else {					
					       $drawAdditionalTable = true;
					       $isXml = true;
					       //foreach xml string, load it into the Importer
					       $xml = simplexml_load_string($assoc);
					       foreach($xml->children() as $sub) {
						       $this->loadSpecificData($sub); 
						       $recordArray[] = $this->getRecordData();
					       }
				       }
			       }
			       
			       //store new format into data array
			       $_SESSION['xmlRecordData'][$fromSid][$i][$fromName] = $recordArray;
		       } 
	       }
	       
	       //if xml was loaded, then show the mapping table
	       if($drawAdditionalTable) {
		       echo '<div id="'.$fromName.'_'.$fromSid.'">';
		       echo "Associate $fromName -> $toName";
		       $this->drawControlMappingTable(false);
		       echo '</div>';
	       }
       }
       
       
       /**
       * Add autofill rule to a control
       * @param xml - xml options of control to autofill
       * @param fillValue - value to autofill  based on paramRules
       * @param paramRules - rules to autofill fillValue
       * @param fromType - control type of the paramControl
       * @param ruleNum - id attribute of the param tag 
       */
       // THIS FUNCTION IS NOT USED CURRENTLY, SEEMS POSSIBLY USEFUL SO LEFT HERE
       /*
       function CreateAutoFillRule($xml,$fillValue,$paramRules,$fromType,$ruleNum) {
	       $rules = $xml->autoFillRules;
	       $rule = $rules->addChild('param');
	       $rule->addAttribute('id',$ruleNum);
	       
	       $rule->addChild('to',$fillValue);
	       $from = $rule->addChild('from');
	       
	       $i = 0;
	       while (isset($paramRules["val$i"])) {
		       if (is_array($paramRules["val$i"]) && $fromType == 'DateControl') {
			       $val = $from->addChild("val$i");
			       foreach ($paramRules["val$i"] as $type=>$value) {
				       $val->addChild($type,trim($value));
			       }
		       }
		       else {
			       $from->addChild("val$i",$paramRules["val$i"]);
		       }
		       ++$i;
	       }
	       $from->addChild('op',$paramRules["op"]);
	       
	       return $xml;
       }
       */
       
       /**
       * Find auto fill rule based on control options
       * @param paramOptions 
       * @param paramData 
       * @param value
       */
       function FindAutoFill($paramOptions,$paramData,$value) {
	       global $db;
	       
	       $autoFillValue = "";
	       
	       $query = "SELECT name,options FROM p".$this->pid."Control WHERE schemeid=".$this->sid." AND cid=".$paramOptions->autoFill." LIMIT 1";
	       $query = $db->query($query);
	       $data = $query->fetch_assoc();
	       $xml = simplexml_load_string($data['options']);
	       
	       $autoFillRules = $xml->autoFillRules;
	       
	       foreach($autoFillRules->children() as $param) {
		       $from = $param->from;
		       switch($from->op) {
		       case "like":
			       break;
		       case "equals":
			       break;
		       case "between":
			       if ($paramData['type'] == "DateControl") {
				       $inputValue = explode(" ",$value[0]);
				       list($detMon,$detDay,$detYear) = explode('/',$inputValue[0]);
				       
				       $inputValue = array();
				       $val0 = array();
				       $val1 = array();
				       if ($detYear != 0) { 
					       $inputValue[] = $detYear;
					       $val0[] = $from->val0->year;
					       $val1[] = $from->val1->year; 
				       }
				       if ($detMon != 0) { 
					       $inputValue[] = $detMon;
					       $val0[] = $from->val0->month;
					       $val1[] = $from->val1->month; 
				       }
				       if ($detDay != 0) { 
					       $inputValue[] = $detDay;
					       $val0[] = $from->val0->day;
					       $val1[] = $from->val1->day; 
				       }
				       $inputValue = implode('-',$inputValue);
				       $val0 = implode('-',$val0);
				       $val1 = implode('-',$val1);
				       
				       $between = false;
				       if(($inputValue >= $val0 && $inputValue <= $val1) || ($inputValue >= $val1 && $inputValue <= $val0)) {
					       $between = true;
				       }
			       } else {
				       $between = false;
				       //code for between, but not a date
			       }
			       
			       if ($between) {
				       $autoFillValue = array(strip_tags($param->to->asXML()));
			       }
			       break;
		       }
	       }
	       return array($data['name'],$autoFillValue);
       }
       
       /**
       * Get control type by control name
       */
       function GetControlType($controlName) {
	       global $db;
	       
	       $query = "SELECT cid,type FROM p".$this->pid."Control WHERE name='$controlName' AND schemeid=".$this->sid." LIMIT 1";
	       $result = $db->query($query);
	       $data = $result->fetch_assoc();
	       return $data['cid']."->".$data['type'];
       }
       
       /**
       * Get fields from all file and image controls
       */
      function GetFileControls($returnArr=array('*')) {
	       /*global $db;	
	       
	       $controlInfo = array();
	       $query = "SELECT ".implode(",",$returnArr)." FROM p".Manager::GetProject()->GetPID()."Control WHERE schemeid=".Manager::GetScheme()->GetSID()." AND type IN ('ImageControl','FileControl')";
	       $result = $db->query($query);
	       
	       while ($data = $result->fetch_assoc()) {
		       foreach($data as $name=>$value){
			       $controlInfo[] = "$name->$value";
		       }
	       }
	       
	       echo implode('///',$controlInfo);*/
		   trigger_error("Deprecated function 'GetFileControls' in 'importer.php' called.", E_USER_NOTICE);
       }
       
       /**
       * get all schemenames from scheme ids
       */
       // TODO:  THIS IS AN UGLY WAY TO DO THIS, BUT IF YOU LOOK AT WHERE IT IS USED, WE DON'T HAVE A PID IN CONTEXT TO USE TO DO ANYTHING MORE OOP
       function GetSchemeNames($schemeIds) {
	       global $db;
	       
	       $schemeIds[] = 0;
	       
	       $nameQuery =  'SELECT scheme.schemeName AS schemeName, scheme.schemeid AS id,';
	       $nameQuery .= ' project.name AS projectName FROM scheme LEFT JOIN project USING (pid)';
	       $nameQuery .= ' WHERE scheme.schemeid IN ('.implode(',',$schemeIds).')';
	       
	       $schemeNames = array();
	       $nameQuery = $db->query($nameQuery);
	       while($result = $nameQuery->fetch_assoc()) {
		       $schemeNames[$result['id']] = array('project' => $result['projectName'],
			       'scheme'  => $result['schemeName']);
	       }
	       
	       return $schemeNames;
       }
       
	public static function PrintExportOptions()
	{ ?>
		<div><a class='link ks_exportfailedrecords'>Export Failed Data</a> <a class='link ks_exportsuccessrecords'>Export Successful Data</a></div>	
	<?php 
	}
	
	public function ValidateXML($filename) {
		error_reporting(0); //ignore warning errors
		//XMLReader::open may be called statically, but will issue an E_STRICT error.
		$xml = XMLReader::open($filename);
		$xml->setParserProperty(XMLReader::VALIDATE, true);

		error_reporting(E_ALL); // stop ignoring errors
		
		if ($xml->isValid()) {
			print '<div>'.gettext('**VALIDATED').'</div>';
			return true;
		} else {
			return false;
		}
	}
	
       /**
       * remove an xml tag based on attribute
       */
       /* UNUSED
       function removeXMLByAttribute($xml,$attName,$attValue) {
	       $returnStr = "";
	       foreach($xml->children() as $childType=>$childValue)
	       {
		       $keep = true;
		       $attArray = array();
		       foreach($childValue->attributes() as $a=>$b) {
			       if($a == $attName && $b == $attValue) {
				       $keep = false;
			       } else {
				       $attArray[] = "$a=\"$b\""; 
			       }
		       }
		       
		       if ($keep) {
			       if (sizeof($childValue->children()) > 0) {
				       $returnStr .= "<$childType ".implode(" ",$attArray).">".removeXMLByAttribute($childValue,$attName,$attValue)."</$childType>";
			       }
			       else {
				       $returnStr .= "<$childType ".implode(" ",$attArray).">$childValue</$childType>";
			       }
		       }
	       }
	       
	       return $returnStr;
       }
       */
       
       /**
       * Remove xml tag by value
       */
       /* UNUSED
       function removeXMLByValue($xml,$value) {
	       $returnStr = "";
	       foreach($xml->children() as $childType=>$childValue)
	       {
		       if ($childValue != $value) {
			       if (sizeof($childValue->children()) > 0) {
				       $child = removeXMLByValue($childValue,$value);
				       $returnStr .= "<$childType>$child</$childType>";
			       }
			       else {
				       // $childValue is unescaped now, probably from foreach, so we 
				       // need to escape it again.  Fortunately, $value is also unescaped, 
				       // so the comparison in the previous if statement still works.
				       $childValue = xmlEscape($childValue);
				       $returnStr .= "<$childType>$childValue</$childType>";
			       }
		       } else {
			       //once the value is found, set the value to an empty string to be 
			       //sure that if there are duplicates, that only one gets removed.
			       $value = "";
		       }
	       }
	       return $returnStr;
       }	
       */
}
?>