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

// Initial Version: Brian Beck, 2008
// Refactor: Joseph Deming, Anthony D'Onofrio, 2013

require_once(__DIR__.'/listControl.php');
require_once(__DIR__.'/../includes/includes.php');
Manager::AddJS('controls/multiListControl.js', Manager::JS_CLASS); 

/**
 * @class MultiListControl object
 *
 * This class respresents a MultiListControl in KORA
 */
class MultiListControl extends ListControl
{
	protected $name = "Multi-List Control";
	
	/**
	  * Standard constructor for a control. See Control::Construct for details.
	  *
	  * @return void
	  */
	public function MultiListControl($projectid='', $controlid='', $recordid='', $inPublicTable = false)
	{
		if (empty($projectid) || empty($controlid)) return;
		global $db;
		
		$this->Construct($projectid,$controlid,$recordid,$inPublicTable);
		
		$this->isSortValid = false;
		
		// If data exists for this control, get it
		if (!empty($this->rid))
		{
			$this->LoadValue();
		}
		else if (isset($this->options->defaultValue->option))
		{
			// Otherwise, this is an initial ingestion, so fill in the default
			$this->value = simplexml_load_string(utf8_encode('<multilist />'));
			foreach($this->options->defaultValue->option as $option)
			{
				$this->value->addChild('value', xmlEscape((string)$option));
			}
		}
	}
	
	/**
	  * Prints control view for public ingestion
	  *
	  * @return void
	  */
	public function display($defaultValue=true)
	{
		$hasDef = false;
		if($this->value != null && $this->value->value != ""){
			$hasDef = true;
		}
		if (!$this->StartDisplay($hasDef)) { return false; }
		
		echo "<select id=".$this->cName." class='kcmlc_curritems' name=".$this->cName."[] multiple='multiple' size='5'>";
		
		$values = array();
		if (isset($this->value->value))
		{
			foreach($this->value->value as $v)
			{
				$values[] = (string)$v;
			}
		}
		
		// display the options, with the current value selected.
		foreach($this->options->option as $option) {
			echo "<option value='".htmlEscape($option)."'";
			if(in_array($option, $values) && $defaultValue) echo ' selected="selected"';
			echo ">$option</option>\n";
		}
		
		echo '</select>';
		$this->EndDisplay();
	}
	
	/**
	  * Print out the XML value of the MLC
	  *
	  * @return void
	  */
	public function displayXML()
	{
		if( !$this->isOK()) return;
		
		$values = array();
		if (isset($this->value->value))
		{
			foreach($this->value->value as $v)
			{
				$values[] = (string)$v;
			}
		}
		
		$xmlstring = "<multilist>";
		foreach($this->options->option as $option)
		{
			if(in_array($option, $values))
			{
				$xmlstring .= '<value>'.xmlEscape($this->value).'</value>';
			}
		}
		$xmlstring .= '</multilist>';
		return $xmlstring;
	}
	
	/**
	  * Return string to enter into a Kora_Clause
	  *
	  * @param string $submitData Submited data for control
	  *
	  * @return Search string on success
	  */
	public function getSearchString($submitData) {
		if(isset($submitData[$this->cName]) && !empty($submitData[$this->cName])) {
			
			$options = array();
			foreach($submitData[$this->cName] as $value) {
				$options[] = array('LIKE',"'%<value>$value</value>%'");
			}
			
			return $options;
		}
		else return false;
	}
	
	public function getType() { return "List (Multi-Select)"; }
	
	/**
	  * Set the value of the XML imput
	  *
	  * @param string $value Value to set
	  *
	  * @return void
	  */
	public function setXMLInputValue($value) {
		$x = array();
		foreach($value as $v)
		{
			$x[] = xmlEscape($v);
		}
		$this->XMLInputValue = $x;
	}
	
	/**
	  * Add values from array to the MLC object
	  *
	  * @param Array[string] $valueArray Values to load
	  *
	  * @return void
	  */
	private function loadValues($valueArray) {
		$this->value = simplexml_load_string(utf8_encode('<multilist></multilist>'));
		
		foreach($valueArray as $selectedOption)
		{
			$this->value->addChild('value', xmlEscape($selectedOption));
		}
	}
	
	/**
	  * Ingest the data into the control
	  *
	  * @param string $publicIngest Are we ingesting the data publically
	  *
	  * @return void
	  */
	public function ingest($publicIngest = false)
	{
		global $db;
		
		if (!$this->isOK()) return;
		
		//determine whether to insert into public ingestion table or not
		if($publicIngest)
		{
			$tableName = 'PublicData';
		}
		else $tableName = 'Data';
		
		if (empty($this->rid)) {
			echo '<div class="error">'.gettext('No Record ID Specified').'.</div>';
			return;
		} else if (isset($this->XMLInputValue)) {
			$this->loadValues($this->XMLInputValue);
		} else if (isset($_REQUEST[$this->cName]) && !empty($_REQUEST[$this->cName])){
			$this->loadValues($_REQUEST[$this->cName]);
		} else {
			$this->loadValues(array());
		}
		
		// ingest the data
		$query = '';    // default blank query
		if ($this->existingData) {
			if ($this->isEmpty()) $query = 'DELETE FROM p'.$this->pid.$tableName.' WHERE id='.escape($this->rid).
				' AND cid='.escape($this->cid).' LIMIT 1';
			else $query = 'UPDATE p'.$this->pid.$tableName.' SET value='.escape($this->value->asXML()).
			' WHERE id='.escape($this->rid).' AND cid='.escape($this->cid).' LIMIT 1';
		} else {
			if (!$this->isEmpty()) $query = 'INSERT INTO p'.$this->pid.$tableName.' (id, cid, schemeid, value) VALUES ('.escape($this->rid).', '.escape($this->cid).', '.escape($this->sid).', '.escape($this->value->asXML()).')';
		}
		
		if (!empty($query)) $db->query($query);
	}
	
