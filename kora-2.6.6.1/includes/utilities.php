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

/**
 * NOTE: ALL FUNCTIONS IN THIS FILE ASSUME THAT THEY ARE BEING CALLED WITH PROPER INPUT.
 * They perform no additional input validation, and such validation is the result of the
 * script using them!
 */

// Initial Version: Brian Beck, 2008
// Refactor: Joe Deming, Anthony D'Onofrio 2013

require_once('includes.php');
date_default_timezone_set('America/New_York');

/**
  * Creates a thumbnail version of a file
  *
  * @param file $origFile The file to be converted
  * @param string $destFile The destination for new thumbnail
  * @param int $maxWidth Max width of the thumbnail
  * @param int $maxHeight Max height of the thumbnail
  *
  * @return void
  */
function createThumbnail($origFile, $destFile, $maxWidth, $maxHeight)
{
    if ($maxWidth < 1 || $maxHeight < 1 || !file_exists($origFile) || getimagesize($origFile) === false)
    {
        return FALSE;
    }
    
    // Figure out the new dimensions
    $originalSize = getimagesize($origFile);
    $originalWidth = $originalSize[0];
    $originalHeight = $originalSize[1];
            
    // First attempt: Fit to width
    $scale = ((float)$maxWidth) / $originalWidth;
    $thumbWidth = $maxWidth;
    $thumbHeight = (int) ($originalHeight * $scale);
            
    // If the height is too large, fit to it instead
    if ($thumbHeight > $maxHeight)
    {
        $scale = ((float)$maxHeight) / $originalHeight;
        $thumbWidth = (int) ($originalWidth * $scale);
        $thumbHeight = $maxHeight;
    }
    
    if ($originalSize['mime'] == 'image/jpeg' || $originalSize['mime'] == 'image/pjpeg')
    {
        $imageCreateFunction = 'imagecreatefromjpeg';
        $imageFunction = 'imagejpeg';
    }
    else if ($originalSize['mime'] == 'image/gif')
    {
        $imageCreateFunction = 'imagecreatefromgif';
        $imageFunction = 'imagegif';
    }
    else if ($originalSize['mime'] == 'image/png' || $originalSize['mime'] == 'image/x-png')
    {
        $imageCreateFunction = 'imagecreatefrompng';
        $imageFunction = 'imagepng';
    }
    else if ($originalSize['mime'] = 'image/bmp')
    {
        $imageCreateFunction = 'imagecreatefromwbmp';
        $imageFunction = 'imagewbmp';
    }
            
    // Create the Image
    $originalImage = $imageCreateFunction($origFile);
    $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);
    imagecopyresampled($thumbnail, $originalImage, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $originalWidth, $originalHeight);
    imagedestroy($originalImage);
            
    $imageFunction($thumbnail, $destFile);
    imagedestroy($thumbnail);
}

/**
  * Escapes a sting to remove unwanted processing of a text
  *
  * @param string $rawString The string to be escaped
  * @param bool $addQuotes Allows the addition of quotes to the string
  *
  * @return escaped string
  */
function escape($rawString, $addQuotes=true) {
    global $db;
    if ($addQuotes==true){
        return "'".$db->real_escape_string($rawString)."'";
    } else {
        return $db->real_escape_string($rawString);
    }
}

/**
  * Extracts files from a zip folder
  *
  * @param string $tmpZipFolder Temporary name of the zip folder
  * @param string $originalName Original name of the zip folder
  *
  * @return extracted files
  */
function extractZipFolder($tmpZipFolder,$originalName) {
	$zip = new ZipArchive();
	$extractedFiles = array();
	if ($zip->open($tmpZipFolder)) {
		//get folder name
		$pathInfo = pathinfo($originalName);
		$folderName = $pathInfo['filename'];
		if ($zip->extractTo(basePath.extractFileDir) && $files = scandir(basePath.extractFileDir)) {
			//get files in extracted folder
			
			
			foreach ($files as $file){
				if ($file == "." || $file == "..") continue;
				rename(basePath.extractFileDir."/".$file, basePath.extractFileDir."/".$file);
				$extractedFiles[]=$file;
			}

			
			//return $extensionNames;
		} else {
			print '<div class="error">'.gettext('Could not extract the zip folder. Please try again.').'</div>';
			$extractedFiles=false;
		}
		$zip->close();
	} else {
		print '<div class="error">'.gettext('There was trouble opening the zip folder.  Please try again.').'</div>';
		$extractedFiles=false;
	}
	return $extractedFiles;
}

/**
  * Gets a list of all available control types
  *
  * @return list of control types
  */
function getControlList()
{
    global $db;

    $controlList = array();
    
    $result = $db->query("SELECT name, file, class FROM control ORDER BY name");
    while($r = $result->fetch_assoc()) { 
    	$controlList[] = $r; 
    }
    
    return $controlList;
}

/**
  * Gets a list of all dublin core fields for a given scheme
  *
  * @param int $schemeid Scheme ID
  * @param int $projectid Project ID
  *
  * @return list of dublin core fields
  */
