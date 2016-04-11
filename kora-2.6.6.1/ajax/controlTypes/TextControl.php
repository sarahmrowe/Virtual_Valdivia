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

//Ajax calls for TextControl
if(Manager::CheckRequestsAreSet(['action', 'source', 'pid', 'cid']) && $_REQUEST['source'] == 'TextControl')
{
	$action = $_REQUEST['action'];
	$ctrlopts = Manager::GetControl($_REQUEST['pid'], $_REQUEST['cid']);
	//Handles updating regular expression rules for TC
	if($action == 'updateRegEx' && Manager::CheckRequestsAreSet(['regex'])) {
		$ctrlopts->updateRegEx($_REQUEST['regex']);
	} 
	//Handles updating default value for TC
	else if($action == 'updateDefaultValue' && Manager::CheckRequestsAreSet(['defaultV'])) {
		$ctrlopts->updateDefaultValue($_REQUEST['defaultV']);
	} 
	//Handles updating text size of TC
	else if ($action == 'updateSize' && Manager::CheckRequestsAreSet(['rows','cols'])) {
		$ctrlopts->updateSize($_REQUEST['rows'], $_REQUEST['cols']);
	} 
	//Handles setting of a preset to a TC
	else if ($action == 'usePreset' && Manager::CheckRequestsAreSet(['preset'])) {
		$ctrlopts->usePreset($_REQUEST['preset']);
	} 
	//Handles saving a new preset from a TC
	else if ($action == 'savePreset' && Manager::CheckRequestsAreSet(['name','regex'])) {
		$ctrlopts->savePreset($_REQUEST['name'], $_REQUEST['regex']);
	} 
	//Handles updating which editor is used by a TC
	else if ($action == 'updateEditor' && Manager::CheckRequestsAreSet(['editor'])) {
		$ctrlopts->updateEditor($_REQUEST['editor']);
	} 
	//Handles printing of control options for TC
	else if ($action == 'showDialog') {
		Manager::GetControl($_REQUEST['pid'], $_REQUEST['cid'])->PrintControlOptions();
	}
}
?>