	public function isXMLPacked() { return true; }
	
	/**
	  * Get the data from the control for display
	  *
	  * @return control data
	  */
	public function showData()
	{
		if (!empty($this->rid))
		{
			if (isset($this->value->value))
			{
				$returnString = '';
				foreach($this->value->value as $val)
				{
					$val = (string) $val;
					$returnString .= htmlEscape($val).'<br />';
				}
				return $returnString;
			}
		}
	}
	
	/**
	  * Gather information about control for display
	  *
	  * @param string $xml XML to write information to
	  * @param int $pid Project ID
	  * @param int $cid Control ID
	  *
	  * @return XML object
	  */
	public function storedValueToDisplay($xml,$pid,$cid)
	{
		$xml = simplexml_load_string($xml);
		
		$returnVal = '';
		if (isset($xml->value))
		{
			foreach($xml->value as $v)
			{
				$v = (string) $v;
				$returnVal .= htmlEscape($v).'<br />';
			}
		}
		
		return $returnVal;
	}
	
	/**
	  * Gathers values from XML
	  *
	  * @param string $xml XML object to get data from
	  *
	  * @return Array of values
	  */
	public function storedValueToSearchResult($xml)
	{
		$xml = simplexml_load_string($xml);
		
		$returnVal = array();
		if (isset($xml->value))
		{
			foreach($xml->value as $v) $returnVal[] = (string) $v;
		}
		
		return $returnVal;
	}
	
	/**
	  * Validates the ingested data to see if it meets the data requirements for this control
	  *
	  * @param bool $publicIngest Is this a public ingestion
	  *
	  * @return Result string
	  */
	public function validateIngestion($publicIngest = false)
	{
		if ($this->required && $this->isEmpty()){
			return gettext('No value supplied for required field').': '.htmlEscape($this->name);
		}
		
		if(!empty($_REQUEST[$this->cName])){
			$value = explode('<MLC>',$_REQUEST[$this->cName][0]);
		}else if (!empty($this->XMLInputValue)){
			$value = $this->XMLInputValue;
		}else return '';
		
		foreach ($this->options->option as $option) {
			$optionArray[] = (string) $option;
		}
		
		foreach($value as $v){
			// THIS HAPPENS WHEN NOTHING IS SELECTED AND AN EMPTY ARRAY IS PASSED VIA JAVASCRIPT
			if ($v === '') { continue; }
			if(!in_array((string)$v,$optionArray)){
				return '"'.htmlEscape($v).gettext('" is not a valid value for '.$this->GetName());
			}
		}
	}
	
	/**
	  * Print out the control options for the control
	  *
	  * @return void
	  */
	public function PrintControlOptions()
	{
		Control::PrintControlOptions();
		$this->showDialog();
	}
	
	/**
	  * Print out each menu piece of the control options
	  *
	  * @return void
	  */
	public function showDialog()
	{
		print "<div class='kora_control kora_control_opts' pid='{$this->pid}' cid='{$this->cid}'>";
		$this->OptPrintListOption();
		$this->OptPrintMultiDefValue();
		$this->OptPrintPresets();
		$this->OptPrintNewPreset();
		print "</div>";
	}
	
	/**
	  * Print out table for default values
	  *
	  * @return void
	  */
	protected function OptPrintMultiDefValue(){
		$xml = $this->GetControlOptions();
		if(!$xml) return;
		?>
		
		<table class="table kcopts_style">
		<tr>
		<td width="60%" class="kcopt_label"><b><?php echo gettext('Default Value')?></b><br /><?php echo gettext('Optionally, select which value(s) which will be initially selected upon first ingestion')?>.</td>
		<td>
		<?php
		// get the list of default values
		$currentDefaults = array();
		if (isset($xml->defaultValue->option)) { foreach($xml->defaultValue->option as $option) {
			$currentDefaults[] = (string)$option;
		}}
		
		$selected = '<select name="selectedDefault" class="kcmlcopts_selDef" size="7">';
		$unselected = '<select name="unselectedDefault" class="kcmlcopts_unSelDef" size="7">';
		
		// display all the modifiers
		foreach($xml->option as $option) {
			if (in_array((string)$option, $currentDefaults))
			{
				$selected .= '<option>'.htmlEscape($option)."</option>\n";
			}
			else
			{
				$unselected .= '<option>'.htmlEscape($option)."</option>\n";
			}
		}
		
		$selected .= '</select>';
		$unselected .= '</select>';
		
		echo '<table border="0"><tr><td>'.gettext('Options').'</td><td></td><td>'.gettext('Default Value').'</td></tr>';
		echo "<tr><td>$unselected</td>";
		echo '<td><input type="button" value="-->" class="kcmlcopts_dvBtnAddDef" /><br /><br />';
		echo '<input type="button" value="<--" class="kcmlcopts_dvBtnRemDef" /></td>';
		echo "<td>$selected</td></tr></table>";
		?>
		</td>
		</tr>
		</table>
		
		<?php
	}
	
	/**
	  * Update the default value of the MLC
	  *
	  * @param string $values 
	  *
	  * @return void
	  */
	public function updateMultiDef($values){
		$defval = '';
		foreach ($values as $value)
			{ $defval .= "<option>".xmlEscape($value)."</option>"; }
		$this->SetExtendedOption('defaultValue', $defval);
		echo gettext('Default Value Updated').'.<br /><br />';
	}
}

?>