function getDublinCoreFields($schemeid,$projectid) {
   global $db;
   if($schemeid <= 0 || $projectid <= 0)   return 0;
   $query = "SELECT dublinCoreFields FROM scheme WHERE schemeid=$schemeid AND pid=$projectid AND dublinCoreFields IS NOT NULL LIMIT 1";
   $results = $db->query($query);
   if($results->num_rows<=0)  return 0;
   $array = $results->fetch_assoc();
   $dcxml = simplexml_load_string($array['dublinCoreFields']);
   $dcfields = array();
   foreach($dcxml->children() as $dctypes) {
      if(count($dctypes->children() > 0 )) {
         $ids = array();
         foreach($dctypes->children() as $cids) {
            $ids[] = $cids;
         }
         if(count($ids) > 0)  //if tags are left by the remove calls, this will make sure only tags w/ ids are included
            $dcfields[$dctypes->getName()] = $ids;
      }
   }
   return $dcfields;
}

/**
  * Get the file name of a specific record in a file control
  *
  * @param int $rid The Record ID of the file
  * @param int $cid The Control ID of the file
  *
  * @return file directory path for the file
  */
function getFilenameFromRecordID($rid, $cid)
{
    global $db;
    
    $recordInfo = Record::ParseRecordID($rid);
    
    $query = 'SELECT value FROM p'.$recordInfo['project'].'Data WHERE id='.escape($rid).' AND cid='.escape($cid).' LIMIT 1';
    $query = $db->query($query);
    
    // make sure data was returned
    if($query->num_rows != 1) return '';
    
    $fileInfo = $query->fetch_assoc();
    $fileInfo = simplexml_load_string($fileInfo['value']);
    
    return basePath.fileDir.$recordInfo['project'].'/'.$recordInfo['scheme'].'/'.$fileInfo->localName;
}

/**
  * Get the file name of a specific record in a file control from a public record
  *
  * @param int $pid The Project ID of the file
  * @param int $sid The Scheme ID of the file
  * @param int $rid The Record ID of the file
  * @param int $cid The Control ID of the file
  *
  * @return file directory path for the file
  */
function publicGetFilenameFromRecordID($pid, $sid, $cid, $rid)
{
    global $db;
    
	$rid = (int)$rid;
    
    $query = 'SELECT value FROM p'.$pid.'PublicData WHERE id='.escape($rid).' AND cid='.escape($cid).' LIMIT 1';
    $query = $db->query($query);
    
    // make sure data was returned
    if($query->num_rows != 1) return '';
    
    $fileInfo = $query->fetch_assoc();
    $fileInfo = simplexml_load_string($fileInfo['value']);
    
    return basePath.awaitingApprovalFileDir.$pid.'/'.$sid.'/'.$fileInfo->localName;
}

/**
  * Produces the next record ID available for a particular scheme
  *
  * @param int $pid Project ID
  * @param int $sid Scheme ID
  *
  * @return new record ID
  */
function getNewRecordID($pid, $sid)
{
    global $db;
		
	$s = new Scheme($pid,$sid);
	
	$updateQuery = $db->query("UPDATE scheme SET nextid=nextid+1 WHERE schemeid=".escape($sid)." LIMIT 1");
	return  strtoupper(dechex($pid)).'-'
	.strtoupper(dechex($sid)).'-'
	.strtoupper(dechex($s->GetNextID()));
}
 
/**
  * Get the directory path of the thumbnail of the original file
  *
  * @param string $filename The original filename
  *
  * @return directory of the thumbnail
  */
function getThumbPathFromFileName($filename)
{
    $fileParts = explode('-', $filename);
    $pid = hexdec($fileParts[0]);
    $sid = hexdec($fileParts[1]);
    
    return basePath.fileDir.$pid.'/'.$sid.'/thumbs/'.$filename;
}

/**
  * Gets the permissions of all the tokens
  *
  * @param string $token The token to get the permissions for
  *
  * @return valid projects of the token
  */
function getTokenPermissions($token)
{
    global $db;
    
    $validProjects = array();
    
    $projectQuery = $db->query('SELECT member.pid AS pid FROM member LEFT JOIN user USING (uid) WHERE user.password='.escape($token));
    while($result = $projectQuery->fetch_assoc())
    {
        $validProjects[] = $result['pid'];
    }
    
    return $validProjects;
}

/**
  * Escapes html from a string
  *
  * @param string $rawString The string to be escaped
  *
  * @return the escaped string
  */
function htmlEscape($rawString) {
    return str_replace("\n", '<br />', htmlspecialchars($rawString, ENT_QUOTES, "UTF-8"));
}

/**
  * Resets the index for the next record ID
  *
  * @param int $schemeId The scheme ID where the reset is taking place
  *
  * @return true on success
  */
function resetNextRecordId($schemeId) {
	global $db;
	
    $updateQuery = $db->query("UPDATE scheme SET nextid=nextid-1 WHERE schemeid=".escape($schemeId)." LIMIT 1");
	if ($updateQuery) {
		return true;
	} else {
		return false;
	}
}

