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

// This ajax file handles all requests related to a Scheme Model 

require_once(__DIR__.'/../includes/includes.php');

Manager::Init();

if (Manager::CheckRequestsAreSet(['action', 'source']) &&
	($_REQUEST['source'] == 'SchemeFunctions') &&
	Manager::GetUser())
{
	switch ($_REQUEST['action'])
	{
	//Handles the printing of a scheme layout
	case 'PrintSchemeLayout':
		if (Manager::GetProject() && Manager::GetScheme()) 
		{ Manager::GetScheme()->PrintSchemeLayout(); }
		break;
	//Handles the printing of the add control form
	case 'PrintAddControl':
		if (Manager::GetProject() && Manager::GetScheme() && Manager::CheckRequestsAreSet(['newcollid'])) 
		{ Manager::GetScheme()->PrintAddControl($_REQUEST['newcollid']); }
		break;
	//Handles the printing of the add collection form
	case 'PrintAddCollection':
		if (Manager::GetProject() && Manager::GetScheme()) 
		{ Manager::GetScheme()->PrintAddCollection(); }
		break;
	//Handles the printing of the update collection form
	case 'PrintUpdateCollection':
		if (Manager::GetProject() && Manager::GetScheme() && Manager::CheckRequestsAreSet(['collid'])) 
		{ Manager::GetScheme()->PrintUpdateCollection(escape($_REQUEST['collid'],false)); }
		break;
	//Handles the printing of the set allowed associations form
	case 'PrintSetAllowedAssoc':
		if (Manager::GetProject() && Manager::GetScheme()) 
		{ Manager::GetScheme()->PrintAssocSetAllowedSchemes(); }
		break;
	//Handles the printing of the allowed associations form
	case 'PrintAllowedAssoc':
		if (Manager::GetProject() && Manager::GetScheme()) 
		{ Manager::GetScheme()->PrintAssocAllowedSchemes(); }
		break;
	//Handles creation of a control
	case 'CreateControl':
		if (Manager::GetProject() && Manager::GetScheme()) 
		{ Manager::GetScheme()->CreateControl(true); }
		break;
	//Handles creation of a collection
	case 'CreateCollection':
		if (Manager::GetProject() && Manager::GetScheme() && Manager::CheckRequestsAreSet(['addGroup'])) 
		{ Manager::GetScheme()->CreateCollection(); }
		break;
	//Handles updating a collection
	case 'UpdateCollection':
		if (Manager::GetProject() && Manager::GetScheme() && Manager::CheckRequestsAreSet(['collid', 'name','description'])) 
		{ Manager::GetScheme()->UpdateCollection($_REQUEST['collid'], $_REQUEST['name'],$_REQUEST['description']); }
		break;
	//Handles moving a collection within the scheme layout
	case 'MoveSchemeCollection':
		if (Manager::GetScheme() && Manager::CheckRequestsAreSet(['movecid', 'direction'])) 
		{ Manager::GetScheme()->MoveCollection($_REQUEST['movecid'], $_REQUEST['direction']); }
		break;
	//Handles moving a control within the scheme layout
	case 'MoveSchemeControl':
		if (Manager::GetScheme() && Manager::CheckRequestsAreSet(['movecid', 'direction'])) 
		{ Manager::GetScheme()->MoveControl($_REQUEST['movecid'], $_REQUEST['direction']); }
		break;
	//Handles deletion of a scheme collection
	case 'DeleteSchemeCollection':
		if (Manager::GetScheme() && Manager::CheckRequestsAreSet(['delcid'])) 
		{ Manager::GetScheme()->DeleteCollection($_REQUEST['delcid']); }
		break;
	//Handles deletion of a scheme control
	case 'DeleteSchemeControl':
		if (Manager::GetScheme() && Manager::CheckRequestsAreSet(['delcid'])) 
		{ Manager::GetScheme()->DeleteControl($_REQUEST['delcid']); }
		break;
	//Handles updating the scheme preset
	case 'UpdateSchemePreset':
		if (Manager::GetScheme() && Manager::CheckRequestsAreSet(['preset'])) 
		{ Manager::GetScheme()->UpdateSchemePreset($_REQUEST['preset']); }
		break;
	//Handles adding an allowed association
	case 'AddAllowedAssoc':
		if (Manager::GetProject() && Manager::GetScheme() && Manager::CheckRequestsAreSet(['addpid', 'addsid'])) 
		{ Manager::GetScheme()->AddAllowedAssociation($_REQUEST['addpid'],$_REQUEST['addsid']); }
		break;
	//Handles removing an allowed Association
	case 'DeleteAllowedAssoc':
		if (Manager::GetProject() && Manager::GetScheme() && Manager::CheckRequestsAreSet(['delpid', 'delsid'])) 
		{ Manager::GetScheme()->DeleteAllowedAssociation($_REQUEST['delpid'],$_REQUEST['delsid']); }
		break;
	//Handles retreiving of the record actions
	case 'GetRecordActions':
		if (Manager::GetProject() && Manager::GetScheme()) 
		{ Manager::GetScheme()->PrintRecordActions(); }
		break;
	//Handles uploading of an xml scheme
	case 'SchemeXMLUpload':
		if (Manager::GetProject()) 
		{ Scheme::SubmitSchemeImport(); }
		break;
	//Handles uploading of multiple records
	case 'MultiRecordXMLUpload':
		if (Manager::GetProject() && Manager::GetScheme()) 
		{ Manager::GetScheme()->SubmitMultiRecordImport(); }
		break;
	default:
		break;
	}
}
?>