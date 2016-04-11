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
// Refactor: Joe Deming, Anthony D'Onofrio 2013

// This ajax file handles all requests related to a Record Model

require_once(__DIR__.'/../includes/includes.php');

Manager::Init();

//Prints out a record in a viewable form
function ViewRecord($rid,$showall)
{
	$rparts = Record::ParseRecordID($rid);
	if ($rparts === false) { return false; }
	$r = new Record($rparts['pid'], $rparts['sid'], $rid);
	$r->PrintRecordView($showall);
}

if(Manager::CheckRequestsAreSet(['action', 'rid'])) {
	//Handles printing a view of the record
	if ($_REQUEST['action'] == 'viewRecord') {
		$showall = (Manager::CheckRequestsAreSet(['showall']) && $_REQUEST['showall'] == 'true') ? true : false;
		print ViewRecord($_REQUEST['rid'],$showall);
	//Handles deletion of a record
	} else if ($_REQUEST['action'] == 'deleteRecord' && Manager::CheckRequestsAreSet(['pid','sid'])) {
		$r = new Record($_REQUEST['pid'], $_REQUEST['sid'], $_REQUEST['rid']);
		$r->Delete();
	}
}

if(Manager::CheckRequestsAreSet(['action', 'source']) && $_REQUEST['source'] == 'PresetFunctions'){
    $action = $_REQUEST['action'];
	//Handles addition of a record preset
    if($action == 'addRecordPreset') {
    	Record::addRecordPreset($_REQUEST['kid'], $_REQUEST['name'], $_REQUEST['sid']);
    } 
    
    //Handles demoting of a record preset
    elseif($action == 'demoteRecordPreset') {
    	Record::demoteRecordPreset($_REQUEST['kid'],$_REQUEST['sid']);	
    } 
    
    //Handles renaming of a record preset
    elseif($action == 'renameRecordPreset') {
    	Record::renameRecordPreset($_REQUEST['kid'], $_REQUEST['name'],$_REQUEST['sid']);
    } 
    
    //Handles printing of the record preset form
    elseif($action == 'PrintForm') {
    	Record::PrintRecordPresetsForm($_REQUEST['sid'], $_REQUEST['pid']);
    } 
}

?>