/**
  * Escapes xml from a string
  *
  * @param string $rawString string to be escaped
  *
  * @return the escaped string
  */
function xmlEscape($rawString)
{
    return str_replace(array('&', '<', '>', '"', "'"), array('&amp;','&lt;', '&gt;', '&quot;', '&apos;'), $rawString);
}

if(!function_exists('print_rr')){
	/**
	 * A wrapper function for print_r(). Adds <pre> tags and html escaping.
	 *
	 * @param Array $array
	 */
	function print_rr($array){

		echo "<pre>";
		ob_start();
		print_r($array);
		$text = ob_get_contents();
		ob_end_clean();
		echo htmlspecialchars($text);
		echo "</pre>";

	}
}

/**
  * Performs a boolean keyword on search
  *
  * @param string $string Keyword string to process
  * @param int $projectID Project ID 
  * @param int $schemeID Scheme ID
  * @param string $searchFields Fields to search
  * @param array[string] $keywords Keywords for the search
  *
  * @return void
  */
function booleanKeywordSearch($string,$projectID,$schemeID,$searchFields,&$keywords = array()){
	global $db;
	$booleanClause = '';

	// unescape the string because it may cause problems with double quotes.
	$string = stripslashes($string);
	$string = strtolower($string);
	
	// get quoted strings
	$matches = array();
	$pattern = '/"([^"]+)"/';
	$num = preg_match_all($pattern,$string,$matches);
	
	// replace quoted strings with empty qoutes as markers for later so
	// we can do functions that would otherwise affect these strings.
	$string = preg_replace($pattern,'"" ',$string);

	// remove AND because we will be treating spaces the same way.
	$string = str_ireplace(' and ',' ',$string);
	
	// condense all white space
	$string = trim(preg_replace('/[\s]+/',' ',$string));
	
	if($string != ''){
		// $n is a counter for quoted strings that we replaced
		$n=0;
		$or = explode(' or ',$string);
		foreach($or as &$and){
			$and = explode(' ',trim($and));
			
			foreach($and as &$keyword){
				$query = 'value ';
				if($keyword[0] == '-'){
					$query.= 'NOT ';
					$keyword = substr($keyword,1);
					// add quoted strings back in
					if($keyword == '""') $keyword = $matches[1][$n++];
				}
				else{
					// add quoted strings back in
					if($keyword == '""') $keyword = $matches[1][$n++];
					$keywords[]=$keyword;
				}

				
				
				$keyword = $db->real_escape_string($keyword);
				$query .="LIKE '%$keyword%'";
				$keyword = $query;
			}
			
			$and = '('.implode(' AND ',$and).')';
		}
		$booleanClause = implode(' OR ',$or);
	}


	// do the query and get a list of record ids.
	$recordQuery = "SELECT * FROM p{$projectID}Data WHERE schemeid='$schemeID' ";
	if (!empty($searchFields)){
		$recordQuery .= 'AND cid IN ('.implode(',',$searchFields).') ';
	}
	if($booleanClause != ''){
		$recordQuery .= 'AND ('.$booleanClause.') ';
	}
	//echo $recordQuery .'<br>';
	$recordQuery = $db->query($recordQuery);
	
	// add ids as array keys so we don't have to do an array_unique() later.
	$idList = array();
	while($r = $recordQuery->fetch_assoc()){
		$idList[$r['id']]='';
	}
	
	return array_keys($idList);
}

/**
  * Print error on mysql error
  *
  * @param string $sql_ SQL statement
  * @param string $okmsg_ Ok message
  * @param string $errmsg_ Error message
  *
  * @return error message
  */
function DoQueryPrintError($sql_, $okmsg_ = 'OK', $errmsg_ = 'ERROR')
{
	global $db;
	if ($db->query($sql_))
	{ return "<div class='noerror'>OK: $okmsg_</div>"; }
	else
	{ Manager::PrintErrDiv($errmsg_); }
}

/*

 */
function encodeValue($value)
{
	//Convert special chars to match the encoded values in the db.
	$encoded_keyword = preg_replace_callback('/[\x{80}-\x{10FFFF}]/u', function ($m) {
	$char = current($m);
	$utf = iconv('UTF-8', 'UCS-4', $char);
	return sprintf("&#x%s;", ltrim(strtoupper(bin2hex($utf)), "0"));
	}, $value);
	
	return $encoded_keyword;
}


// These translations are needed to display some strings in the correct language
// from the database without saving them in the database in non-English.
gettext('Record Associator');
gettext('Date');
gettext('Image');
gettext('List');
gettext('Date (Multi-Input)');
gettext('List (Multi-Select)');
gettext('Text (Multi-Input)');
gettext('Text');
gettext('Title');
gettext('Creator');
gettext('Subject');
gettext('Publisher');
gettext('Contributor');
gettext('Date Original');
gettext('Date Digital');
gettext('Format');
gettext('Source');
gettext('Coverage');
gettext('Rights');
gettext('Contributing Institution');
gettext('Geolocator');
gettext('Geolocator Control');


?>
