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

//Ajax calls for FileControl
if(Manager::CheckRequestsAreSet(['action', 'source', 'pid', 'cid'])  && $_REQUEST['source'] == 'FileControl'){
	if(Manager::GetUser()->HasProjectPermissions(EDIT_LAYOUT)){
		$action = $_REQUEST['action'];
		$ctrlopts = Manager::GetControl($_REQUEST['pid'], $_REQUEST['cid']);
		//Handle updating for mime types for FC
		if($action == 'updateMimeTypes' && Manager::CheckRequestsAreSet(['mimetypes'])) {
			$ctrlopts->updateMimeTypes($_REQUEST['mimetypes']);
		} 
		//Handle updating file size limit for FC
		else if ($action == 'updateFileSize' && Manager::CheckRequestsAreSet(['maxsize'])) {
			$ctrlopts->updateFileSize($_REQUEST['maxsize']);
		} 
		//Handle updating of file type restrictions for FC
		else if ($action == 'updateFileRestrictions' && Manager::CheckRequestsAreSet(['restrict'])) {
			$ctrlopts->updateFileRestrictions($_REQUEST['restrict']);
		} 
		//Handle using of preset for FC
		else if ($action == 'usePreset' && Manager::CheckRequestsAreSet(['preset'])) {
			$ctrlopts->usePreset($_REQUEST['preset']);
		} 
		//Handle saving of new preset for FC
		else if ($action == 'savePreset' && Manager::CheckRequestsAreSet(['name'])) {
			$ctrlopts->savePreset($_REQUEST['name']);
		}
		//Handle updating of archiving for FC
		else if ($action == 'updateArchival' && Manager::CheckRequestsAreSet(['archival'])) {
			$ctrlopts->updateArchival($_REQUEST['archival']);
		} 
		//Handle printing of control options for FC 
		else if ($action == 'showDialog') {
			Manager::GetControl($_REQUEST['pid'], $_REQUEST['cid'])->PrintControlOptions();
		}
	}
	if(Manager::GetUser()->HasProjectPermissions(DELETE_RECORD)){
		$action = $_REQUEST['action'];
		$ctrlopts = Manager::GetControl($_REQUEST['pid'], $_REQUEST['cid']);
		if ($action == 'deleteFile' && Manager::CheckRequestsAreSet(['kid'])) {
			$ctrlopts->deleteFile($_REQUEST['kid']);
		} 
	}
}
?>