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
// Refactor: Joe Deming, Anthony D'Onofrio 2013

require_once(__DIR__.'/textControl.php');
require_once(__DIR__.'/../includes/includes.php');
Manager::AddJS('controls/multiTextControl.js', Manager::JS_CLASS); 

/**
 * @class MultiTextControl object
 *
 * This class respresents a TextControl in KORA
 */
class MultiTextControl extends TextControl
{
	protected $name = 'Multi-Text Control';
	
	/**
	  * Standard constructor for a control. See Control::Construct for details.
	  *
	  * @return void
	  */
	public function MultiTextControl($projectid='', $controlid='', $recordid='', $inPublicTable = false)
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
		else if (isset($this->options->defaultValue->value))
		{
			// Otherwise, this is an initial ingestion, so fill in the default
			$this->value = simplexml_load_string('<multitext />');
			foreach($this->options->defaultValue->value as $option)
			{
				$this->value->addChild('text', xmlEscape((string)$option));
			}
		}
	}
	
	/**
	  * Prints control view for public ingestion
	  *
	  * @param bool $isSearchForm Is this for a search form instead
	  *
	  * @return void
	  */
	public function display($defaultValue=true)
	{
		global $db;
		
		if (!$this->StartDisplay()) { return false; }
		?>
		
		<table>
		<tr>
		<td><input type="text" class="kcmtc_additem" name="Input<?php echo $this->cid?>" id="Input<?php echo $this->cid?>" value="" /></td>
		<td><input type="button" class="kcmtc_additem" value="<?php echo gettext('Add')?>" /></td>
		</tr>
		<tr><td rowspan='3'>
		<select id="<?php echo $this->cName?>" class="kcmtc_curritems fullsizemultitext" name="<?php echo $this->cName?>[]" multiple="multiple" size="5">
		<?php       if (isset($this->value->text))
		{
			foreach($this->value->text as $text) {
				if($this->hasData())
					echo '            <option value="'.(string)$text.'" selected>'.(string)$text."</option>\n";
				else
					echo '            <option value="'.(string)$text.'">'.(string)$text."</option>\n";
			}
		}
		?>
		</select>
		</td>
		<td><input type="button" class="kcmtc_removeitem" value="<?php echo gettext('Remove')?>" /></td>
		</tr>
		<tr>
		<td><input type="button" class="kcmtc_moveitemup" value="<?php echo gettext('Up')?>" /></td>
		</tr>
		<tr>
		<td><input type="button" class="kcmtc_moveitemdown" value="<?php echo gettext('Down')?>" /></td>
		</tr>
		</table>

		<?php $this->EndDisplay(); ?>
		<?php
	}
	
	/**
	  * Print out the XML value of the MTC
	  *
	  * @return void
	  */
	public function displayXML()
	{
		if(!$this->isOK()) return;
		
		$xmlString = '<multitext>';
		
		foreach($this->value->text as $text)
		{
			$xmlString .= '<text>'.xmlEscape( (string) $text).'</text>';
		}
		
		$xmlString .= '</multitext>';
		
		return $xmlString;
	}
	
	public function getType()
	{
		return 'Text (Multi-Input)';
	}
	
	/**
	  * Return string to enter into a Kora_Clause
	  *
	  * @param string $submitData Submited data for control
	  *
	  * @return Search string on success
	  */
	public function getSearchString($submitData) {
		if (isset($submitData[$this->cName]) && !empty($submitData[$this->cName])) {
			$values = array();
			foreach($submitData[$this->cName] as $text) {
				$values[] = array('LIKE',"'%<text>".$text."</text>%'");
			}
			return $values;
		}
		else
	    	return false;
    }
    	
    /**
	  * Set the value of the XML imput
	  *
	  * @param string $value Value to set
	  *
	  * @return void
	  */
    public function setXMLInputValue($value) {
    	$this->XMLInputValue = $value;
    }
    
    /**
	  * Load values from a text array and save to control
	  * 
	  * @param Array[string] $textArray Texts to add
	  *
	  * @return void
	  */
    private function loadValues($textArray) {
    	$this->value = simplexml_load_string('<multitext></multitext>');
    	foreach($textArray as $selectedOption)
    	{
    		$this->value->addChild('text', xmlEscape($selectedOption));
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
    		}else if (!empty($_REQUEST) && isset($_REQUEST[$this->cName])) {
			$this->loadValues($_REQUEST[$this->cName]);
		} else {
			$this->value = simplexml_load_string('<multitext></multitext>');
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
		if (!empty($this->rid) && isset($this->value->text))
		{
			$returnString = '';
			foreach($this->value->text as $text)
			{
				$returnString .= (string)$text . '<br />';
			}
			return $returnString;
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
		
		foreach($xml->text as $text)
		{
			$returnVal .= (string)$text . '<br />';
		}
		
		return $returnVal;
	}
	
	/**
	  * Gathers values from XML
	  *
	  * @param string $xml XML object to get data from
	  *
	  * @return Array of text values
	  */
	public function storedValueToSearchResult($xml)
	{
		$xml = simplexml_load_string($xml);
		
		$returnVal = array();
		
		foreach($xml->text as $text)
		{
			$returnVal[] = (string)$text;
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
			$texts = $_REQUEST[$this->cName];
		}else if (!empty($this->XMLInputValue)){
			$texts = $this->XMLInputValue;
		}else return '';
		
		if($texts[0]=='' && $this->required){
			return gettext('No value supplied for required field').': '.htmlEscape($this->name);
		}
		
		$pattern = (string) $this->options->regex;
		if (empty($pattern)) return '';
				
		$returnVal = '';
		foreach($texts as $text)
		{
			// THIS HAPPENS WHEN NOTHING IS SELECTED AND AN EMPTY ARRAY IS PASSED VIA JAVASCRIPT
			if ($text === '') { continue; }
			if (!preg_match($pattern, $text))
			{
				$returnVal = gettext('Value supplied for field').': '.htmlEscape($this->name).' '.gettext('does not match the required pattern').'.';
			}
		}
		return $returnVal;
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
		$this->OptPrintDefaultValue();
		$this->OptPrintRegEx();
		$this->OptPrintPresets();
		$this->OptPrintSavePreset();
		print "</div>";
	}
	
	// TODO:  KILL ALL OF THESE UGLY TABLES
	
	/**
	  * Print out table for default value
	  *
	  * @return void
	  */
	protected function OptPrintDefaultValue()
	{ 
		$xml = $this->GetControlOptions();
		if(!$xml) return;
		?>
		<table class="table kcopts_style">
		<tr>
		<td width="60%" class="kcopt_label"><b><?php echo gettext('Default Value')?></b><br />(<?php echo gettext('Leave blank to have no initial value')?>)</td>
		<td>
		<div class="kora_control">
		<table>
		<tr>
		<td><input type="text" name="defVal" class='kcmtcopts_defnew' /></td>
		<td><input type="button" value="<?php echo gettext('Add')?>"  class='kcmtcopts_defadd' /></td>
		</tr>
		<tr><td rowspan='3'>
		<select name="defaultValue" class='kcmtcopts_defval fullsizemultitext' size="5">
		<?php
		if (isset($xml->defaultValue->value))
		{
			foreach($xml->defaultValue->value as $value)
			{
				echo '<option value="'.htmlEscape((string)$value).'">'.htmlEscape((string)$value).'</option>';
			}
		}
		?>
		</select>
		</td>
		<td><input type="button" value="<?php echo gettext('Remove')?>"  class='kcmtcopts_defremove' /></td>
		</tr>
		<tr>
		<td><input type="button" value="<?php echo gettext('Up')?>" class='kcmtcopts_defmoveup' /></td>
		</tr>
		<tr>
		<td><input type="button" value="<?php echo gettext('Down')?>"  class='kcmtcopts_defmovedown' /></td>
		</tr>
		</table>
		</div>
		</td>
		</tr>
		</table>
	<?php }
	
	/**
	  * Update the default value for the MTC
	  *
	  * @param Array[string] $values Default values
	  *    span multiple lines.
	  *
	  * @return result string on success
	  */
	public function updateDefaultValue($values)
	{
		$defval = '';
		foreach ($values as $value)
		{ $defval .= "<value>".xmlEscape($value)."</value>"; }
		$this->SetExtendedOption('defaultValue', $defval);
		echo gettext('Default Value Updated').'.<br /><br />';
	}
}

?>
