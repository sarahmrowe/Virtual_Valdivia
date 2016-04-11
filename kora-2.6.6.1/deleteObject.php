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
require_once('includes/includes.php');

Manager::Init();

Manager::RequireLogin();

if( !Manager::CheckRequestsAreSet(['deleted'])){
	Manager::RequireRecord();

	// Make sure the user has permissions to delete this object
	if (!Manager::GetUser()->HasProjectPermissions(DELETE_RECORD, Manager::GetRecord()->GetPID()))
	{
		header('Location: schemeLayout.php');
		die();
	}

	Manager::PrintHeader();

	$rid = Manager::GetRecord()->GetRID();
	echo '<h2>'.gettext('Delete Record').': '.$rid.'</h2>';

	echo gettext('Warning').': '.gettext('This will permanently delete all data within this record. Are you sure you really want to delete this record?').'<br /><br />';

	if (sizeof(Manager::GetRecord()->GetAssociatedRecords()) > 0)
	{
		Manager::PrintErrDiv(gettext('Warning').': '
			.gettext('At least one record associates to this record.  Any such associations will be lost if this record is deleted.')
			.'<br /><br />');
	}

	Manager::GetRecord()->PrintRecordDeleteForm();

	Manager::GetRecord()->PrintRecordView();

}
else{
	Manager::PrintHeader();
	
	echo '<h2>'.gettext('Delete Record').'</h2>';
	echo gettext('Record successfully deleted.');
}

Manager::PrintFooter();
?>