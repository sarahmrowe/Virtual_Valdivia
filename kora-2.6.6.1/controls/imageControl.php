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
Manager::AddJS('controls/imageControl.js', Manager::JS_CLASS); 

/**
 * @class ImageControl object
 *
 * This class respresents a ImageControl in KORA
 */
class ImageControl extends FileControl
{
	protected $name = 'Image Control';
	public static $maxThumbWidth = 300;
	public static $maxThumbHeight = 300;
	
	/**
	  * Delete this control from it's project
	  *
	  * @return void
	  */
	public function delete()
	{
		// Delete the Thumb before calling FileControl::Delete because otherwise the
		// information will be lost
		@unlink($this->thumbPath($this->inPublicTable));
		
		FileControl::delete();
	}
	
	public function getType() { return "Image"; }
	
	/**
	  * Set the value of the XML imput
	  *
	  * @param string $value Value to set
	  *
	  * @return void
	  */
	public function setXMLInputValue($value) {
		FileControl::setXMLInputValue($value);
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
		// If there's existing data and a new file is uploaded, we must remove the thumbnail
		// before calling FileControl::ingest because otherwise the information necessary to
		// delete the file will be lost
		if ($this->existingData && !empty($_FILES[$this->cName]) && $_FILES[$this->cName]['error'] != UPLOAD_ERR_NO_FILE)
		{
			@unlink($this->thumbPath($publicIngest));
		}
		
		FileControl::ingest($publicIngest);
		
		if (!FileControl::isEmpty())
		{
			if ( (isset($_FILES[$this->cName]) && $_FILES[$this->cName]['error'] != UPLOAD_ERR_NO_FILE) || isset($this->value))
			{
				// Create the Thumbnail
				
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
				
				$parentDir = basePath.$fileDirectory.$this->pid.'/'.$this->sid;
				$thumbDir = basePath.$fileDirectory.$this->pid.'/'.$this->sid.'/thumbs/';
				if (!is_dir($thumbDir)) {
					if (is_writable($parentDir))
						mkdir($thumbDir, 02775);
					else {
						echo '<div class="error">'.gettext('Thumbnail directory not writable').'.</div>';
						return;
					}
				}
				
				// I don't use getFilenameFromRecordID here because for some reason (database refresh
				// stuff?) it pulls up the old localName.
				$origPath = basePath.$fileDirectory.$this->pid.'/'.$this->sid.'/'.(string) $this->value->localName;
				
				createThumbnail($origPath, $this->thumbPath($publicIngest), (int) $this->options->thumbWidth, (int) $this->options->thumbHeight);
			}
			else if (isset($_REQUEST['preset'.$this->cName]) && !empty($_REQUEST['preset'.$this->cName]))
			{
				createThumbnail(getFilenameFromRecordID($this->rid, $this->cid), $this->thumbPath($publicIngest), (int) $this->options->thumbWidth, (int) $this->options->thumbHeight);
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
		return '<options><maxSize>0</maxSize><restrictTypes>Yes</restrictTypes><allowedMIME><mime>image/bmp</mime><mime>image/gif</mime><mime>image/jpeg</mime><mime>image/png</mime><mime>image/pjpeg</mime><mime>image/x-png</mime></allowedMIME><thumbWidth>125</thumbWidth><thumbHeight>125</thumbHeight><archival>No</archival></options>';
	}
	
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
			// Regenerate the thumbnail if necessary
			if (!file_exists($this->thumbPath($this->inPublicTable)))
			{
				createThumbnail(getFilenameFromRecordID($this->rid, $this->cid), $this->thumbPath($this->inPublicTable), (int) $this->options->thumbWidth, (int) $this->options->thumbHeight);
			}
			$returnString .= '<div class="kc_file_tn"><img src="'.$this->thumbURL($this->inPublicTable).'" /></div>';
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
	public function storedValueToDisplay($xml, $pid, $cid)
	{
		$xml = simplexml_load_string($xml);
		$pid = (int) $pid;
		if ($pid < 1) return gettext('Invalid PID');
		$cid = (int) $cid;
		if ($cid < 1) return gettext('Invalid Control ID');
		
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
		if (isset($xml->localName))
		{
			// Regenerate the thumbnail if necessary
			if (!file_exists(getThumbPathFromFileName($xml->localName)))
			{
				// Load the options to get the maximum thumbnail
				global $db;
				$optionQuery = $db->query('SELECT options FROM p'.$pid.'Control WHERE cid='.$cid.' LIMIT 1');
				if ($optionQuery->num_rows < 1)
				{
					return gettext('Invalid PID/CID');
				}
				$optionQuery = $optionQuery->fetch_assoc();
				
				$options = simplexml_load_string($optionQuery['options']);
				
				createThumbnail(getFullPathFromFileName($xml->localName), getThumbPathFromFileName($xml->localName), (int) $options->thumbWidth, (int) $options->thumbHeight);
			}
			$returnVal .= '<div class="kc_file_tn"><img src="'.getThumbURLFromFileName((string)$xml->localName).'" /></div>';
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
	public function validateIngestion($publicIngest = false) {
		$fileError = FileControl::validateIngestion($publicIngest);
		if ($fileError != '') return $fileError;
		
		// check if there was a file uploaded
		$fileName = '';
		if (!empty($this->XMLInputValue)){
			$fileName = $this->XMLInputValue;
		}else if (!empty($_FILES[$this->cName])){
			$fileName = $_FILES[$this->cName]['tmp_name'];
		}
		// test that the uploaded file is actually an image
		if($fileName!='' && getimagesize($fileName)===false){
			return htmlEscape($this->name).': '.gettext('File is not an image');
		}
		
		// Don't fail if there is no file uploaded.
		// This is checked for in the File Control.
		return '';
	}
	
	/**
	  * Return the directory path to the thumbnail image
	  *
	  * @param bool $publicIngest Does this IC still need approval
	  *
	  * @return void
	  */
	protected function thumbPath($publicIngest = false)
	{
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
		
		return basePath.$fileDirectory.$this->pid.'/'.$this->sid.'/thumbs/'
		.(string) $this->value->localName;
	}
	
	/**
	  * Return the directory url to the thumbnail image
	  *
	  * @param bool $publicIngest Does this IC still need approval
	  *
	  * @return void
	  */
	protected function thumbURL($publicIngest = false)
	{
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
		
		return baseURI.$fileDirectory.$this->pid.'/'.$this->sid.'/thumbs/'.(string) $this->value->localName;
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
		$this->OptPrintMaxFileSize();
		$this->OptPrintAllowedFileTypes();
		$this->OptPrintArchival(); // <-- PLUGIN?
		$this->OptPrintThumbnailSize();
		print "</div>";
	}
	
	
	// TODO:  KILL ALL OF THESE UGLY TABLES
	/**
	  * Print out table for setting thumbnail size
	  *
	  * @return void
	  */
	protected function OptPrintThumbnailSize()
	{ 
		$xml = $this->GetControlOptions();
		if(!$xml) return;
		?>
		<table class="table kcopts_style">
		<tr>
		<td width="60%" class="kcopt_label"><b>Thumbnail Size</b><br />These settings control the maximum width/height of the image previews shown when displaying a record.  Please keep sizes between 1 and <?php echo ImageControl::$maxThumbWidth?> pixels.</td>
		<td>
		<?php
		echo '<table border="0">';
		echo '<tr><td>'.gettext('Width').':</td><td><input type="text" name="thumbWidth" class="kcicopt_thumbwidth" value="'.(string) $xml->thumbWidth.'" /></td></tr>';
		echo '<tr><td>'.gettext('Height').':</td><td><input type="text" name="thumbHeight" class="kcicopt_thumbheight" value="'.(string) $xml->thumbHeight.'" /></td></tr>';
		echo '</table>';
		?>
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
		<td width="60%" class="kcopt_label"><b><?php echo gettext('Allowed File Types')?></b><br /><?php echo gettext('Note: This list is based on the image types which PHP can process and cannot be changed; it is provided here solely for reference.')?></td>
		<td>
		<select class="kcicopt_allowedmimes" multiple="multiple" size="4">
		<option>image/bmp</option>
		<option>image/gif</option>
		<option>image/jpeg</option>
		<option>image/png</option>
		</select>
		</td>
		</tr>
		</table>
	<?php }
	
	/**
	  * Update the file size of the thumbnail
	  *
	  * @param int $width Width of thumbnail
	  * @param int $height Height of thumbnail
	  * 
	  * @return void
	  */
	public function updateThumbnailSize($width, $height)
	{
		global $db;
		
		$width = (int) $width;
		$height = (int) $height;
		
		$validControlQuery = $db->query('SELECT cid FROM p'.$this->pid.'Control WHERE cid='.escape($this->cid).' AND type="ImageControl" LIMIT 1');
		
		if ($validControlQuery->num_rows < 1)
		{
			echo gettext('Invalid Control ID');
		}
		else if ($width < 1 || $height < 1)
		{
			echo gettext('Width and Height must be Positive Integers');
		}
		else if ($width > ImageControl::$maxThumbWidth)
		{
			echo gettext('Maximum Width').': '.ImageControl::$maxThumbWidth.' '.gettext('pixels').'.';
		}
		else if ($height > ImageControl::$maxThumbHeight)
		{
			echo gettext('Maximum Height').': '.ImageControl::$maxThumbHeight.' '.gettext('pixels').'.';
		}
		else
		{
			// NO XMLESCAPE BECAUSE IT'S ALREADY CASTED ABOVE
			$this->SetExtendedOption('thumbWidth', $width );
			$this->SetExtendedOption('thumbHeight', $height );
			
			// Purge all existing thumbnails for this scheme in case they're the
			// wrong size
			
			// DO THIS FOR PUB INGEST LOCATION
			//temporary storage for publically ingested files to be approved
			$fileDirectory = awaitingApprovalFileDir;
			$thumbDir = basePath.$fileDirectory.$this->pid.'/'.$this->sid.'/thumbs/';
			$fileHandle = '/^'.dechex($this->pid).'-'.dechex($this->sid).'-/';
			
			if ($dir = opendir($thumbDir))
			{
				while ($f = readdir($dir))
				{
					// If the image is from this scheme, delete it
					if (preg_match($fileHandle, $f))
					{
						unlink($thumbDir.$f);
					}
				}
			}
			else
			{
				Manager::PrintErrDiv(gettext('Unable to open thumbnail directory'));
			}
			
			// AND THE SAME THING FOR STANDARD THUMB LOCATION
			//default file directory, ingested from within KORA
			$fileDirectory = fileDir;
			$thumbDir = basePath.$fileDirectory.$this->pid.'/'.$this->sid.'/thumbs/';
			$fileHandle = '/^'.dechex($this->pid).'-'.dechex($this->sid).'-/';
			
			if ($dir = opendir($thumbDir))
			{
				while ($f = readdir($dir))
				{
					// If the image is from this scheme, delete it
					if (preg_match($fileHandle, $f))
					{
						unlink($thumbDir.$f);
					}
				}
			}
			else
			{
				Manager::PrintErrDiv(gettext('Unable to open public thumbnail directory'));
			}
			
			
			echo gettext('Thumbnail Size Updated').'<br /><br />';
			
		}
	}
}


?>
