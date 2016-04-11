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
along with this program.  If not, see <http://www.gnu.org/licenses/>.

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

 * In the messages.po files, the date formats ("MM DD, YYYY", etc.) must stay
 * in the same format. The letters may change according to language but they 
 * must stay in the same positions.

   When creating a new message.po file with xgettext -n *.php, 
 * 		you must list the following files:  ((relative to any LC_MESSAGES folder))
 * 			../../../controls/associatorControl.php
 * 			../../../controls/dateControl.php
 * 			../../../controls/fileControl.php
 * 			../../../controls/geolocatorControl.php
 * 			../../../controls/imageControl.php
 * 			../../../controls/listControl.php
 * 			../../../controls/multiDateControl.php
 * 			../../../controls/multiListControl.php
 * 			../../../controls/multiTextControl.php
 * 			../../../controls/textControl.php
 *			../../../includes/dublinFunctions.php
 *			../../../includes/fixity.php
 * 			../../../includes/footer.php
 * 			../../../includes/header.php
 *			../../../includes/ingestionClass.php
 *			../../../includes/koraSearch.php
 * 			../../../includes/menu.php
 *			../../../includes/presetFunctions.php
 *			../../../includes/projectFunctions.php
 *			../../../includes/required.php
 *			../../../includes/searchTokenFunctions.php
 *			../../../includes/utilities.php
 *			../../../accountActivate.php
 * 			../../../accountLogin.php
 * 			../../../accountRegister.php
 *  			../../../accountRecoverPassword.php
 * 			../../../accountResetPassword.php
 *			../../../accountSettings.php
 *			../../../addCollection.php
 *			../../../addScheme.php
 *			../../../crossProjectSearch.php
 *			../../../crossProjectSearchResults.php
 *			../../../deleteObject.php
 *			../../../editCollection.php
 *			../../../editControl.php
 *			../../../editOptions.php
 * 			../../../importMultipleRecords.php
 * 			../../../importScheme.php
 *			../../../index.php
 * 			../../../ingestApprovedData.php
 *			../../../ingestObject.php
 *			../../../ingestRecord.php
 * 			../../../manageAssocPerms.php
 * 			../../../manageControlPresets.php
 * 			../../../manageDublinCore.php
 * 			../../../manageProjects.php
 * 			../../../manageProjectPerms.php
 * 			../../../manageRecordPresets.php
 * 			../../../manageSearchTokens.php
 * 			../../../manageUsers.php
 * 			../../../publicIngest.php
 *  			../../../reviewPublicIngestions.php
 *  			../../../schemeExport.php
 *  			../../../schemeExportLanding.php
 * 			../../../schemeLayout.php
 * 			../../../searchProject.php
 * 			../../../searchProjectResults.php
 * 			../../../selectProject.php
 * 			../../../selectScheme.php
 * 			../../../systemManagement.php
 * 			../../../updateDublinCore.php
 *			../../../upgradeDatabase.php
 *			../../../viewObject.php
 *			../../../javascripts/gettext.js.php
 *
 *		Here is the list again for pasting in the terminal. (see the wiki)
		
 ../../../controls/associatorControl.php ../../../controls/dateControl.php ../../../controls/fileControl.php ../../../controls/geolocatorControl.php ../../../controls/imageControl.php ../../../controls/listControl.php ../../../controls/multiDateControl.php ../../../controls/multiListControl.php ../../../controls/multiTextControl.php ../../../controls/textControl.php ../../../includes/dublinFunctions.php ../../../includes/fixity.php ../../../includes/footer.php ../../../includes/header.php ../../../includes/ingestionClass.php ../../../includes/koraSearch.php ../../../includes/menu.php ../../../includes/presetFunctions.php ../../../includes/projectFunctions.php ../../../includes/required.php ../../../includes/searchTokenFunctions.php ../../../includes/utilities.php ../../../accountSettings.php ../../../accountActivate.php ../../../addCollection.php ../../../addScheme.php ../../../crossProjectSearch.php ../../../crossProjectSearchResults.php ../../../deleteObject.php ../../../editCollection.php ../../../editControl.php ../../../editOptions.php ../../../importMultipleRecords.php ../../../importRecord.php ../../../importScheme.php ../../../index.php ../../../ingestApprovedData.php ../../../ingestObject.php ../../../accountLogin.php ../../../manageAssocPerms.php ../../../manageControlPresets.php ../../../manageDublinCore.php ../../../manageProjects.php ../../../manageProjectPerms.php ../../../manageRecordPresets.php ../../../manageSearchTokens.php ../../../manageUsers.php ../../../publicIngest.php ../../../accountRecoverPassword.php ../../../accountRegister.php ../../../accountResetPassword.php ../../../reviewPublicIngestions.php ../../../schemeExport.php ../../../schemeExportLanding.php ../../../schemeLayout.php ../../../searchProject.php ../../../searchProjectResults.php ../../../selectProject.php ../../../selectScheme.php ../../../systemManagement.php ../../../updateDublinCore.php ../../../upgradeDatabase.php ../../../viewObject.php ../../../javascripts/gettext.js.php
 
 */




// Initial Version: Cassia Miller, 2009
include_once('includes.php');

// list for use in the drop down menus for choosing the language
$locale_list = array(
	'en_US' => 'English',
	'de_DE' => 'German',
	'fr_FR' => 'Fran&#231ais');

$language='en_US';

if(isset($_REQUEST['language'])) $_SESSION['language'] = $_REQUEST['language'];
if(isset($_SESSION['language'])) $language=$_SESSION['language'];

// I18N support information here
putenv("LANGUAGE=$language"); 
setlocale(LC_ALL, $language . '.utf8');
// Set the text domain as 'messages'
$domain = 'messages';
bindtextdomain($domain, basePath."locale/"); 
textdomain($domain);
?>