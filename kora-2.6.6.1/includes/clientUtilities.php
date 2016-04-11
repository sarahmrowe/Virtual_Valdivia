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
 * These utilities are designed to be useful to developing front-end websites which utilize
 * data stored in a KORA Repository.
 * 
 */

// Initial Version: Brian Beck, 2008

/**
 * Get the full path to a file from its filename.  Expects a valid internal localName
 * From KORA.
 * 
 * @param string $filename
 */
function getFullURLFromFileName($filename)
{
	$fileParts = explode('-', $filename);
	$pid = hexdec($fileParts[0]);
	$sid = hexdec($fileParts[1]);
	
	return baseURI.fileDir.$pid.'/'.$sid.'/'.rawurlencode($filename);
}

/**
 * Get the full path to a file from its filename.  Expects a valid internal localName
 * From KORA.
 * 
 * @param string $filename
 */
function getFullPathFromFileName($filename)
{
    $fileParts = explode('-', $filename);
    $pid = hexdec($fileParts[0]);
    $sid = hexdec($fileParts[1]);
    
    return basePath.fileDir.$pid.'/'.$sid.'/'.$filename;
}

/**
 * Get the full path to a file from its filename.  Expects a valid internal localName
 * From KORA
 * 
 * @param string $filename
 */
function getThumbURLFromFileName($filename)
{
    $fileParts = explode('-', $filename);
    $pid = hexdec($fileParts[0]);
    $sid = hexdec($fileParts[1]);
    
    return baseURI.fileDir.$pid.'/'.$sid.'/thumbs/'.rawurlencode($filename);   
}


/**
 * Given a Record ID and Control ID, gets the local filename
 * If bad inputs given, returns null
 *
 * @param string $rid
 * @param string $cid
 * 
 * @return string
 */
function getURLFromRecordID($rid, $cid)
{
    global $db;
    
    $recordInfo = Record::ParseRecordID($rid);

    $pquery = 'SELECT value FROM p'.$recordInfo['pid'].'Data WHERE id='.escape($rid).' AND cid='.escape($cid).' LIMIT 1';
    $query = $db->query($pquery);
    
    // make sure data was returned
    if($query->num_rows != 1) return '';
    
    $fileInfo = $query->fetch_assoc();
    $fileInfo = simplexml_load_string($fileInfo['value']);
    
    return baseURI.fileDir.$recordInfo['project'].'/'.$recordInfo['scheme'].'/'.rawurlencode($fileInfo->localName);
}

/**
 * Given a Project ID, Scheme ID, Control ID, and Record ID, 
 * gets the local filename. (this function is for records that have
 * been ingested publicly and have not been approved yet by an admin.)
 * If bad inputs given, returns null
 *
 * @param string $pid
 * @param string $sid
 * @param string $cid
 * @param string $rid
 * 
 * @return string
 */
function publicGetURLFromRecordID($pid, $sid, $cid, $rid)
{
	global $db;
	
	$query = 'SELECT value FROM p'.$pid.'PublicData WHERE id='.escape($rid).' AND cid='.escape($cid).' LIMIT 1';
    $query = $db->query($query);
    
    // make sure data was returned
    if($query->num_rows != 1) return '';
    
    $fileInfo = $query->fetch_assoc();
    $fileInfo = simplexml_load_string($fileInfo['value']);
    
	return baseURI.awaitingApprovalFileDir.$pid.'/'.$sid.'/'.rawurlencode($fileInfo->localName);
}

/**
 * Gets a list of all projects a token has permission to search, as well
 * as all schemes in the project.  Returns both names and ids.
 *
 * @param String $token - a search token
 * @return Array - array in the following format:
 * array('project name'=>array(
 *			'pid'=>pid,
 *			'schemes'=>array(
 *				'scheme name 1'=>schemeid,
 *				'scheme name 2'=>schemeid,...)))
 */
function getProjectInfoFromToken($token){
	global $db;

    $validProjects = array();

    $projectQuery = $db->query('SELECT member.pid AS pid, project.name AS name FROM member LEFT JOIN user USING (uid) LEFT JOIN project USING (pid) WHERE user.password='.escape($token));
	if($db->error){
//		echo $db->error."<br/>";
//		echo $projectQuery."<br/>";
		return array();
	}
    while($result = $projectQuery->fetch_assoc())
    {
        $validProjects[$result['name']] = array('pid'=>$result['pid']);
		$validProjects[$result['name']]['schemes']=array();
		$schemeQuery = $db->query('SELECT schemeid,schemeName FROM scheme WHERE pid='.$result['pid']);
		while($schemeResult = $schemeQuery->fetch_assoc()){
			$validProjects[$result['name']]['schemes'][$schemeResult['schemeName']] = $schemeResult['schemeid'];
		}
    }
	return $validProjects;
}

?>
