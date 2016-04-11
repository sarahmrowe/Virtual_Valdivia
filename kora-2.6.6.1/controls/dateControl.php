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

require_once(__DIR__.'/../includes/includes.php');
Manager::AddJS('controls/dateControl.js', Manager::JS_CLASS); 

/**
 * @class DateControl object
 *
 * This class respresents a DateControl in KORA
 */
class DateControl extends Control
{
	protected $name = "Date Control";
	
	public static $months = array(
		0  => array('name' => '',		  'days' =>  0),
		1  => array('name' => 'January',   'days' => 31),
		2  => array('name' => 'February',  'days' => 28),
		3  => array('name' => 'March',	 'days' => 31),
		4  => array('name' => 'April',	 'days' => 30),
		5  => array('name' => 'May',	   'days' => 31),
		6  => array('name' => 'June',	  'days' => 30),
		7  => array('name' => 'July',	  'days' => 31),
		8  => array('name' => 'August',	'days' => 31),
		9  => array('name' => 'September', 'days' => 30),
		10 => array('name' => 'October',   'days' => 31),
		11 => array('name' => 'November',  'days' => 30),
		12 => array('name' => 'December',  'days' => 31)
	);
	
	/**
	  * Standard constructor for a control. See Control::Construct for details.
	  *
	  * @return void
	  */
	public function DateControl($projectid='', $controlid='', $recordid='', $inPublicTable = false)
	{
		if (empty($projectid) || empty($controlid)) return;
		global $db;

		$this->Construct($projectid,$controlid,$recordid,$inPublicTable);
			
		// If data exists for this control, get it
		if (!empty($this->rid))
		{
			$this->LoadValue();

			// for reverse compatibility
			if ($this->HasData())
			{
				if(!isset($this->value->prefix)) $this->value->addChild('prefix','');
				if(!isset($this->value->suffix)) $this->value->addChild('suffix','');
			}
		}
		else if (!empty($presetid))
		{
			$valueCheck = $db->query('SELECT value FROM p'.$projectid.'Data WHERE id='.escape($presetid).' AND cid='.escape($controlid).' LIMIT 1');
			if ($valueCheck->num_rows > 0)
			{
				$this->existingData = true;
				$valueCheck = $valueCheck->fetch_assoc();
				$this->value = simplexml_load_string($valueCheck['value']);
			}
		}
		else if (isset($this->options->defaultValue) && ( !empty($this->options->defaultValue->month) ||
		 		 !empty($this->options->defaultValue->day) || !empty($this->options->defaultValue->year) ))
		{
			$this->value = $this->options->defaultValue;
		}
	}
	
	/**
	  * Delete this control from it's project
	  *
	  * @return void
	  */
	public function delete() {
		global $db;
		
		if (!$this->isOK()) return;
		
		if (!empty($this->rid)) $deleteCall = $db->query('DELETE FROM p'.$this->pid.'Data WHERE id='.escape($this->rid).' AND cid='.escape($this->cid).' LIMIT 1');
		else {
			$deleteCall = $db->query('DELETE FROM p'.$this->pid.'Data WHERE cid='.escape($this->cid));
			$publicDeleteCall = $db->query('DELETE FROM p'.$this->pid.'PublicData WHERE cid='.escape($this->cid));
		}
		
		//function must be present in all delete extentions of base class, Control
		$this->deleteEmptyRecords();
	}

	/**
	  * Prints control view for public ingestion
	  *
	  * @return void
	  */
	public function display($defaultValue=true)
	{
	
		$hasDef = false;
		if($this->value != null){
			$hasDef = true;
		}
		
		if (!$this->StartDisplay($hasDef)) { return false; }
		
		$this->PrintDateSelectDivs($defaultValue);

		$this->EndDisplay();
	}
	
