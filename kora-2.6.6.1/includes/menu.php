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
 */

// Initial Version: Brian Beck, 2008
// Revised: Cassia Miller, 2009 (added internationalization support)
// Refactor: Joe Deming, Anthony D'Onofrio 2013

require_once('includes.php');

include_once('gettextSupport.php');	// need to include this to access $locale_list

// HOLY COW, TAKING THESE MENUS TO ARRAY-DEFS HERE INSTEAD OF TERRIBLE TEXT-BLOBS THAT CAME OUT OTHERWISE
$menu_nologin = [
	'index.php' => gettext('Log In'),
	'accountRegister.php' => gettext('Register an Account'),
	'accountActivate.php' => gettext('Activate an Account')
	];

$menu_sysadmin = [
	'manageProjects.php' => gettext('Create/Modify Projects'),
	'manageSearchTokens.php' => gettext('Manage Search Tokens'),
	'manageUsers.php' => gettext('Manage Users'),
	'systemManagement.php' => gettext('System Management')
	];

$menu_proj = [
	'selectProject.php' => gettext('Select a Project')
	];
	
$menu_scheme = [
	'selectScheme.php' => gettext('Select a Scheme')
	];
	
$menu_record = [
	'searchResults.php' => gettext('List Scheme Records')
	];
	
$menu_search = [
];

$menu_plugin = [
];

$menu_pluginSettings = [
	'pluginSettings.php' => gettext('Plugin Settings')
];

// GET USER PERMS FOR CURRENT PROJECT
$userPermissions = (Manager::GetProject()) ? Manager::GetProject()->GetUserPermissions() : 0;

// HERE WE APPEND SOME ITEMS TO MENUS DEPENDING ON USER PERMISSIONS AND GLOBAL SETTINGS
if (Manager::GetProject()) 
	{ $menu_search['crossProjectSearch.php'] = gettext('Cross-Project Search'); }
if (Manager::GetProject() && Manager::GetScheme()) 
	{ $menu_search['advancedSearch.php'] = gettext('Scheme Search'); }
if (Manager::GetProject()) 
	{ $menu_search['searchProject.php'] = gettext('Project Search'); }
// WIERD ONE, HANDLE THIS OPTION BETTER?
if(@$solr_enabled) 
	{ $menu_search['koraFileSearch.php'] = gettext('KORA File Search'); }

if (Manager::IsProjectAdmin()) 
	{ $menu_record['schemeExportLanding.php'] = gettext('Export Data To XML'); }
if ((Manager::IsLoggedIn()) && (Manager::GetUser()->HasProjectPermissions(INGEST_RECORD))) 
	{ $menu_record['importMultipleRecords.php'] = gettext('Import Records from XML'); }
if ((Manager::IsLoggedIn()) && (Manager::GetUser()->HasProjectPermissions(INGEST_RECORD))) 
	{ $menu_record['ingestObject.php'] = gettext('Ingest Record'); }
if ((Manager::IsLoggedIn()) && (Manager::GetUser()->HasProjectPermissions(MODERATOR))) 
	{ $menu_record['reviewPublicIngestions.php'] = gettext('Review Public Ingestions'); }

if ((Manager::IsLoggedIn()) && (Manager::GetScheme())) 
	{ $menu_scheme['schemeLayout.php'] = gettext('Scheme Layout'); }
if ((Manager::IsLoggedIn()) && (Manager::GetUser()->HasProjectPermissions(CREATE_SCHEME))) 
	{ $menu_scheme['importScheme.php'] = gettext('Import Scheme From XML'); }
if ((Manager::IsLoggedIn()) && (Manager::GetUser()->HasProjectPermissions(EDIT_LAYOUT)) && (Manager::GetScheme())) 
	{ $menu_scheme['manageAssocPerms.php'] = gettext('Manage Associator Permissions'); }
if ((Manager::IsLoggedIn()) && (Manager::GetScheme()) && (Manager::GetUser()->HasProjectPermissions(EDIT_LAYOUT))) 
	{ $menu_scheme['manageRecordPresets.php'] = gettext('Manage Record Presets'); }
if (Manager::IsSystemAdmin() && (Manager::GetScheme())) 
	{ $menu_scheme['updateDublinCore.php'] = gettext('Refresh Dublin Core Data'); }
if ((Manager::IsLoggedIn()) && (Manager::GetScheme()) && (Manager::GetUser()->HasProjectPermissions(EDIT_LAYOUT))) 
	{ $menu_scheme['manageDublinCore.php'] = gettext('Manage Dublin Core Fields'); }

if (Manager::IsProjectAdmin()) 
	{ $menu_proj['manageControlPresets.php'] = gettext('Manage Control Presets'); }
