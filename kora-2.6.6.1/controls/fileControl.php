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
require_once(__DIR__.'/../includes/fixity.php');
if (defined('basePath') && @$solr_enabled)
{
	require_once(basePath."includes/solrUtilities.php");
}

// JS_END USED BECAUSE THEY'RE BOMBING OUT OTHER SCRIPTS
Manager::AddJS('javascripts/DragAndDrop/jquery.fileupload-ui.js', Manager::JS_END); 
Manager::AddJS('javascripts/DragAndDrop/jquery.fileupload.js', Manager::JS_END); 
Manager::AddJS('javascripts/DragAndDrop/jquery.iframe-transport.js', Manager::JS_END); 
Manager::AddJS('javascripts/DragAndDrop/example/application.js', Manager::JS_END); 
Manager::AddJS('//ajax.aspnetcdn.com/ajax/jquery.templates/beta1/jquery.tmpl.min.js', Manager::JS_END); 
Manager::AddCSS('javascripts/DragAndDrop/jquery.fileupload-ui.css', Manager::CSS_CLASS); 
Manager::AddJS('controls/fileControl.js', Manager::JS_CLASS); 

/**
 * @class FileControl object
 *
 * This class respresents a FileControl in KORA
 */
class FileControl extends Control {
	protected $name = "File Control";
	