	//TODO: AUTOFILL NEEDS TO BE REDONE
	/*public function displayAutoFill($category) {
		$dateOptions = array('from','to');
	
		for($j=0 ; $j<sizeof($dateOptions) ; ++$j) {
			ob_start();
			echo '<select class="af_'.$category.$j.'" name="af_'.$category.$j.'_month" id="af_'.$category.$j.'_month">';
			for ($i=1; $i <= 12; $i++)
			{
				echo "<option value=\"$i\">".gettext(DateControl::$months[$i]['name']).'</option>';
			}
			echo '</select>';
			$monthDisplay = ob_get_clean();
	
			// Day
			ob_start();
			echo '<select class="af_'.$category.$j.'" name="af_'.$category.$j.'_day" id="af_'.$category.$j.'_day">';
			for ($i=1; $i <= 31; $i++)
			{
				echo "<option value=\"$i\">$i</option>";
			}
			echo '</select>';
			$dayDisplay = ob_get_clean();
			
			// Year
			ob_start();
			echo '<select class="af_'.$category.$j.'" name="af_'.$category.$j.'_year" id="af_'.$category.$j.'_year">';
			$startYear = (int) $this->options->startYear;
			$endYear = (int) $this->options->endYear;
			
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
				echo "<option value=\"$i\">$i</option>";
			}
			echo '</select>';
			$yearDisplay = ob_get_clean();
	
			// CE/BCE?
			ob_start();
			if ((string) $this->options->era == 'Yes')
			{
				$era = '';
				if (isset($this->value->era)) $era = (string) $this->value->era;
				
				echo '<select class="af_'.$category.$j.'" name="af_'.$category.$j.'_era" id="af_'.$category.$j.'_era">';
				
				$eras = $this->getEraData();
				foreach($eras as $e)
				{
					if($e) {
						echo "<option value=\"$e\">$e</option>";
					}
				}
				echo '</select>';
			}
			else
			{
				echo '<input type="hidden" class="af_'.$category.$j.'" name="af_'.$category.$j.'_era" id="af_'.$category.$j.'_era" value="CE" />';
			}
			$eraDisplay = ob_get_clean();
			
			// Display stuff in the proper order
			if ($this->options->displayFormat == 'MDY')
			{
				echo $monthDisplay.$dayDisplay.$yearDisplay.$eraDisplay;
			}
			else if ($this->options->displayFormat == 'DMY')
			{
				echo $dayDisplay.$monthDisplay.$yearDisplay.$eraDisplay;
			}
			else if ($this->options->displayFormat == 'YMD')
			{
				echo $yearDisplay.$monthDisplay.$dayDisplay.$eraDisplay;
			}
			else
			{
				echo gettext("This control's display format is an unrecognized value; please check its options.");
			}
			echo '</div>';

			if ($j == 0) {
				echo '<div style="text-align: center;"> to </div>';
			}
		}
		echo '<input type="hidden" id="af_'.$category.'_op" value="between"/>';
	}*/
	
	/**
	  * Print out the XML value of the DC
	  *
	  * @return void
	  */
	public function displayXML()
	{
		if(!$this->isOK()) return '';
		
		$xmlString = '<date>';
		$xmlString .= '<month>'.(int)$this->value->month.'</month>';
		$xmlString .= '<day>'.(int)$this->value->day.'</day>';
		$xmlString .= '<year>'.(int)$this->value->year.'</year>';
		$xmlString .= '<era>'.(int)$this->value->era.'</era>';
		$xmlString .= '<prefix>'.(int)$this->value->prefix.'</prefix>';
		$xmlString .= '<suffix>'.(int)$this->value->suffix.'</suffix>';
		$xmlString .= '</date>';

		return $xmlString;
	}

	/**
	  * Format a DC for display
	  *
	  * @param int $month Month
	  * @param int $day Day
	  * @param int $year Year
	  * @param string $era Era
	  * @param bool $showEra Do we shoe the era in display
	  * @param string $format Format of the date
	  * @param string $prefix Prefix for date
	  * @param string $suffix Suffix for date
	  *
	  * @return void
	  */
	public static function formatDateForDisplay($month, $day, $year, $era, $showEra, $format, $prefix='', $suffix='')
	{
		$month = (int)$month;
		$day = (int)$day;
		$year = (string)$year;
		
		
		$returnVal = ($prefix == '') ? '':$prefix.' ';
		
		if ($format == 'MDY')
		{
			if ($month > 0)
			{
				$returnVal .= gettext(DateControl::$months[$month]['name']) . ' ';
			}
			if ($day > 0)
			{
				$returnVal .= (string) $day;
				if ($year > 0)
				{
					$returnVal .= ', ';
				}
			}
			if ($year > 0)
			{
				$returnVal .= (string) $year;
			}
		}
		else if ($format == 'DMY')
		{
			if ($day > 0)
			{
				$returnVal .= (string) $day.' ';
			}
			if ($month > 0)
			{
				$returnVal .= gettext(DateControl::$months[$month]['name']) . ' ';
			}
			if ($year > 0)
			{
				$returnVal .= (string) $year;
			}
		}
		else if ($format == 'YMD')
		{
			if ($year > 0)
			{
				$returnVal .= (string) $year.' ';
			}
			if ($month > 0)
			{
				$returnVal .= gettext(DateControl::$months[$month]['name']) . ' ';
			}
			if ($day > 0)
			{
				$returnVal .= (string) $day;
			}
		}
		else
		{
			$returnVal = gettext('This control has no format option set; please check its options.');
		}
		
		$returnVal .= $suffix;
		
		if ($showEra)
		{
			$returnVal .= ' '.(string) $era;
		}
		
		return $returnVal;
	}
	