if (Manager::IsProjectAdmin()) 
	{ $menu_proj['manageProjectPerms.php'] = gettext('Manage Project Permissions'); }
	
$nameArray = array();

foreach(Plugin::$plugins as $plugin){
	if($plugin['Enabled']==1){
		$menuArray = explode(",",$plugin['Menu']);
		
		$html = $plugin['FileName'] . '/';
		for($i = 0; $i < count($menuArray) ; $i++)
		{
			$keyVal = explode(" = ", $menuArray[$i]);
			$menu_plugin[baseURI."plugins/".$html.$keyVal[1]] = $keyVal[0];	
		}
		$nameArray[explode(",",$plugin['pluginName'])[0]] = $menu_plugin;
		unset($menu_plugin);
		$menu_plugin=array();
	}
}

// SORT ALL MENUS ALPHABETICALLY BEFORE DISPLAYING, SHOULD EVEN HANDLE FORIEGN LANGUAGE!
asort($menu_nologin);
asort($menu_sysadmin);
asort($menu_proj);
asort($menu_scheme);
asort($menu_record);
asort($menu_search);
asort($menu_plugin);
asort($nameArray);
asort($menu_pluginSettings);

// HERE IS THE ACTUAL OUTPUT
print "<div id='left'>\n";
if (!Manager::IsLoggedIn()) {
	PrintMenu(gettext('Accounts'), $menu_nologin);
	if(Manager::CheckRequestsAreSet(['lang'])){
		$_SESSION['language'] = $_REQUEST['lang'];
	}
	PrintLanguageDiv();
} else { 
	if (Manager::IsSystemAdmin()) {
		PrintMenu(gettext('Management'), $menu_sysadmin);
	}
	if (Manager::GetProject()) { PrintMenu(gettext('Search'), $menu_search); }
	if (Manager::GetProject() && Manager::GetScheme()) { PrintMenu(gettext('Record'), $menu_record); }
	if (Manager::GetProject()) { PrintMenu(gettext('Scheme'), $menu_scheme); }
	PrintMenu(gettext('Project'), $menu_proj);
	if (Plugin::PluginsExist()) { PrintMenu(gettext('Plugins'), $menu_pluginSettings); }
	if (Plugin::PluginsExist()) {
		
		foreach($nameArray as $key => $val){
			PrintMenu(gettext($key), $val, 'plugin ');
		}	
	}
}
print "</div> <!-- left -->\n";
// HELPER FUNCTIONS
function PrintMenu($title_, $items_)
{
	$path_parts = pathinfo($_SERVER["PHP_SELF"]);
	
	echo "<div class='ddblueblockmenu'>";
	echo "<div class='menutitle'>$title_</div>\n<ul>\n";
	foreach ($items_ as $url => $txt)
	{
		$noProj = array('manageProjects.php','manageSearchTokens.php','manageUsers.php','systemManagement.php');
		// ALWAYS APPEND GLOBAL PID/SID TO MENU ITEMS
		// 2.5: with the new quick jump, having them in the management pages can break it. Especially in project management where you disable a project
		if(!in_array($url,$noProj)){
			if (Manager::GetProject()) { $url .= '?pid='.Manager::GetProject()->GetPID(); }
			if (Manager::GetScheme()) { $url .= '&sid='.Manager::GetScheme()->GetSID(); }
		}
		// ALSO APPEND RID?
		echo "<li><a class='";
		if($path_parts['basename']==$url) echo "selected"; 
		else echo "normal";
		echo "' href='$url' >$txt</a></li>\n";
	}
	echo "</ul>";
	echo "</div>";
}

// THIS CAN REALLY BE CLEANED UP AND THE HANDLING TAKEN TO JQUERY
function PrintLanguageDiv()
{ 
	global $locale_list; 
	?><div class="language"><h3><?php echo gettext('change language');?></h3>
	<select class="kora_language_select">
	<?php
	// If a language isn't set, set it to the browser language if possible.
	if(!isset($_SESSION['language'])) 
	{
		$client_lang = substr($_SERVER["HTTP_ACCEPT_LANGUAGE"],0,5);
		if(stristr($client_lang, '-')) $client_lang = str_ireplace('-', '_', $client_lang);
		if(array_key_exists($client_lang, $locale_list)) $_SESSION['language']=$client_lang;
	}
	
	// sticky drop down menu to choose the display language
	foreach($locale_list as $key => $value)
	{
		echo "<option value=\"$key\"";
		if(isset($_SESSION['language']) && ($key == $_SESSION['language'])) echo " selected";
		echo ">$value</option>";
	}?>	     
	</select>
	</div>	
<?php }
?>