	/**
	  * Standard constructor for a control. See Control::Construct for details.
	  *
	  * @return void
	  */
	public function FileControl($projectid='', $controlid='', $recordid='', $inPublicTable=false)
	{
		if (empty($projectid) || empty($controlid)) return;
		global $db;
		
		$this->Construct($projectid,$controlid,$recordid,$inPublicTable);
		
		$this->isAdvSearchableValid = false;
		$this->hasFileStored = true;

		// If data exists for this control, get it
		if (!empty($this->rid))
		{
			$this->LoadValue();
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
		
		if (!empty($this->rid)) {
			// Get the information about the file and delete it
			$filePath = getFilenameFromRecordID($this->rid, $this->cid);
			if (file_exists($filePath))
			{
				$quotaQuery = 'SELECT quota,currentsize FROM project WHERE pid = "'.$this->pid.'"';
				$results = $db->query($quotaQuery);
				$result = $results->fetch_assoc();
				$fileSize = ($this->value->size)/1024.0/1024;
				$sizeUpdate = 'UPDATE project SET currentsize='.($result['currentsize']-($fileSize)).' WHERE pid="'.$this->pid.'"';
				$query = $db->query($sizeUpdate);
				unlink($filePath);
				// REMOVE FROM INDEX //
				if (@$solr_enabled) deleteFromSolrIndexByRID($this->rid, $this->cid);
			}
			
			//remove item from fixity table
			if((string)$this->options->archival == 'Yes') removeFixityItem($this->rid,$this->cid);
			// Remove the record from the database
			$deleteCall = $db->query('DELETE FROM p'.$this->pid.'Data WHERE id='.escape($this->rid).' AND cid='.escape($this->cid).' LIMIT 1');
		}
		else {
			// Remove all the files
			$fileList = $db->query('SELECT id, value FROM p'.$this->pid.'Data WHERE cid='.escape($this->cid));
			while($fileInfo = $fileList->fetch_assoc())
			{
				$recordVals = simplexml_load_string($fileInfo['value']);
				$filePath = getFilenameFromRecordID($fileInfo['id'], $this->cid);
				if (!empty($filePath) && file_exists($filePath))
				{
					$quotaQuery = 'SELECT quota,currentsize FROM project WHERE pid = "'.$this->pid.'"';
					$results = $db->query($quotaQuery);
					$result = $results->fetch_assoc();
					$fileSize = ($recordVals['size'])/1024.0/1024;
					$sizeUpdate = 'UPDATE project SET currentsize='.($result['currentsize']-($fileSize)).' WHERE pid="'.$this->pid.'"';
					$query = $db->query($sizeUpdate);
					unlink($filePath);
					// REMOVE FROM INDEX //
					if (@$solr_enabled) deleteFromSolrIndexByRID($fileInfo['id'], $this->cid);
				}
				
			}
			
			// also do this for public table, but check if there is a public table first.
			$pubFileList = $db->query('SELECT id FROM p'.$this->pid.'PublicData WHERE cid='.escape($this->cid));
			if (!$db->error){
				while($fileInfo = $pubFileList->fetch_assoc())
				{
					$filePath = publicGetFilenameFromRecordID($this->pid, $this->sid, $this->cid, $fileInfo['id']);
					if (!empty($filePath) && file_exists($filePath)) unlink($filePath);
				}
			}
			
			//kill the fixity information for these non-existant files.
			if((string)$this->options->archival == 'Yes') {
				$query = "DELETE FROM fixity WHERE kid LIKE '".dechex($this->pid)."-%' AND cid=".$this->cid;
				$db->query($query);
			}
			
			// Remove all the records from the database
			$deleteCall = $db->query('DELETE FROM p'.$this->pid.'Data WHERE cid='.escape($this->cid));
			$publicDeleteCall = $db->query('DELETE FROM p'.$this->pid.'PublicData WHERE cid='.escape($this->cid));
		}
		
		//function must be present in all delete extentions of base class, Control
		$this->deleteEmptyRecords();
	}
	
	public function GetLocalName() { return $this->value->localName; }
	public function GetOrigName() { return $this->value->originalName; }
	public function GetFileSize() { return $this->value->size; }
	public function GetFileMime() { return $this->value->size; }
	
	/**
	* Gets the url local filename
	*
	* @return string
	*/
	function GetURL()
	{
		return baseURI.fileDir.$this->GetPID().'/'.$this->GetSID().'/'.rawurlencode($this->GetLocalName());
	}
	
	/**
	* Gets the base local filename
	*
	* @return string
	*/
	function GetPath()
	{
		return basePath.fileDir.$this->GetPID().'/'.$this->GetSID().'/'.$this->GetLocalName();
	}
	
	/**
	  * Prints control view for public ingestion
	  *
	  * @param bool $isSearchForm Is this for a search form instead
	  *
	  * @return void
	  */
	public function display($defaultValue=true) {
		global $db;
		
		if (!$this->StartDisplay()) { return false; }
		?>
		<?php
		//echo '<table><tr><td>';
		echo '<input type="hidden" name="preset'.$this->cName.'" id="preset'.$this->cName.'" value="'.$this->preset.'" />
		<span><i>To upload a file, click below or drag from desktop to gray area <br/> (javascript must be enabled for drag-and-drop to work)</i></span>';
		if($this->value) {  
			echo '<div><input type="file" name="'.$this->cName.'" id="'.$this->cName.'" class="filespace"></div>';
			if(Manager::GetRecord() != null){
				echo '<div id="existing'.$this->cName.'" class="kcfc_existingfile"><br />'.gettext('Existing File').': <a href="';
				echo $this->GetURL();
				echo '" class="kcfc_file_existing"><strong>'.$this->value->originalName.'</strong></a><br/><strong>'.gettext('Size').':</strong> '.$this->value->size;
				echo '<br /><br />';
				echo '<a class="link kcfc_delfile">'.gettext('Delete this File').'</a>';
				echo '</div>';
			}
		} else{
			echo '<div><input type="file" name="'.$this->cName.'" id="'.$this->cName.'" class="filespace"></div>';
		}
		echo "<i>Note: Video Playback supported for MP4 videos</i>";
		$this->EndDisplay();
		//echo '</table>';
	}
	
	/**
	  * Print out the XML value of the TC
	  *
	  * @return void
	  */
	public function displayXML() {
		if (!$this->isOK()) return;
		
		$xmlString = '<file>';
		
		$xmlString .= '</file>';
		
		return $xmlString;
	}
	
	/**
	  * Return string to enter into a Kora_Clause (FC Incompatible)
	  *
	  * @param string $submitData Submited data for control
	  *
	  * @return false
	  */
	public function getSearchString($submitData) { return false; }
	
	public function getType() { return "File"; }
	
	/**
	  * Set the value of the XML imput
	  *
	  * @param string $value Value to set
	  *
	  * @return void
	  */
	public function setXMLInputValue($value) {
		$this->XMLInputValue = extractFileDir.$value[0];
		if (isset($value['_attributes'])){
			$this->XMLAttributes =$value['_attributes'];
		}
	}
	
	/**
	  * Ingest the data into the control
	  *
	  * @param string $publicIngest Are we ingesting the data publically
	  *
	  * @return void
	  */
	public function ingest($publicIngest = false) {
		global $db;
		
		if (empty($this->rid)) {
			echo '<div class="error">'.gettext('No Record ID Specified').'.</div>';
			return;
		}
		//determine whether to insert into public ingestion table or not
		if($publicIngest)
		{
			$tableName = 'PublicData';
		}
		else $tableName = 'Data';
		if (isset($_REQUEST['preset'.$this->cName])&&!empty($_REQUEST['preset'.$this->cName]))
		{
			$rid = Record::ParseRecordID($_REQUEST['preset'.$this->cName]);
			
			// if the rid is value, see if it constitutes a valid data object
			if ($rid && $rid['project'] == $this->pid && $rid['scheme'] == $this->sid)
			{
				// If it contains a valid data object, copy its file and set our data
				// to match the preset's data
				$valueCheck = $db->query('SELECT value FROM p'.$this->pid.$tableName.' WHERE id='.escape($rid['rid']).' AND cid='.escape($this->cid).' LIMIT 1');
				if ($valueCheck->num_rows > 0)
				{
					$valueCheck = $valueCheck->fetch_assoc();
					$this->value = simplexml_load_string($valueCheck['value']);
					
					// Calculate the new name (without any absolute path) for storing
					// in the database
					$newFileName = $this->rid.'-'.$this->cid.'-'.(string) $this->value->originalName;
					$this->value->localName = xmlEscape($newFileName);
					
					// Store the new information in the database so that we can use
					// getFilenameFromRecordID later
					
					if ($this->existingData)
					{
						// Remove any old file if it exists
						$filePath = getFilenameFromRecordID($this->rid, $this->cid);
						if(file_exists($filePath)) unlink($filePath);
						$db->query('UPDATE p'.$this->pid.$tableName.' SET value='.escape($this->value->asXML().' WHERE id='.escape($this->rid).' AND cid='.escape($this->cid)));
					}
					else
					{
						$db->query('INSERT INTO p'.$this->pid.$tableName.' (id, cid, schemeid, value) VALUES ('.escape($this->rid).', '.escape($this->cid).', '.escape($this->sid).', '.escape($this->value->asXML()).')');
					}
					
					// oldFileName and newFileName are absolute paths
					$oldFileName = getFilenameFromRecordID($rid['rid'], $this->cid);
					$newFileName = getFilenameFromRecordID($this->rid, $this->cid);
					if((string)$this->options->archival == 'Yes') {
						addFixityItem($this->rid,$this->cid,$newFileName);
					}
					// copy the file
					copy($oldFileName, $newFileName);
					
					// ADD TO INDEX //
		        		if (!$publicIngest && @$solr_enabled)
		       			{
		        			addToSolrIndexByRID($this->rid, $this->cid);
		        		}
		        	}
		        }
		}
		elseif (!$this->isEmpty())
		{
			if ( (isset($_FILES[$this->cName]) && $_FILES[$this->cName]['error'] != UPLOAD_ERR_NO_FILE) || file_exists($this->XMLInputValue) )
			{
				// Is there an existing file?  If so, delete it
				if ($this->existingData)
				{
					$filePath = getFilenameFromRecordID($this->rid, $this->cid);
					if(file_exists($filePath)) unlink($filePath);
					if((string)$this->options->archival == 'Yes') removeFixityItem($this->rid,$this->cid);
				}
				
				// make sure directory exists
				if($publicIngest)
				{
					//temporary storage for publically ingested files to be approved
					$fileDirectory = awaitingApprovalFileDir;
				}
				else
				{
					//default file directory, ingested from within KORA
					$fileDirectory = fileDir;
				}
				
				
				$parentDir = basePath.$fileDirectory;
				$fileDir = basePath.$fileDirectory.$this->pid.'/';
				$oldumask = umask(0);
				if (!is_dir($fileDir)) { 
					if (is_writable($parentDir))
						mkdir($fileDir, 02775);
					else {
						echo '<div class="error">'.gettext('File directory not writable').'.</div>';
						return;
					}
				}
				
				$parentDir = $fileDir;
				$fileDir .= $this->sid.'/';
				if (!is_dir($fileDir)) { 
					if (is_writable($parentDir))
						mkdir($fileDir, 02775);
					else {
						echo '<div class="error">'.gettext('Project file directory not writable').'.</div>';
						return;
					}
				}
				umask($oldumask);
				
				// copy file over
				if (!empty($_FILES[$this->cName])) {
					// & and ' are allowed in filenames on win7 but are not allowed in xml. remove them.
					$origName = str_replace(array('&',"'"), array('', ''), $_FILES[$this->cName]['name']);
					$type = $_FILES[$this->cName]['type'];
					
					$newName = $this->rid . '-' . $this->cid . '-' . $origName;
					$success = @move_uploaded_file($_FILES[$this->cName]['tmp_name'], $fileDir.$newName);
					if (!$success) {
						echo '<div class="error">'.gettext('Unable to upload file').'.</div>';
						return;
					}
				}
				else if (isset($this->XMLInputValue)) {
					$pathInfo = pathinfo($this->XMLInputValue);
					$origName = $pathInfo['basename'];
					if (isset($this->XMLAttributes)){
						$origName = $this->XMLAttributes['originalName'];
					}
					$finfo = finfo_open(FILEINFO_MIME); // return mime type ala mimetype extension
					$type = finfo_file($finfo, $this->XMLInputValue);
					finfo_close($finfo);
		        		
					$newName = $this->rid . '-' . $this->cid . '-' . $origName;
					copy($this->XMLInputValue,$fileDir.$newName);
				}
				
				
				// create XML record
				$xml  = '<file><originalName>'.$origName.'</originalName>';
				$xml .= '<localName>'.$newName.'</localName>';
				$xml .= '<size>'.filesize($fileDir.$newName).'</size>';
				$xml .= '<type>'.$type.'</type>';
				$xml .= '</file>';
				$this->value = simplexml_load_string($xml);
				
				
				// insert into the table
				if ($this->existingData)
				{
					$db->query('UPDATE p'.$this->pid.$tableName.' SET value='.escape($xml).' WHERE id='.escape($this->rid).' AND cid='.escape($this->cid));
				}
				else
				{
					$db->query('INSERT INTO p'.$this->pid.$tableName.' (id, cid, schemeid, value) VALUES ('.escape($this->rid).', '.escape($this->cid).', '.escape($this->sid).', '.escape($xml).')');
				}
				if((string)$this->options->archival == 'Yes') {
					addFixityItem($this->rid,$this->cid,$fileDir.$newName);
				}
				
				// ADD TO INDEX //
				if (!$publicIngest && @$solr_enabled)
				{
					addToSolrIndexByRID($this->rid, $this->cid);
				}
			}
		}
	}
	
	/**
	  * Initialize function for control options
	  *
	  * @return void
	  */
	public static function initialOptions()
	{
		return '<options><maxSize>0</maxSize><restrictTypes>No</restrictTypes><allowedMIME></allowedMIME><archival>No</archival></options>';
	}
	
	/**
	  * Does this control have data in it?
	  *
	  * @return true on success
	  */
	public function isEmpty() {
		return !( !((empty($_FILES[$this->cName]) || $_FILES[$this->cName]['error'] == 4) && !$this->existingData) || isset($this->XMLInputValue) );
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
			$returnString = '';
			if($this->inPublicTable) {
				$returnString .= "<div class='kc_file_name'>".gettext('Name').': <a href="'.publicGetURLFromRecordID($this->pid, $this->sid, $this->cid, $this->rid).'">'.$this->value->originalName.'</a></div>';
			}
			else {
				$returnString .= "<div class='kc_file_name'>".gettext('Name').': <a href="'.getURLFromRecordID($this->rid, $this->cid).'">'.$this->value->originalName.'</a></div>';
			}
			
			$returnString .= "<div class='kc_file_size'>".gettext('Size').': '.$this->value->size.' bytes</div>';
			$returnString .= "<div class='kc_file_type'>".gettext('Type').': '.$this->value->type.'</div>';
            if($this->value->type == 'video/mp4' ) {
                $returnString .= "<div class='kc_file_video'>".gettext('Video').': <br>';
                if ($this->inPublicTable) {
                    $returnString .= "<video src='" . publicGetURLFromRecordID($this->pid, $this->sid, $this->cid, $this->rid) . "' width='320' height='240'>";
                } else {
                    $returnString .= "<video src='" . getURLFromRecordID($this->rid, $this->cid) . "' width='320' height='240'>";
                }
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
		if (isset($xml->originalName))
		{
			$returnVal .= "<div class='kc_file_name'>".gettext('Name').': '.$xml->originalName.'</div>';;
		}
		if (isset($xml->size))
		{
			$returnVal .= "<div class='kc_file_size'>".gettext('Size').': '.$xml->size.' '.gettext('bytes').'</div>';
		}
		if (isset($xml->type))
		{
			$returnVal .= "<div class='kc_file_type'>".gettext('Type').': '.$xml->type.'</div>';
		}
		
		return $returnVal;
	}
	
	/**
	  * Gathers values from XML (TC Incompatible)
	  *
	  * @param string $xml XML object to get data from
	  *
	  * @return Array of values
	  */
	public function storedValueToSearchResult($xml)
	{
		$xml = simplexml_load_string($xml);
		
		$returnVal = array();
		$returnVal['originalName'] = (string) $xml->originalName;
		$returnVal['localName'] = (string) $xml->localName;
		$returnVal['size'] = (string) $xml->size;
		$returnVal['type'] = (string) $xml->type;
		
		return $returnVal;
	}
	
	/**
	  * Validates the ingested data to see if it meets the data requirements for this control
	  *
	  * @param bool $publicIngest Is this a public ingestion
	  *
	  * @return Result string
	  */
	public function validateIngestion($publicIngest = false) {
		global $db;
		$type = '';
		$fileName = '';
		$fileExists = false;
		$fileSize = 0;
		
		
		if (!empty($this->XMLInputValue)) {
			$fileExists = file_exists($this->XMLInputValue);
			$fileName = $this->XMLInputValue;
			
			if($fileExists) 
			{
				// file ingesting through xml importer
				$finfo = finfo_open(FILEINFO_MIME); // return mime type ala mimetype extension
				$type = finfo_file($finfo, $this->XMLInputValue);
				finfo_close($finfo);
				// finfo returns strings like: image/jpeg; charset=binary
				// we just want the first part.
				$type = explode(";",$type);
				$type = $type[0];
				
				$fileSize = filesize($this->XMLInputValue);
			}
			
		}else if(!empty($_FILES[$this->cName])){
			// file ingesting through web ingestion form
			$type = $_FILES[$this->cName]['type'];
			$fileExists = ($_FILES[$this->cName]['error']==UPLOAD_ERR_OK);
			$fileSize = (int)$_FILES[$this->cName]['size'];
			$fileName = $_FILES[$this->cName]['tmp_name'];
		}
		
		// First, see if it's required and no value was supplied.
		if ($fileName == ''){
			if ($this->required && !$this->existingData){
				return htmlEscape($this->name).': '.gettext('No value supplied for required field');
			}else{
				// control will be empty/use existing value
				return '';
			}
		}
		
		// make sure the file is there
		if(!$fileExists){
			return htmlEscape($this->name).': '.gettext('File upload failed');
		}
		
		// check to see if file upload directory is write-able
		if($publicIngest) {
			//temporary storage for publically ingested files to be approved
			$fileDirectory = awaitingApprovalFileDir;
		}
		else {
			//default file directory, ingested from within KORA
			$fileDirectory = fileDir;
		}
		
		$baseUploadDir = basePath.$fileDirectory;
		$projUploadDir = $baseUploadDir.$this->pid.'/';
		$schemeUploadDir = $projUploadDir.$this->sid.'/';
		
		$oldumask = umask(0);	
		// i guess if the final target upload dir exists, even if the baseUploadDir is not writable... pass this check
		if (!is_dir($schemeUploadDir) && !is_writable($baseUploadDir)) {
			return htmlEscape($this->name).': '.gettext('Global file upload directory not writable');
		}
		elseif (!is_dir($projUploadDir) && is_writable($baseUploadDir)) { mkdir($projUploadDir, 02775);	}
		
		// same.. if the final target upload dir exists, even if the schemeUploadDir is not writable... pass this check
		if (!is_dir($schemeUploadDir) && !is_writable($projUploadDir)) {
			return htmlEscape($this->name).': '.gettext('Project file upload directory not writable');
		}
		elseif (!is_dir($schemeUploadDir) && is_writable($projUploadDir)) { mkdir($schemeUploadDir, 02775); }
		
		// this one just check the final target, if it exists, but is not writable... fail
		if (is_dir($schemeUploadDir) && !is_writable($schemeUploadDir)) {
			return htmlEscape($this->name).': '.gettext('Scheme file upload directory not writable');
		}
		umask($oldumask);
		// done checking if directories are writable    	
		
		// check the file type
		if ( (string)$this->options->restrictTypes == 'Yes' ){
			$allowedMIME = array();
			foreach($this->options->allowedMIME->mime as $mime){
				$allowedMIME[] = (string)$mime;
			}
			
			if (!in_array($type, $allowedMIME)){
				return htmlEscape($this->name).': '.gettext('Filetype is not in approved list').': '.$type;
			}
		}
		
		// make sure file is the right size
		if ( (int)$this->options->maxSize > 0 && $fileSize > (int)$this->options->maxSize * 1024 ){
			return htmlEscape($this->name).': '.gettext('File too large').'.';
		}
		
		$quotaQuery = 'SELECT quota,currentsize FROM project WHERE pid = "'.$this->pid.'"';
		$results = $db->query($quotaQuery);
		$result = $results->fetch_assoc();
		if($result['quota']!=0)
		{
			if((($fileSize/1024.0/1024)+$result['currentsize'])>$result['quota'])
			{
				return gettext('Quota has been reached. File is too large');
			}
		}
		$sizeUpdate = 'UPDATE project SET currentsize='.($fileSize/1024.0/1024+$result['currentsize']).' WHERE pid="'.$this->pid.'"';
		$db->query($sizeUpdate);
		// everything is ok
		return '';
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
	  * Write control data to an xml object
	  *
	  * @param string $simplexml XML to write to
	  *
	  * @return XMl object
	  */
	public function ExportToSimpleXML(&$simplexml) 
	{
		$node = $simplexml->addChild(str_replace(' ', '_', $this->GetName()), xmlEscape($this->GetLocalName()));
		$node->addAttribute('originalName', $this->GetOrigName());
		
		return $simplexml;
	}

	/**
	  * Print out each menu piece of the control options
	  *
	  * @return void
	  */
	public function showDialog()
	{
		print "<div class='kora_control kora_control_opts' pid='{$this->pid}' cid='{$this->cid}'>";
		$this->OptPrintMaxFileSize();
		$this->OptPrintAllowedFileTypes();
		$this->OptPrintRestrictFileTypes();
		$this->OptPrintArchival(); // <-- PLUGIN?
		$this->OptPrintPresets();
		$this->OptPrintSavePreset();
		print "</div>";
	}
	
	// TODO:  KILL ALL OF THESE UGLY TABLES
	/**
	  * Print out table for max file size
	  *
	  * @return void
	  */
	protected function OptPrintMaxFileSize()
	{ 
		$xml = $this->GetControlOptions();
		if(!$xml) return;
		?>
		<table class="table kcopts_style">
		<tr>
		<td width="60%" class="kcopt_label"><strong><?php echo gettext('Maximum File Size')?></strong><br /><?php echo gettext('The maximum size (in kB, 1024kB = 1MB) allowed to be uploaded by this control.  Set to 0 to have no limit.')?></td>
		<td>
		<input type="text" name="fileSize" class="kcfcopts_maxsize" value="<?php echo (string) $xml->maxSize ?>" />
		</td>
		</tr>
		</table>
	<?php }

	/**
	  * Print out table for allowed file types
	  *
	  * @return void
	  */
	protected function OptPrintAllowedFileTypes()
	{ 
		$xml = $this->GetControlOptions();
		if(!$xml) return;
		?>
		<table class="table kcopts_style">
		<tr>
		<td width="60%" class="kcopt_label"><strong><?php echo gettext('Allowed File Types')?></strong><br />
		<?php  printf('Provide a list of %s that are allowed for ingestion into this control',"<a href=\"http://www.iana.org/assignments/media-types/\">MIME</a> filetypes (such as 'image/jpeg')");?>.</td>
		<td><select name="allowedTypes"  class="kcfcopts_allowedtypes" size="5">
		<?php
		foreach($xml->allowedMIME->mime as $mime)
		{
			echo "<option value=\"$mime\">$mime</option>";
		}
		?>
		</select> <br />
		<input type="button" class="kcfcopts_allowedtypesmoveup" value="<?php echo gettext('Up')?>" />
		<input type="button" class="kcfcopts_allowedtypesmovedown" value="<?php echo gettext('Down')?>" />
		<input type="button" class="kcfcopts_allowedtypesremove" value="<?php echo gettext('Remove')?>" />
		<br /><br /><input type="text" name="newOption"  class="kcfcopts_allowedtypesnew" />
		<input type="button"  class="kcfcopts_allowedtypesadd" value="<?php echo gettext('Add Option')?>" /></td>
		</tr>
		</table>
	<?php }

	/**
	  * Print out table for restricted file types
	  *
	  * @return void
	  */
	protected function OptPrintRestrictFileTypes()
	{ 
		$xml = $this->GetControlOptions();
		if(!$xml) return;
		?>
		<table class="table kcopts_style">
		<tr>
		<td width="60%" class="kcopt_label"><strong><?php echo gettext('Restrict File Types?')?></strong><br /><?php echo gettext('If this is set to yes, only the filetypes set in the above list will be allowed')?></td>
		<td><input type="radio" name="restrictTypes" class="kcfcopts_restrictedtypes" value="No" <?php  if ( (string)$xml->restrictTypes == 'No' ) echo 'checked'; ?> /><?php echo gettext('No')?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" name="restrictTypes" class="kcfcopts_restrictedtypes" value="Yes" <?php  if ( (string)$xml->restrictTypes == 'Yes' ) echo 'checked'; ?> /><?php echo gettext('Yes')?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		</tr>
		</table>
	<?php }

	/**
	  * Print out table for archival settings
	  *
	  * @return void
	  */
	protected function OptPrintArchival()
	{ 
		$xml = $this->GetControlOptions();
		if(!$xml) return;
		?>
		<table class="table kcopts_style">
		<tr>
		<td width="60%" class="kcopt_label"><strong><?php echo gettext('Archival Enabled?')?></strong><br /><?php echo gettext('This will enable fixity integrity checking on files ingested after this is enabled')?></td>
		<td><input type="radio" name="archival" class="kcfcopts_archival" value="No" <?php if ((string)$xml->archival == 'No') echo 'checked'; ?> /><?php echo gettext('No')?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" name="archival" class="kcfcopts_archival" value="Yes" <?php if ((string)$xml->archival == 'Yes') echo 'checked'; ?> /><?php echo gettext('Yes')?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		</td>
		</tr>
		</table>
	<?php }
	
	/**
	  * Print out table for file presets
	  *
	  * @return void
	  */
	protected function OptPrintPresets()
	{ 
		global $db;
		$xml = $this->GetControlOptions();
		if(!$xml) return;
		?>
		<table class="table kcopts_style">
		<tr>
		<td width="60%" class="kcopt_label"><strong><?php echo gettext('Presets')?></strong><br /><?php echo gettext('Sets of pre-defined file-types which are commonly used')?></td>
		<td><select name="filePreset" class="kcfcopts_presetname" >
		<option></option>
		<?php
		
		// Get the list of File Control Presets
		$presetQuery = $db->query('SELECT name, presetid FROM controlPreset WHERE class=\'FileControl\' AND (global=1 OR project='.$this->pid.') ORDER BY name');
		while($preset = $presetQuery->fetch_assoc())
		{
			echo "<option value=\"$preset[presetid]\">".htmlEscape($preset['name']).'</option>';
		}
		?>
		</select> <input type="button" class="kcfcopts_presetuse" value="<?php echo gettext('Use Preset')?>" />
		</tr>
		</table>
	<?php }
	
	/**
	  * Print out table for saving file presets
	  *
	  * @return void
	  */
	protected function OptPrintSavePreset()
	{ 
		$xml = $this->GetControlOptions();
		if(!$xml) return;
		?>
		<table class="table kcopts_style">
		<tr>
		<td width="60%" class="kcopt_label"><strong><?php echo gettext('Create New Preset')?></strong><br /><?php echo gettext("If you would like to save this set of allowed file types as a preset, enter a name and click 'Save as Preset'")?>.</td>
		<td><input type="text" name="presetName" class="kcfcopts_presetnew" /> <input type="button" class="kcfcopts_presetsave" value="<?php echo gettext('Save as Preset')?>" /></td>
		</tr>
		</table>
	<?php }
	
	/**
	  * Update the mime types for the file control
	  *
	  * @param Array[string] $mimetypes Mime types
	  *
	  * @return result string on success
	  */
	public function updateMimeTypes($mimetypes)
	{// TODO: REDO THIS OPTION AS SEVERAL 'allowedMIME' NODES?
		$mimexml = '';
		foreach ($mimetypes as $val)
		{ $mimexml .= '<mime>'.xmlEscape($val).'</mime>'; }
		
		$this->SetExtendedOption('allowedMIME', $mimexml );
		echo gettext('Mime Types Updated').'.<br /><br />';
	}
	
	/**
	  * Set the maximum file size allowed for FC
	  *
	  * @param int $size File size
	  *
	  * @return result string on success
	  */
	public function updateFileSize($size)
	{
		$size = (int) $size;
		
		if ($size < 0)
		{
			echo gettext('Invalid maximum size specified').'.';
		}
		else
		{
			// NO XMLESCAPE BECAUSE IT'S ALREADY CASTED ABOVE
			$this->SetExtendedOption('maxSize', $size );
			echo gettext('Maximum File Size Updated').'.<br /><br />';
		}
	}
	
	/**
	  * Update the file restrictions for FC
	  *
	  * @param Array[string] $restrictions List of restrictions
	  *
	  * @return result string
	  */
	public function updateFileRestrictions($restrictions)
	{
		global $db;
		
		if (!in_array($restrictions, array('No', 'Yes')))
		{
			echo gettext("Restrictions must be 'Yes' or 'No'").'.';
		}
		else
		{
			// NO XMLESCAPE BECAUSE IT'S SANITIZED ABOVE
			$this->SetExtendedOption('restrictTypes', $restrictions );
			echo gettext('File Restrictions Updated').'.<br /><br />';
		}
	}
	
	/**
	  * Set whether or not to use archiving
	  *
	  * @param string $archival Response to turn it on or off
	  *
	  * @return result string
	  */
	public function updateArchival($archival)
	{
		if (!in_array($archival, array('No', 'Yes')))
		{
			echo gettext("Archival must be 'Yes' or 'No'").'.';
		}
		else
		{
			// NO XMLESCAPE BECAUSE IT'S SANITIZED ABOVE
			$this->SetExtendedOption('archival', $archival );
			echo gettext('Archival Settings Updated').'.<br /><br />';
		}
	}
	
	/**
	  * Set a preset for the file control
	  *
	  * @param int $newPresetID Preset ID
	  *
	  * @return void
	  */
	public function usePreset($newPresetID)
	{
		global $db;
		
		$existenceQuery = $db->query('SELECT value FROM controlPreset WHERE class=\'ListControl\' AND presetid='.escape($newPresetID).' LIMIT 1');
		
		if ($existenceQuery->field_count > 0)
		{
			$existenceQuery = $existenceQuery->fetch_assoc();
			
			$query = 'UPDATE p'.$this->pid.'Control SET options=';
			$query .= escape($existenceQuery['value']);
			$query .= ' WHERE cid='.escape($this->cid).' LIMIT 1';
			
			$db->query($query);
		}
	}
	
	/**
	  * Save a new preset to FC
	  *
	  * @param string $name Name of preset
	  * @param Array[string] $mimetypes Mime types for preset
	  *
	  * @return result string on failure
	  */
	public function savePreset($name, $mimetypes)
	{
		global $db;
		
		$freeNameQuery = $db->query('SELECT presetid FROM controlPreset WHERE class=\'FileControl\' AND name='.escape($name).' LIMIT 1');
		if ($freeNameQuery->num_rows > 0)
		{
			echo gettext('There is already a File Control preset with the name').': '.htmlEscape($name);
		}
		else
		{
			$xml = $this->GetControlOptions();
			if(!$xml) return;
			
			$newXML = simplexml_load_string('<allowedMIME />');
			if (isset($xml->allowedMIME->mime))
			{
				foreach($xml->allowedMIME->mime as $mime)
				{
					$newXML->addChild('mime', xmlEscape((string) $mime));
				}
			}
			
			$db->query('INSERT INTO controlPreset (name, class, project, global, value) VALUES ('.escape($name).", 'FileControl', $this->pid, 0, ".escape($newXML->asXML()).')');
		}
	}
	
	/**
	  * Delete file from control
	  *
	  * @param string $kid Record ID to delete file from
	  *
	  * @return result string
	  */
	public function deleteFile($kid)
	{
		// Make sure that this is a legitamite kid
		$kidInfo = Record::ParseRecordID($kid);
		if (!$kidInfo ||
			$kidInfo['project'] != $this->pid ||
			$kidInfo['scheme']  != $this->sid)
		{
			echo gettext('Invalid KID');
			return;
		}
		// Delete the file, delete the record from the DB.  That SHOULD
		// be all that's required.  If for some reason the delete file function
		// starts breaking ingestion, this would be a good first place to look.
		$filePath = getFilenameFromRecordID($kid, $this->cid);
		global $db;
		if(file_exists($filePath))
		{
			// REMOVE FROM INDEX //
			if (@$solr_enabled) deleteFromSolrIndexByRID($kid, $this->cid);
			unlink($filePath);
		}
		$db->query('DELETE FROM p'.$kidInfo['project'].'Data WHERE id='.escape($kid).' AND cid='.escape($this->cid).' LIMIT 1');
		echo gettext('File Deleted');
	}	
}




?>
