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
along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Initial Version: Meghan McNeil, 2009
// Refactor: Joe Deming, Anthony D'Onofrio 2013

require_once('includes/includes.php');

Manager::Init();
Manager::AddJS('javascripts/MappingManager.js', Manager::JS_CORE); 

Manager::RequireLogin();
Manager::RequireProject();
Manager::RequireScheme();

Manager::PrintHeader();

?>

<script type="text/javascript">
function cancelIngestion() {
	window.location = "importMultipleRecords.php?pid=<?php echo $_REQUEST['pid']?>&sid=<?php echo $_REQUEST['sid']?>";
}
</script>

<h2><?php echo gettext('Upload an XML file');?></h2>  

<?php 
// display results instead of trying to import again 
if(@$_REQUEST['page']=='results' && !empty($_SESSION['ob'])){
	echo $_SESSION['ob'];
	Manager::PrintFooter();
	die;
}

print '<div id="ingestXMLerror"></div>';
print '<div id="xmlActionDisplay">';

$pid = Manager::GetProject()->GetPID();
$sid = Manager::GetScheme()->GetSID();


unset($_SESSION['ob']);
Scheme::PrintImportRecordsForm();

print '</div>';

print '<div id="ingestprogress"></div>';
Manager::PrintFooter();
?>

