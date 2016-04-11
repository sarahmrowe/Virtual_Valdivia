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

require_once(__DIR__.'/includes/includes.php');

Manager::Init();

// Overall, this page should function and look very, very close to ingestObject.php
// the only difference should be that when instantiating controls, the record ID is
// passed as an argument.

// show the initial edit form if nothing was submitted
if (!Manager::CheckRequestsAreSet(['rid']))
{
        header('Location: schemeLayout.php');
        die();
}

$rid = $_REQUEST['rid'];

// make sure the rid is valid
$recordInfo = Record::ParseRecordID($rid);
if ($recordInfo === false)
{
    header('Location: schemeLayout.php');
    die();
}

Manager::RequireProject();
Manager::RequireScheme();
Manager::RequireRecord();
Manager::RequirePermissions(INGEST_RECORD, 'schemeLayout.php?pid='.Manager::GetProject()->GetPID().'&sid='.Manager::GetScheme()->GetSID());
	
// make sure the record's pid matches the project pid
if (Manager::GetProject()->GetPID() != Manager::GetRecord()->GetPID())
{
    header('Location: selectProject.php');
    die();
}
Manager::PrintHeader();

if (Manager::CheckRequestsAreSet(['ingestionForm'])) Manager::GetRecord()->ingest();
else {
	$controls = Manager::GetRecord()->GetControls();
	foreach($controls as $ctrl){
		$ctrl->validateIngestion();
	}
	Manager::GetRecord()->PrintRecordDisplay();
}

Manager::PrintFooter();

?>
