<?php
/*
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

// THIS IS ONE OF THE ONLY PAGES IN KORA THAT CAN WORK BY PASSING IN
// ONLY AN RID TO THE QUERYSTRING, SO WE PARSE THAT DATA AND 'HACK' THE
// REQUEST VARIABLE LOCAL TO THIS PAGE ONLY, TRY TO AVOID THIS TECHNIQUE
// ELSEWHERE AND JUST PASS PID= & SID= TO OTHER PAGES INSTEAD

// WE CAN'T WRITE OUTPUT AT THIS POINT OR REQUIRELOGIN/ETC WON'T REDIRECT
// SO SAVE POTENTIAL ERRORS TO ARRAY AND WRITE OUT LATER
$preiniterrors = [];
$recordInfo = null;
$showrecord = true;
// make sure a record id has been passed
if (!Manager::CheckRequestsAreSet(['rid'])) {
	$preiniterrors[] = gettext('No record ID specified').'.';
}
else
{
	// make sure the rid is valid
	$recordInfo = Record::ParseRecordID(trim($_REQUEST['rid']));
	if ($recordInfo === false) {
		$preiniterrors[] = gettext('Invalid Record ID format').'.';
	}
	$_REQUEST['pid'] = $recordInfo['project'];
	$_REQUEST['sid'] = $recordInfo['scheme'];
}

Manager::Init();

Manager::RequireLogin();

// NOW WE CAN BEGIN TO WRITE THINGS OUT, SO PRECEEDING ERRORS
foreach ($preiniterrors as $err)
{ Manager::PrintErrDiv($err); $showrecord=false; }


// MAKE SURE THE USER HAS SOME RIGHTS TO PROJECT, DON'T USE 'REQUIREPROJECT' OR
// ANYTHING THAT WILL REDIRECT THE USER FOR THE SAKE OF LINKS AND SUCH
$e = '';
if (!Manager::GetProject())
{
	$e = gettext('Invalid Project/Scheme ID').'.';
	$showrecord=false;
}
else if(Manager::GetProject()->GetUserPermissions() <= 0){
	$e = gettext('You do not have permissions to view this record').'.';
	$showrecord=false;
}

if (!Manager::GetRecord() || !Manager::GetRecord()->HasData())
{
	$e = gettext('Record not found or failed to load').'.';
	$showrecord=false;
}

//If a record errors out in global search or other context, we will bounce to cross project search
//Potentially might want to bounce elsewhere.
if(!$showrecord){
	header("Location: ".baseURI."selectProject.php?err=1"); 
}


if ($showrecord)
{
	Manager::PrintHeader();
	
	echo '<h2>'.gettext('Viewing Record').': '.$_REQUEST['rid'].'</h2>';
	
	// overall pseudocode:
	
	// get list of all controls for the scheme of which this object is a part
	// instantiate all those controls with the object's record identifier and 
	// call the showData() method on them to display the values of the object.
	// note that ALL fields, even empty ones, are to be shown.
	
	// Get list of controls for scheme of which it's a part
	$cTable = 'p'.$recordInfo['project'].'Control';
	$controlQuery  = "SELECT $cTable.cid AS cid, $cTable.name AS name, $cTable.type AS class, ";
	$controlQuery .= "control.file AS file FROM $cTable LEFT JOIN control";
	$controlQuery .= " ON control.class = $cTable.type ";
	$controlQuery .= " LEFT JOIN collection ON ($cTable.collid = collection.collid)";
	$controlQuery .= " WHERE $cTable.schemeid=".$recordInfo['scheme'];
	$controlQuery .= " ORDER BY collection.sequence, $cTable.sequence";
	$controlQuery = $db->query($controlQuery);
	
	if (Manager::GetUser()->HasProjectPermissions(INGEST_RECORD, Manager::GetRecord()->GetPID()))
	{echo ' <a href="editObject.php?pid='.Manager::GetRecord()->GetPID().'&sid='.Manager::GetRecord()->GetSID().'&rid='.Manager::GetRecord()->GetRID().'">'.gettext(' edit').'</a> | ';}
	if (Manager::GetUser()->HasProjectPermissions(DELETE_RECORD, Manager::GetRecord()->GetPID()))
	{echo '<a href="deleteObject.php?pid='.Manager::GetRecord()->GetPID().'&sid='.Manager::GetRecord()->GetSID().'&rid='.Manager::GetRecord()->GetRID().'">'.gettext('delete').'</a> | ';}
	if (Manager::GetUser()->HasProjectPermissions(INGEST_RECORD, Manager::GetRecord()->GetPID()))
	{echo '<a href="ingestObject.php?pid='.Manager::GetRecord()->GetPID().'&sid='.Manager::GetRecord()->GetSID().'&preset='.Manager::GetRecord()->GetRID().'">'.gettext('clone').'</a><br />';}
	
	Manager::GetRecord()->PrintRecordView(true);
	
	if (Manager::GetUser()->HasProjectPermissions(INGEST_RECORD, Manager::GetRecord()->GetPID()))
	{echo ' <a href="editObject.php?pid='.Manager::GetRecord()->GetPID().'&sid='.Manager::GetRecord()->GetSID().'&rid='.Manager::GetRecord()->GetRID().'">'.gettext(' edit').'</a> | ';}
	if (Manager::GetUser()->HasProjectPermissions(DELETE_RECORD, Manager::GetRecord()->GetPID()))
	{echo '<a href="deleteObject.php?pid='.Manager::GetRecord()->GetPID().'&sid='.Manager::GetRecord()->GetSID().'&rid='.Manager::GetRecord()->GetRID().'">'.gettext('delete').'</a> | ';}
	if (Manager::GetUser()->HasProjectPermissions(INGEST_RECORD, Manager::GetRecord()->GetPID()))
	{echo '<a href="ingestObject.php?pid='.Manager::GetRecord()->GetPID().'&sid='.Manager::GetRecord()->GetSID().'&preset='.Manager::GetRecord()->GetRID().'">'.gettext('clone').'</a><br />';}
	
	// If the user has authority to create presets, show that dialog
	if (Manager::GetUser()->HasProjectPermissions(EDIT_LAYOUT))
	{
	?>
	<br />
	<form id="preset" action="">
	<table class="table" id="AddPresetTable">
	<tr>
	    <td colspan="3"><strong><?php echo gettext('Save this record as a preset');?> </strong></td>
	</tr>
	<tr>    
	    <td style="width:25%"><?php echo gettext('Name');?>:</td><td colspan="2"><input type="text" id="presetName" /></td>
	</tr>
	<tr>    
	    <td></td><td style="width:25%"><input type="button" value="<?php echo gettext('Create');?>" class="preset_record_create" /></td><td style="width:50%"><div id="ajax"></div></td>
	</tr>
	<tr>
	    <td colspan="3"><?php echo gettext('Note: Records saved as presets will no longer appear in search results or as distinct records.');?></td>
	</tr>
	</table>
	</form>
	<?php 
	}
	
	Manager::PrintFooter();
}



?>
