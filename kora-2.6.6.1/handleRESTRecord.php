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

header('Access-Control-Allow-Origin: *');

require_once(__DIR__.'/includes/includes.php');

Manager::Init();

if (!Manager::GetProject()) { die(gettext('PID was not submitted this is required for ingestion')); }
if (!Manager::GetScheme()) { die(gettext('SID was not submitted this is required for ingestion')); }

$pid = $_REQUEST['pid'];
$sid = $_REQUEST['sid'];

$rid = Manager::CheckRequestsAreSet(['rid']) ? $_REQUEST['rid'] : null;

$keyfieldMatch = false;
$recorddata = null;

echo gettext("Ingesting object ")."... ";

//ingest record data
$ingestion = new Record($pid,$sid,$rid);

if (Manager::CheckRequestsAreSet(['ingestdata']) && Manager::CheckRequestsAreSet(['ingestmap']))
{ $recorddata = $ingestion->GetImportData(); }

if (!$keyfieldMatch)
{
	
	if (!$ingestion->ingest($recorddata)) { die(gettext('Please fix errors and try again')); }
}
	

?>