	/**
	  * Return string to enter into a Kora_Clause
	  *
	  * @param string $submitData Submited data for control
	  *
	  * @return Search string on success
	  */
	public function getSearchString($submitData) {
		if(	   isset($submitData[$this->cName."month"]) && !empty($submitData[$this->cName."month"])
			|| isset($submitData[$this->cName."day"])   && !empty($submitData[$this->cName."day"])
			|| isset($submitData[$this->cName."year"])  && !empty($submitData[$this->cName."year"])
			|| isset($submitData[$this->cName."era"])   && !empty($submitData[$this->cName."era"])) {
			
			$str = "'%<month>";
			$str .= !empty($submitData[$this->cName."month"]) ? $submitData[$this->cName."month"] : "%";
			$str .= "</month><day>";
			$str .= !empty($submitData[$this->cName."day"]) ? $submitData[$this->cName."day"] : "%";
			$str .= "</day><year>";
			$str .= !empty($submitData[$this->cName."year"]) ? $submitData[$this->cName."year"] : "%";
			$str .= "</year><era>";
			$str .= !empty($submitData[$this->cName."era"]) ? $submitData[$this->cName."era"] : "%";
			$str .= "</era>%'";
			
			return array(array("LIKE",$str));
		}
		else return false;
	}
	
	public function getType()
	{
		return "Date";
	}
   
	/**
	  * Set the value of the XML imput
	  *
	  * @param string $value Value to set
	  *
	  * @return void
	  */
	public function setXMLInputValue($value) {
		$dateData = explode(" ",$value[0]);
		list($month,$day,$year) = explode("/",$dateData[0]);
		
		$this->XMLInputValue = array();
		$this->XMLInputValue[$this->cName.'month'] = $month;
		$this->XMLInputValue[$this->cName.'day'] = $day;
		$this->XMLInputValue[$this->cName.'year'] = $year;
		$this->XMLInputValue[$this->cName.'era'] = isset($dateData[1]) ? $dateData[1] : '';

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
		
		$this->value = simplexml_load_string('<date><month /><day /><year /><era /><prefix /><suffix /></date>');
		if (empty($this->rid))
		{
			echo '<div class="error">'.gettext('No Record ID Specified').'.</div>';
			return;
		} else if (isset($this->XMLInputValue)) {
			$this->SetXMLFromArray($this->XMLInputValue);
		} else if (!empty($_REQUEST)) {
			$this->SetXMLFromArray($_REQUEST);
		} else {
			$this->value = '';
		}
			  
		// ingest the data
		$query = '';	// default blank query
		if ($this->existingData)
		{
			if ($this->isEmpty()) $query = 'DELETE FROM p'.$this->pid.$tableName.' WHERE id='.escape($this->rid).
										   ' AND cid='.escape($this->cid).' LIMIT 1';
			else $query =   'UPDATE p'.$this->pid.$tableName.' SET value='.escape($this->value->asXML()).
							' WHERE id='.escape($this->rid).' AND cid='.escape($this->cid).' LIMIT 1';
		}
		else
		{
			if (!$this->isEmpty()) $query = 'INSERT INTO p'.$this->pid.$tableName.' (id, cid, schemeid, value) VALUES ('.escape($this->rid).', '.escape($this->cid).', '.escape($this->sid).', '.escape($this->value->asXML()).')';
		}
		
		if (!empty($query)) $db->query($query);
	}
	
	/**
	  * Initialize function for control options
	  *
	  * @return void
	  */
	public static function initialOptions()
	{
		return '<options><startYear>1970</startYear><endYear>2070</endYear><era>No</era><displayFormat>MDY</displayFormat><defaultValue><day /><month /><year /><era /></defaultValue></options>';
	}
	
	/**
	  * Does this control have data in it?
	  *
	  * @return true on success
	  */
	public function isEmpty()
	{
		# RE-WRITTEN THIS WAY TO AVOID PHP WARNINGS
		if (isset($this->XMLInputValue)) { return false; }
		else
		{ return !( !(empty($_REQUEST[$this->cName.'month']) && empty($_REQUEST[$this->cName.'day']) && empty($_REQUEST[$this->cName.'year']) )); }
	}
	
