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

require_once(__DIR__."/dateControl.php");
require_once(__DIR__.'/../includes/includes.php');
Manager::AddJS('controls/multiDateControl.js', Manager::JS_CLASS); 

/**
 * @class MultiDateControl object
 *
 * This class respresents a MultiDateControl in KORA
 */
class MultiDateControl extends DateControl
{
	protected $name = 'Multi-Date Control';
	
	/**
	  * Standard constructor for a control. See Control::Construct for details.
	  *
	  * @return void
	  */
	public function MultiDateControl($projectid='', $controlid='', $recordid='', $inPublicTable = false)
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
		else if (isset($this->options->defaultValue) && !empty($this->options->defaultValue))
		{
			$this->value = $this->options->defaultValue;
		}
	}
	
	/**
	  * Prints control view for public ingestion
	  *
	  * @param bool $defaultValue, if true, use the default if selected, else it's blank at first
	  *
	  * @return void
	  */
	public function display($defaultValue=true)
	{
		global $db;
		
		if (!$this->StartDisplay()) { return false; }
		?>
		
		<table border="0">
		<tr>
		<td><?php $this->PrintDateSelectDivs();	?></td>
		<td><input type="button" class="kcmdc_additem" value="<?php echo gettext('Add')?>" kcmdc_format="<?php echo $this->options->displayFormat ?>" kcmdc_showera="<?php echo $this->options->era ?>" /></td>
		</tr>
		<tr><td rowspan='3'>
		<select id="<?php echo $this->cName?>" class="kcmdc_curritems fullsizemultitext" name="<?php echo $this->cName?>[]" multiple="multiple" size="5">
		<?php       if (isset($this->value->date))
		{
			foreach($this->value->date as $date) {
				$value = htmlEscape($date->asXML());
				
				if ((int)$date->month > 0)
				{
					$month = gettext(DateControl::$months[(int)$date->month]['name']);
				}
				else
				{
					$month = '';
				}
				$day = (int)$date->day;
				$year = (int)$date->year;
				
				$display = DateControl::formatDateForDisplay((int)$date->month, (int)$date->day, (int)$date->year, (string)$date->era, ((string)$this->options->era == 'Yes'),(string)$this->options->displayFormat);
				
				echo '            <option value="'.$value.'">'.$display."</option>\n";
			}
		}
		?>
		</select>
		</td>
		<td><input type="button" class="kcmdc_removeitem" value="<?php echo gettext('Remove')?>" /></td>
		</tr>
		<tr>
		<td><input type="button" class="kcmdc_moveitemup" value="<?php echo gettext('Up')?>" /></td>
		</tr>
		<tr>
		<td><input type="button" class="kcmdc_moveitemdown" value="<?php echo gettext('Down')?>" /></td>
		</tr>
		</table>

		<?php
		$this->EndDisplay();
	}
	
	/**
	  * Print out the XML value of the MDC
	  *
	  * @return void
	  */
	public function displayXML()
	{
		if(!$this->isOK()) return;
		
		$xmlString = '<multidate>';
		
		foreach($this->value->date as $date)
		{
			$xmlString .= '<date>';
			$xmlString .= '<month>'. (string) $date->month .'</month>';
			$xmlString .= '<day>'. (string) $date->day .'</day>';
			$xmlString .= '<year>'. (string) $date->year .'</year>';
			$xmlString .= '<era>'. (string) $date->era .'</era>';
			$xmlString .= '</date>';
		}
		
		$xmlString .= '</multidate>';
		
		return $xmlString;
	}
	
	public function getType()
	{
		return 'Date (Multi-Input)';
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
			$dates = array();
			
			foreach($submitData[$this->cName] as $date) {
				$dateXML = simplexml_load_string($date);
				
				$str = "'%<date><month>";
				$str .= !empty($dateXML->month) ? $dateXML->month : "%";
				$str .= "</month><day>";
				$str .= !empty($dateXML->day) ? $dateXML->day : "%";
				$str .= "</day><year>";
				$str .= !empty($dateXML->year) ? $dateXML->year : "%";
				$str .= "</year><era>";
				$str .= !empty($dateXML->era) ? $dateXML->era : "%";
				$str .= "</era></date>%'";
				
				$dates[] = array('LIKE',$str);
			}
			
			return $dates;
		}
		else return false;
	}
	
	/**
	  * Set the value of the XML imput
	  *
	  * @param string $value Value to set
	  *
	  * @return void
	  */
	public function setXMLInputValue($value) {
		$dateParse = array();
		foreach ($value as $date) {
			$dateData = explode(" ",$date);
			list($month,$day,$year) = explode("/",$dateData[0]);
			
			$tmpDateStr = "<date><month>".$month."</month><day>".$day."</day><year>".$year."</year>";
			if (isset($dateData[1])) { $tmpDateStr .= "<era>".$dateData[1]."</era></date>"; }
			else { $tmpDateStr .= "<era>CE</era></date>"; }
			
			$dateParse[] = $tmpDateStr;
		}
		
		$this->XMLInputValue = $dateParse;
	}
	
	/**
	  * Add values from array to the MDC object
	  *
	  * @param Array[string] $valueArray Values to load
	  *
	  * @return void
	  */
	private function loadValues($valueArray) {
		$xmlString = '<multidate>';
		foreach($valueArray as $selectedOption)
		{
			$xmlString .= $selectedOption;
		}
		$xmlString .= '</multidate>';
		$this->value = simplexml_load_string($xmlString);
		
		
		// As an absolute fallback, if that fails (it shouldn't possibly if ingestion
		// calls validateIngestion before calling this), check for that and set it to
		// empty as a fallback
		if ($this->value === FALSE)
		{
			$this->value === simplexml_load_string('<multidate></multidate>');
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
		} else if (!empty($_REQUEST) && isset($_REQUEST[$this->cName])) {
			$this->loadValues($_REQUEST[$this->cName]);
		} else {
			$this->value = simplexml_load_string('<multidate></multidate>');
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
	
	/**
	  * Does this control have data in it?
	  *
	  * @return true on success
	  */
	public function isEmpty()
	{
		return !(!empty($_REQUEST[$this->cName]) || isset($this->XMLInputValue));
	}
	
	/**
	  * Get the data from the control for display
	  *
	  * @return control data
	  */
	public function showData()
	{
		if (isset($this->value->date))
		{
			$returnString = '';
			foreach($this->value->date as $date)
			{
				$returnString .= DateControl::formatDateForDisplay((int)$date->month, (int)$date->day, (int)$date->year, (string)$date->era, ($this->options->era == 'Yes'), (string)$this->options->displayFormat);
				$returnString .= '<br />';
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
		
		if (isset($xml->date))
		{
			foreach($xml->date as $date)
			{
				// Get the options for the control
				global $db;
				$pid = (int) $pid;
				if ($pid < 1) return gettext('Invalid PID');
				$cid = (int) $cid;
				if ($cid < 1) return gettext('Invalid Control ID');
				$optionQuery = $db->query('SELECT options FROM p'.$pid.'Control WHERE cid='.$cid.' LIMIT 1');
				if ($optionQuery->num_rows < 1)
				{
					return gettext('Invalid PID/CID');
				}
				$optionQuery = $optionQuery->fetch_assoc();
				$options = simplexml_load_string($optionQuery['options']);
				
				$returnVal .= DateControl::formatDateForDisplay((int)$date->month, (int)$date->day, (int)$date->year, (string)$date->era, ((string) $options->era == 'Yes'), (string)$options->displayFormat);
				$returnVal .= '<br />';
			}
		}
		
		return $returnVal;
	}
	
	/**
	  * Gathers values from XML
	  *
	  * @param string $xml XML object to get data from
	  *
	  * @return XML object
	  */
	public function storedValueToSearchResult($xml)
	{
		$xml = simplexml_load_string($xml);
		
		$returnVal = array();
		
		if (isset($xml->date))
		{
			foreach($xml->date as $date)
			{
				$currentDate = array();
				if (isset($date->month)) $currentDate['month'] = (string) $date->month;
				if (isset($date->day)) $currentDate['day'] = (string) $date->day;
				if (isset($date->year)) $currentDate['year'] = (string) $date->year;
				if (isset($date->era)) $currentDate['era'] = (string) $date->era;
				
				$returnVal[] = $currentDate;
			}
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
		
		$returnVal = '';
		if (!empty($_REQUEST[$this->cName] )){
			$dates = explode(',',$_REQUEST[$this->cName][0]);
		}else if (!empty($this->XMLInputValue)){
			$dates = $this->XMLInputValue;
		}else return '';
		
        	
		$startYear = (int) $this->options->startYear;
		$endYear = (int) $this->options->endYear;
		
		
		foreach($dates as $date){
			// Suppress the Error but catch it on the line below in case the XML is bad
			@$xml = simplexml_load_string($date);
			if ($xml === FALSE){
				$returnVal = gettext('Value supplied for field').': '.htmlEscape($this->name).' '.gettext('is not valid XML').'.';
			}else{
				if (isset($xml->month) && isset($xml->day) && isset($xml->year) && isset($xml->era)){
					$month = (string) $xml->month;
					$day =   (string) $xml->day;
					$year =  (string) $xml->year;
					$era =   (string) $xml->era;
					
					if (!empty($era) && !in_array($era, array('CE', 'BCE')))
					{
						$returnVal = gettext('Field').' '.htmlEscape($this->name).': '.gettext('Era must be CE or BCE');
					}
					else if (!empty($month) && ((int)$month < 1 || (int)$month > 12))
					{
						$returnVal = gettext('Field').' '.htmlEscape($this->name).': '.gettext('Invalid Month');
					}
					else if (!empty($year) && ((int)$year < $startYear || (int)$year > $endYear))
					{
						$returnVal = gettext('Field').' '.htmlEscape($this->name).': '.gettext('Year outside of valid range');
					}
					else
					{
						$returnVal = $this->validDate((int)$day, (int)$month, (int)$year);
					}
				}
				else
				{
					$returnVal = gettext('Value supplied for field').': '.htmlEscape($this->name).' '.gettext('does not have all required XML members').'.';
				}
			}
		}
		
		return $returnVal;
		
	}
	
	/**
	  * Add date values to an XML object
	  *
	  * @param string $simplexml XML object to add to
	  *
	  * @return xml object
	  */
	public function ExportToSimpleXML(&$simplexml) 
	{
		foreach ($this->value as $v)
		{
			$dateString = "$v->month/$v->day/$v->year";
			if (isset($v['era'])) { $dateString.= " $v->era"; }
			$node = $simplexml->addChild(str_replace(' ', '_', $this->GetName()), xmlEscape($dateString));
			
			if (isset($v['prefix'])) { $node->addAttribute('prefix', xmlEscape($v->prefix)); }
			if (isset($v['suffix'])) { $node->addAttribute('suffix', xmlEscape($v->suffix)); }
		}

		return $simplexml;					
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
		$this->OptPrintDateRange();
		$this->OptPrintDateFormat();
		$this->OptPrintShowEra();
		print "</div>";
	}
	
	// TODO:  KILL ALL OF THESE UGLY TABLES
	
	/**
	  * Print table for default values
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
		<td width="60%" class="kcopt_label"><b><?php echo gettext('Default Value')?></b><br /><?php echo gettext('Please select an optional value which will be initially filled in during ingestion')?>.</td>
		<td>
		<div class="kora_control">
		<table>
		<tr>
		<td>
		<?php
		$month = $day = $year = '';
		if (isset($xml->defaultValue->month)) $month = (string) $xml->defaultValue->month;
		if (isset($xml->defaultValue->day))   $day   = (string) $xml->defaultValue->day;
		if (isset($xml->defaultValue->year))  $year  = (string) $xml->defaultValue->year;
		
		// Use output buffering to get the three fields and then display
		// them in the proper order
		
		// Month
		ob_start();
		echo '<select name="month" class="kcmdcopts_addmonth">';
		echo '<option value=""';
		if (empty($month)) echo ' selected="selected"';
		echo '>&nbsp;</option>';
		
		for ($i=1; $i <= 12; $i++)
		{
			echo "<option value=\"$i\"";
			if ($i == $month) echo ' selected="selected"';
			echo '>'.gettext(DateControl::$months[$i]['name']).'</option>';
		}
		echo '</select>';
		$monthDisplay = ob_get_clean();
		
		// Day
		ob_start();
		echo '<select name="day" class="kcmdcopts_addday">';
		echo '<option value=""';
		if (empty($day)) echo ' selected="selected"';
		echo '>&nbsp;</option>';
		for ($i=1; $i <= 31; $i++)
		{
			echo "<option value=\"$i\"";
			if ($i == $day) echo ' selected="selected"';
			echo ">$i</option>";
		}
		echo '</select>';
		$dayDisplay = ob_get_clean();
		
		// Year
		ob_start();
		echo '<select name="year" class="kcmdcopts_addyear">';
		echo '<option value=""';
		if (empty($year)) echo ' selected="selected"';
		echo '>&nbsp;</option>';
		
		$startYear = (int) $xml->startYear;
		$endYear = (int) $xml->endYear;
		
		// Make sure we don't hit an infinite loop - if end < start, switch them
		if ($endYear < $startYear)
		{
			$temp = $endYear;
			$endYear = $startYear;
			$startYear = $temp;
			unset($temp);
		}
		
		for ($i=$startYear; $i <= $endYear; $i++)
		{
			echo "<option value=\"$i\"";
			if ($i == $year) echo ' selected="selected"';
			echo ">$i</option>";
		}
		echo '</select>';
		$yearDisplay = ob_get_clean();
		
		// CE/BCE?
		ob_start();
		if ((string) $xml->era == 'Yes')
		{
			$era = '';
			if (isset($xml->defaultValue->era)) $era = (string) $xml->defaultValue->era;
			
			echo '<select name="era" class="kcmdcopts_addera">';
			
			$eras = array('','CE','BCE');
			foreach($eras as $e)
			{
				echo "<option value=\"$e\"";
				if ($e == $era) echo ' selected="selected"';
				echo ">$e</option>";
			}
			echo '</select>';
		}
		else
		{
			echo '<input type="hidden" name="era" class="kcmdcopts_addera" value="CE" />';
		}
		$eraDisplay = ob_get_clean();
		
		// Display stuff in the proper order
		if ($xml->displayFormat == 'MDY')
		{
			echo $monthDisplay.'&nbsp;'.$dayDisplay.'&nbsp;'.$yearDisplay.'&nbsp;'.$eraDisplay;
		}
		else if ($xml->displayFormat == 'DMY')
		{
			echo $dayDisplay.' '.$monthDisplay.'&nbsp;'.$yearDisplay.'&nbsp;'.$eraDisplay;
		}
		else if ($xml->displayFormat == 'YMD')
		{
			echo $yearDisplay.' '.$monthDisplay.'&nbsp;'.$dayDisplay.'&nbsp;'.$eraDisplay;
		}
		else
		{
			echo gettext("This control's display format is an unrecognized value; please check its options.");
		}
		?>
		</td>
		<td><input type="button" value="<?php echo gettext('Add')?>" class='kcmdcopts_defadd' /></td>
		</tr>
		<tr><td rowspan='3'>
		<select class='kcmdcopts_defval fullsizemultitext' name="defaultValue" size="5">
		<?php
		// Show any existing Default Values
		if (isset($xml->defaultValue->date))
		{
			foreach($xml->defaultValue->date as $date)
			{
				$display = DateControl::formatDateForDisplay((int)$date->month, (int)$date->day, (int)$date->year, (string)$date->era, ((string)$xml->era == 'Yes'),(string)$xml->displayFormat);
				echo "<option value=\"".$date->asXML()."\">$display</option>";
			}
		}
		?>
		</select>
		</td>
		<td><input type="button" value="<?php echo gettext('Remove')?>" class='kcmdcopts_defremove' /></td>
		</tr>
		<tr>
		<td><input type="button" value="<?php echo gettext('Up')?>" class='kcmdcopts_defmoveup' /></td>
		</tr>
		<tr>
		<td><input type="button" value="<?php echo gettext('Down')?>" class='kcmdcopts_defmovedown' /></td>
		</tr>
		</table>
		</div>
		</td>
		</tr>
		</table>
	<?php }
	
	/**
	  * Save a collection of dates as the default value
	  *
	  * @param string $dates Dates to save
	  *
	  * @return void
	  */
	public function SaveDefaultValue($dates)
	{
		$defvals = '';
		foreach ($dates as $date)
		{
			//var_dump($date);
			$datexml = simplexml_load_string($date);
			if (!$datexml) { continue; }
			
			// TODO: FURTHER VALIDATAION HERE? PROBABLY CAN STREAMLINE WITH BASE CLASS SOMEHOW?
			
			$defvals .= '<date>';
			if ((string)$datexml->year != '')   { $defvals .= "<year>".xmlEscape((string)$datexml->year)."</year>"; }
			if ((string)$datexml->month != '')  { $defvals .= "<month>".xmlEscape((string)$datexml->month)."</month>"; }
			if ((string)$datexml->day != '')    { $defvals .= "<day>".xmlEscape((string)$datexml->day)."</day>"; }
			if ((string)$datexml->era != '')    { $defvals .= "<era>".xmlEscape((string)$datexml->era)."</era>"; }
			$defvals .= '</date>';
		}
		
		$this->SetExtendedOption('defaultValue', $defvals);
	}
}

?>
