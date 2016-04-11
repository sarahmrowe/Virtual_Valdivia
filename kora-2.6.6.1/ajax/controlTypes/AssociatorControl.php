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

//Ajax calls for AssociatorControl

if(Manager::CheckRequestsAreSet(['action', 'source','pid','cid']) && $_REQUEST['source'] == 'AssociatorControl')
{
	$action = $_REQUEST['action'];
	$ctrlopts = Manager::GetControl($_REQUEST['pid'], $_REQUEST['cid']);
	//Handle printing of control options for RAC
	if($action == 'showDialog') {
		Manager::GetControl($_REQUEST['pid'], $_REQUEST['cid'])->PrintControlOptions();
	} 
	//Handles saving of default value for RAC
	else if ($action == 'saveDefault' && Manager::CheckRequestsAreSet(['values'])) {
		$ctrlopts->updateDefaultValue($_REQUEST['values']);
	} 
	//Handles updating of control preview for RAC
	else if ($action == 'updatePreviewControl' && Manager::CheckRequestsAreSet(['prevcid', 'prevval'])) {
		$ctrlopts->UpdatePreview($_REQUEST['prevcid'], $_REQUEST['prevval']);
	} 
	//Handle updating of allowed schemes for RAC
	else if ($action == 'updateAllowedSchemes') {
		$schemes = Manager::CheckRequestsAreSet(['schemes']) ? $_REQUEST['schemes'] : array();
		$ctrlopts->UpdateSearchSchemes($schemes);
	}
}
?>