	public function isXMLPacked() { return true; }
	
	
	/**
	  * Get the data from the control for display
	  *
	  * @return control data
	  */
	public function showData()
	{
		if (!empty($this->rid) && is_object($this->value))
		{
			return DateControl::formatDateForDisplay((int)$this->value->month, (int)$this->value->day, (int)$this->value->year, (string)$this->value->era, ($this->options->era == 'Yes'), (string) $this->options->displayFormat,$this->value->prefix,$this->value->suffix);
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
		
	
		$prefix = isset($xml->prefix) ? (string)$xml->prefix:'';
		$suffix = isset($xml->suffix) ? (string)$xml->suffix:'';
		
		return DateControl::formatDateForDisplay((int)$xml->month, (int)$xml->day, (int)$xml->year, (string)$xml->era, ($options->era == 'Yes'), (string) $options->displayFormat, $prefix, $suffix);
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
		if (isset($xml->month)) $returnVal['month'] = (string) $xml->month;
		if (isset($xml->day)) $returnVal['day'] = (string) $xml->day;
		if (isset($xml->year)) $returnVal['year'] = (string) $xml->year;
		if (isset($xml->era)) $returnVal['era'] = (string) $xml->era;
		if (isset($xml->prefix)) $returnVal['prefix'] = (string) $xml->prefix;
		if (isset($xml->suffix)) $returnVal['suffix'] = (string) $xml->suffix;
		
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
		if ($this->required && $this->isEmpty())
		{
			return gettext('No value supplied for required field').': '.htmlEscape($this->name);
		}
		else if ($this->isEmpty())
		{
			return '';
		}
		
		if (!empty($this->XMLInputValue)) {
			$dateArray = $this->XMLInputValue;
		} else if(!empty($_REQUEST[$this->cName] )){
			$dateArray = $_REQUEST[$this->cName];
		}else return '';
		
	
		$day = (int) $dateArray[$this->cName.'day'];
		$month = (int) $dateArray[$this->cName.'month'];
		$year = (int) $dateArray[$this->cName.'year'];
		$era = $dateArray[$this->cName.'era'];
		
		$startYear = (int) $this->options->startYear;
		$endYear = (int) $this->options->endYear;
		
		// make sure that all of the ranges fit within their specified values
		if (($month < 0) || ($month > 12)) {
			return gettext('Invalid Month specified for field').': '.htmlEscape($this->name);
		} else if ( ( $year < $startYear  ||  $year > $endYear ) && (string)$this->options->yearInputStyle != 'text') {
			return gettext('Invalid Year specified for field').': '.htmlEscape($this->name);
		} else if ( (string) $this->options->era == 'Yes' && !empty($era) && !in_array( (string) $era, $this->getEraData()) ) {
			return '"'.gettext(htmlEscape($era).'" is not a valid option for an era.');
		} else return $this->validDate($day, $month, $year);
	}
	
	/**
	  * Validate if the date is possible
	  *
	  * @param int $day Day
	  * @param int $month Month
	  * @param int $year Year
	  *
	  * @return void
	  */
	public function validDate($day, $month, $year)
	{
		if ( $day > DateControl::$months[$month]['days'] )
		{
			if (($month == 2) && ($day == 29))
			{
				// Ooh boy, a leap year!
				if (($year % 4) || (!($year % 100) && ($year % 400)))
				{
					return "$year ".gettext('is not a leap year').".<br />";
				}
				else return '';   // Wow, it really IS a leap year
			}
			else
			{
				return gettext('There are only ').DateControl::$months[$month]['days'].gettext(' days in ').gettext(DateControl::$months[$month]['name']).".<br />";
			}
		}
		else return '';
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
		
		$dateString = $this->value->month.'/'.$this->value->day.'/'.$this->value->year;
		if (isset($this->value['era'])) { $dateString.= ' '.$this->value->era; }
		$node = $simplexml->addChild(str_replace(' ', '_', $this->GetName()), xmlEscape($dateString));
		
		if (isset($this->value->prefix) && $this->value->prefix!="") { $node->addAttribute('prefix', xmlEscape($this->value->prefix)); }
		if (isset($this->value->suffix) && $this->value->suffix!="") { $node->addAttribute('suffix', xmlEscape($this->value->suffix)); }

		return $simplexml;					
	}
	
	/**
	  * Set date value for DC using an array
	  *
	  * @param string $dateArray Array of date imformation
	  *
	  * @return void
	  */
	private function SetXMLFromArray($dateArray) {
		$this->value->month = (isset($dateArray[$this->cName.'month']) ? $dateArray[$this->cName.'month'] : '');
		$this->value->day   = (isset($dateArray[$this->cName.'day'])   ? $dateArray[$this->cName.'day']   : '');
		$this->value->year  = (isset($dateArray[$this->cName.'year'])  ? $dateArray[$this->cName.'year']  : '');
		$this->value->era   = (isset($dateArray[$this->cName.'era'])   ? $dateArray[$this->cName.'era']   : '');
		$this->value->prefix= (isset($dateArray[$this->cName.'prefix'])? $dateArray[$this->cName.'prefix']   : '');
		$this->value->suffix= (isset($dateArray[$this->cName.'suffix'])? $dateArray[$this->cName.'suffix']   : '');
		
		// See if fields were left blank
		if ((string)$this->value->month === '') $this->value->month = '';
		if ((string)$this->value->day === '') $this->value->day = '';
		if ((string)$this->value->year === '') $this->value->year = '';
		else $this->value->year = (int)(preg_replace('/[^\d]/','',$this->value->year));
	}
	
	/**
	  * Return the era of the DC if data is set
	  *
	  * @return true on success
	  */
	private function getEraData() {
		return ($this->existingData ? array('CE', 'BCE') : array('','CE','BCE'));
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
		$this->OptPrintPrefixes();
		$this->OptPrintSuffixes();
		print "</div>";
	}
	
	// TODO:  KILL ALL OF THESE UGLY TABLES
	/**
	  * Print out table for date range
	  *
	  * @return void
	  */
	protected function OptPrintDateRange()
	{ 
		$xml = $this->GetControlOptions();
		if(!$xml) return;
		?>
		<table class="table kcopts_style">
		<tr>
		<td width="60%" class="kcopt_label"><b><?php echo gettext('Date Range')?></b><br /><?php echo gettext('Please provide a minimum and maximum value for the year in the range 0-9999')?>.</td>
		<td><table border="0">
		<tr>
		<td><?php echo gettext('Start Date')?></td>
		<td><input type="text" name="startDate" class='kcdcopts_rangestart' value="<?php echo (string)$xml->startYear?>" /></td>
		</tr>
		<tr>
		<td><?php echo gettext('End Date')?></td>
		<td><input type="text" name="endDate" class='kcdcopts_rangeend' value="<?php echo (string)$xml->endYear?>" /></td>
		</tr>
		</table></td>
		</tr>
		</table>
	<?php }

	/**
	  * Print out table for date format
	  *
	  * @return void
	  */
	protected function OptPrintDateFormat()
	{ 
		$xml = $this->GetControlOptions();
		if(!$xml) return;
		?>
		<table class="table kcopts_style">
		<tr>
		<td width="60%" class="kcopt_label"><b><?php echo gettext('Date Format')?></b><br /><?php echo gettext('Please select the format you would prefer to enter your dates in.  This format will also be used for displaying the dates inside of KORA (this setting has no effect on front-end sites).')?></td>
		<td><input type="radio" name="format" class='kcdcopts_format' value="MDY" <?php  if ( (string)$xml->displayFormat == 'MDY' ) echo 'checked'; ?> /><?php echo gettext('MM DD, YYYY')?><br />
		<input type="radio" name="format" class='kcdcopts_format' value="DMY" <?php  if ( (string)$xml->displayFormat == 'DMY' ) echo 'checked'; ?> /><?php echo gettext('DD MM YYYY')?><br />
		<input type="radio" name="format" class='kcdcopts_format' value="YMD" <?php  if ( (string)$xml->displayFormat == 'YMD' ) echo 'checked'; ?> /><?php echo gettext('YYYY MM DD')?><br />
		</td>
		</tr>
		</table>
	<?php }

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
		<td width="60%" class="kcopt_label"><b><?php echo gettext('Default Value')?></b><br /><?php echo gettext('Please select an optional value which will be initially filled in during ingestion')?>.</td>
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
		echo '<select name="month" class="kcdcopts_defmonth">';
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
		echo '<select name="day" class="kcdcopts_defday" >';
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
		echo '<select name="year" class="kcdcopts_defyear" >';
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
			
			echo '<select name="era" class="kcdcopts_defera" >';
			
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
			echo '<input type="hidden" name="era" class="kcdcopts_defera" value="CE" />';
		}
		$eraDisplay = ob_get_clean();
		
		// Display stuff in the proper order
		if ($xml->displayFormat == 'MDY')
		{
			echo $monthDisplay.$dayDisplay.$yearDisplay.$eraDisplay;
			echo ' <input type="button" value="'.gettext('Update').'" class="kcdcopts_updatedefault" />';
		}
		else if ($xml->displayFormat == 'DMY')
		{
			echo $dayDisplay.$monthDisplay.$yearDisplay.$eraDisplay;
			echo ' <input type="button" value="'.gettext('Update').'" class="kcdcopts_updatedefault" />';
		}
		else if ($xml->displayFormat == 'YMD')
		{
			echo $yearDisplay.$monthDisplay.$dayDisplay.$eraDisplay;
			echo ' <input type="button" value="'.gettext('Update').'" class="kcdcopts_updatedefault" />';
		}
		else
		{
			echo gettext("This control's display format is an unrecognized value; please check its options.");
		}
		?>
		</td>
		</tr>
		</table>
	<?php }

	/**
	  * Print out table for showing era
	  *
	  * @return void
	  */
	protected function OptPrintShowEra()
	{ 
		$xml = $this->GetControlOptions();
		if(!$xml) return;
		?>
		<table class="table kcopts_style">
		<tr>
		<td width="60%" class="kcopt_label"><b><?php echo gettext('Show Era Field?')?></b><br /><?php echo gettext('Choose whether or not to show the CE/BCE selector; if you choose not to show it, the control will default to assuming CE.')?></td>
		<td><input type="radio" name="showEra" class='kcdcopts_showera' value="No" <?php  if ( (string)$xml->era == 'No' ) echo 'checked'; ?> /><?php echo gettext('No')?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" name="showEra" class='kcdcopts_showera' value="Yes" <?php  if ( (string)$xml->era == 'Yes' ) echo 'checked'; ?> /><?php echo gettext('Yes')?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		</td>
		</tr>
		</table>
	<?php }

	/**
	  * Print out table for prefixes
	  *
	  * @return void
	  */
	protected function OptPrintPrefixes()
	{ 
		$xml = $this->GetControlOptions();
		if(!$xml) return;
		?>
		<table class="table kcopts_style">
		<tr>
		<td width="60%" class="kcopt_label">
		<b><?php echo gettext('Prefixes');?></b><br/>
		<?php echo gettext('Choose prefixes that will be available during ingestion').' (i.e. \'circa\'). '.gettext('This option will not be displayed during ingestion if no values are set here.');?>
		</td>
		<td>
		<table><tr><td>
		<select size="5" name="prefixes" class="kcdcopts_prefixes" ><?php foreach($xml->prefixes as $prefix) echo '<option>'.(string)$prefix.'</option>';?></select><br/>
		<input type="button" value="Remove" class="kcdcopts_prefixesremove" />
		</td><td>
		<input type="text" class="kcdcopts_prefixesaddval" /><br/>
		<input type="button" value="Add" class="kcdcopts_prefixesadd" />
		</td></tr></table>
		</td>
		</tr>
		</table>
	<?php }

	/**
	  * Print out table for suffixes
	  *
	  * @return void
	  */
	protected function OptPrintSuffixes()
	{ 
		$xml = $this->GetControlOptions();
		if(!$xml) return;
		?>
		<table class="table kcopts_style">
		<tr>
		<td width="60%" class="kcopt_label">
		<b><?php echo gettext('Suffixes');?></b><br/>
		<?php echo gettext('Choose suffixes that will be available during ingestion').' (i.e. \'s\'). '.gettext('This option will not be displayed during ingestion if no values are set here.');?>
		</td>
		<td>
		<table><tr><td>
		<select size="5" class="kcdcopts_suffixes" name="suffixes"><?php foreach($xml->suffixes as $suffix) echo '<option>'.(string)$suffix.'</option>';?></select><br/>
		<input type="button" value="Remove" class="kcdcopts_suffixesremove" />
		</td><td>
		<input type="text"  class="kcdcopts_suffixesaddval" /><br/>
		<input type="button" value="Add"  class="kcdcopts_suffixesadd" />
		</td></tr></table>
		</td>
		</tr>
		</table>
	<?php }

	/**
	  * Update the range of allowed dates
	  *
	  * @param string $startDate Starting date
	  * @param string $endDate Ending date
	  *
	  * @return result string
	  */
	public function updateDateRange($startDate, $endDate)
	{
		// Casting to integer should remove injection attacks
		// and make sure our range checks work
		$startDate = (int)$startDate;
		$endDate = (int)$endDate;
		
		if ($startDate < 0 || $startDate > 9999)
		{
			echo gettext('Start Date must be in the range [1,9999]').'.';
		}
		else if ($endDate < 0 || $endDate > 9999)
		{
			echo gettext('End Date must be in the range [1,9999]').'.';
		}
		else if ($startDate > $endDate)
		{
			echo gettext('End Date must be greater than or equal to Start Date');
		}
		else
		{
			$xml = $this->GetControlOptions();
			if(!$xml) return;
		
			// Set the new options
			$xml->startYear = $startDate;
			$xml->endYear = $endDate;
			
			$this->SetControlOptions($xml);
			echo gettext('Date Range Settings Updated').'.';
		}
	}
	
	/**
	  * Update the era for DC
	  *
	  * @param string $era Era
	  *
	  * @return result string on success
	  */
	public function updateEra($era)
	{
		if (!in_array($era, array('Yes', 'No'))) return;
	
		$xml = $this->GetControlOptions();
		if(!$xml) return;
		
		// Set the new selection
		$xml->era = $era;
		
		$this->SetControlOptions($xml);
		echo gettext('Era Settings Updated').'.';
	}
	
	/**
	  * Update the date format for DC
	  *
	  * @param string $format Date format
	  *
	  * @return result string on success
	  */
	public function updateFormat($format)
	{
		if (!in_array($format, array('MDY', 'DMY', 'YMD'))) return;
		
		$xml = $this->GetControlOptions();
		if(!$xml) return;
		
		// Set the new selection
		$xml->displayFormat = $format;
		
		$this->SetControlOptions($xml);
		echo gettext('Date Format Settings Updated').'.';
	}
	
	/**
	  * Set the default value for the DC
	  *
	  * @param int $month 
	  * @param int $day 
	  * @param int $year 
	  * @param string $era 
	  *
	  * @return result string on success
	  */
	public function OptUpdateDefaultValue($month, $day, $year, $era)
	{
		$xml = $this->GetControlOptions();
		if(!$xml) return;
				
		// Validate Input
		
		// Era: Can only be CE, BCE, or blank
		if (!in_array($era, array('', 'CE', 'BCE')))
		{
			return;
		}
		
		// Year: Can only be blank or a number in the date range
		if (!empty($year))
		{
			$year = (int)$year;
			if ($year < (int)$xml->startYear)
			{
				$year = (int)$xml->startYear;
			}
			else if ($year > (int)$xml->endYear)
			{
				$year = (int)$xml->endYear;
			}
		} else $year = ''; // this shouldn't be necessary but it ensures consistency
		
		// Month: Can only be blank or a number in the range 1-12
		if (!empty($month))
		{
			$month = (int)$month;
			if ($month < 1)
			{
				$month = 1;
			}
			else if ($month > 12)
			{
				$month = 12;
			}
		} else $month = ''; // this shouldn't be necessary but it ensures consistency
		
		// Day: Can only be in the range valid for that month
		if (!empty($day))
		{ 
			$day = (int)$day;
		} else $day = ''; // this shouldn't be necessary but it ensures consistency
		
		$message = DateControl::validDate($day, $month, $year);
		if (!empty($message))
		{
			Manager::PrintErrDiv($message);
			return false;
		}
		
		// update the information
		$defval = '';
		if ($year != '')  { $defval .= "<year>$year</year>"; }
		if ($month != '') { $defval .= "<month>$month</month>"; }
		if ($day != '')   { $defval .= "<day>$day</day>"; }
		if ($era != '')   { $defval .= "<era>$era</era>"; }
		$this->SetExtendedOption('defaultValue', $defval);
		echo gettext('Default Value Settings Updated').'.';
	}
	
	/**
	  * Update the prefix values for DC
	  *
	  * @param Array[string] $vals Values for the prefix
	  *    span multiple lines.
	  *
	  * @return result string on success
	  */
	public function OptUpdatePrefixes($vals)
	{
		foreach ($vals as &$val)
		{ $val = xmlEscape($val); }
		
		$this->SetExtendedOption('prefixes', array_unique($vals) );
		echo gettext("Prefixes updated.");
	}

	/**
	  * Update the suffix values for DC
	  *
	  * @param Array[string] $vals Values for the suffix
	  *    span multiple lines.
	  *
	  * @return result string on success
	  */
	public function OptUpdateSuffixes($vals)
	{
		foreach ($vals as &$val)
		{ $val = xmlEscape($val); }
		
		$this->SetExtendedOption('suffixes', array_unique($vals) );
		echo gettext("Suffixed updated.");
	}
	
	/**
	  * Compare two dates
	  *
	  * @param string $r Date one
	  * @param string $l Date two
	  *
	  * @return result TODO: explain what it's doing exactly and what it's returning
	  */
	public static function CompareDates($r,$l){
		//Note that regardless of criteria direction, non-set dates are moved towards the end of the result array
		
		if(empty($l['year'])){
			if(!empty($r['year'])) return 1;
		}
		else if(empty($r['year']))return -1;
		else if($l['year'] < $r['year'])return -1;
		else if($l['year'] > $r['year'])return 1;
		
		if(empty($l['month'])){
			if(!empty($r['month'])) return 1;
		}
		else if(empty($r['month']))return -1;
		else if($l['month'] < $r['month'])return -1;
		else if($l['month'] > $r['month'])return 1;
		
		if(empty($l['day'])){
			if(!empty($r['day'])) return 1;
		}
		else if(empty($r['day']))return -1;
		else if($l['day'] < $r['day'])return -1;
		else if($l['day'] > $r['day'])return 1;
		return 0;
	}
	

	/**
	  * Print out the select divs for each date part
	  *
	  * @return void
	  */
	protected function PrintDateSelectDivs($defaultValue=true)
	{
		$month = $day = $year = $prefix = $suffix = '';
		if (isset($this->value->month)) $month = (string) $this->value->month;
		if (isset($this->value->day))   $day   = (string) $this->value->day;
		if (isset($this->value->year))  $year  = (string) $this->value->year;
		if (isset($this->value->prefix))  $prefix  = (string) $this->value->prefix;
		if (isset($this->value->suffix))  $suffix  = (string) $this->value->suffix;
		
		// Use output buffering to get the three fields and then display
		// them in the proper order
		
		// Month
		ob_start();
		echo "<select class='kcdc_month' name='".$this->cName."month' id='".$this->cName."month'>";
		echo '<option value=""';
		if (empty($month) && !$defaultValue) echo ' selected="selected"';
		echo '>&nbsp;</option>';
		for ($i=1; $i <= 12; $i++)
		{
			echo "<option value=\"$i\"";
			if ($i == $month && $defaultValue) echo ' selected="selected"';
			echo '>'.gettext(DateControl::$months[$i]['name']).'</option>';
		}
		echo '</select>';
		$monthDisplay = ob_get_clean();

		// Day
		ob_start();
		echo "<select class='kcdc_day' name='".$this->cName."day' id='".$this->cName."day'>";
		echo '<option value=""';
		if ((empty($day) || $day == 0) && !$defaultValue) echo ' selected="selected"';
		echo '>&nbsp;</option>';
		for ($i=1; $i <= 31; $i++)
		{
			echo "<option value=\"$i\"";
			if ($i == $day && $defaultValue) echo ' selected="selected"';
			echo ">$i</option>";
		}
		echo '</select>';
		$dayDisplay = ob_get_clean();
		
		// Year
		ob_start();
		
		echo "<select class='kcdc_year' name='".$this->cName."year' id='".$this->cName."year'>";
		echo '<option value=""';
		if ((empty($year) || $year == 0) && !$defaultValue) echo ' selected="selected"';
		echo '>&nbsp;</option>';
		
		$startYear = (int) $this->options->startYear;
		$endYear = (int) $this->options->endYear;
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
			if ($i == $year && $defaultValue) echo ' selected="selected"';
			echo ">$i</option>";
		}
		echo '</select>';
	
		$yearDisplay = ob_get_clean();

		// CE/BCE?
		ob_start();
		if ((string) $this->options->era == 'Yes')
		{
			$era = '';
			if (isset($this->value->era)) $era = (string) $this->value->era;
			
			echo '<select class="kcdc_era" name="'.$this->cName.'era" id="'.$this->cName.'era">';
			
			$eras = $this->getEraData();
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
			echo '<input type="hidden" class="kcdc_era" name="'.$this->cName.'era" id="'.$this->cName.'era" value="CE" />';
		}
		$eraDisplay = ob_get_clean();
		
		
		// prefix
		$prefixDisplay = '';
		if(isset($this->options->prefixes)){
			$prefixDisplay = '<select class="kcdc_prefix" name="'.$this->cName.'prefix" id="'.$this->cName.'prefix">';
			$prefixDisplay .= '<option></option>';
			foreach($this->options->prefixes as $value){
				$value = (string)$value;
				$selected = '';
				if($value == $prefix) $selected = 'selected="selected"';
				
				$prefixDisplay .= "<option $selected >$value</option>";
			}
			
			$prefixDisplay .= '</select>';
		}

		
		
		// suffix
		$suffixDisplay = '';
		if(isset($this->options->suffixes)){
			$suffixDisplay = '<select class="kcdc_suffix" name="'.$this->cName.'suffix" id="'.$this->cName.'suffix">';
			$suffixDisplay .= '<option></option>';
			foreach($this->options->suffixes as $value){
				$value = (string)$value;
				$selected = '';
				if($value == $suffix) $selected = 'selected="selected"';
				
				$suffixDisplay .= "<option $selected >$value</option>";
			}
			
			$suffixDisplay .= '</select>';
		}
		
				
		// Display stuff in the proper order
		if ($this->options->displayFormat == 'MDY')
		{
			echo $monthDisplay.$dayDisplay.$yearDisplay.$eraDisplay;
		}
		else if ($this->options->displayFormat == 'DMY')
		{
			echo $dayDisplay.$monthDisplay.$yearDisplay.$eraDisplay;
		}
		else if ($this->options->displayFormat == 'YMD')
		{
			echo $yearDisplay.$monthDisplay.$dayDisplay.$eraDisplay;
		}
		else
		{
			echo gettext("This control's display format is an unrecognized value; please check its options.");
		}
		
		if($prefixDisplay != ''){
			echo '<br/>Prefix: '.$prefixDisplay;
		}
		if($suffixDisplay != ''){
			echo '<br/>Suffix: '.$suffixDisplay;
		}
	}
}

?>
