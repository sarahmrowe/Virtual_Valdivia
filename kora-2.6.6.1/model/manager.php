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

// Initial Version: Joseph M. Deming, 2013

require_once(__DIR__.'/../includes/includes.php');

/**
 * @class Manager object
 */
class Manager {
	
	const CSS_CORE = 0;
	const CSS_LIB = 100;
	const CSS_CLASS = 1000;
	const CSS_THEME = 10000;
	const CSS_END = 65536;
	
	const JS_CORE = 0;
	const JS_LIB = 100;
	const JS_CLASS = 1000;
	const JS_END = 65536;
	
	private static $db = null;
	private static $js = [];
	private static $css = [];
	private static $user = null, $uid = null;
	private static $project = null, $pid = null;
	private static $scheme = null, $sid = null;
	private static $record = null, $rid = null;
	
	public static function Init()
	{
		Manager::SetUser();
		Manager::SetProject();
		Manager::SetScheme();
		Manager::SetRecord();
		
		Manager::AddCSS('css/all.css', Manager::CSS_CORE);
		Manager::AddCSS('includes/thickbox/thickbox.css', Manager::CSS_LIB);
        Manager::AddCSS('includes/mediaelement/build/mediaelementplayer.css', manager::CSS_LIB);
		
		Manager::AddJS('//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js', Manager::JS_CORE);
		Manager::AddJS('javascripts/gettext.js.php', Manager::JS_CORE);
		Manager::AddJS('includes/thickbox/thickbox.js', Manager::JS_LIB);
		Manager::AddJS('javascripts/loading.js', Manager::JS_CORE);
        Manager::AddJS('includes/mediaelement/build/mediaelement-and-player.min.js', Manager::JS_LIB);
		
		// ADD STYLE FOR SELECTED PROJECT (OR DEFAULT IF NO PROJ)
		if (Manager::GetProject()) { Manager::AddCSS(Manager::GetProject()->GetStylePath(), Manager::CSS_THEME); }
		else                       { Manager::AddCSS('css/default.css', Manager::CSS_THEME); }
		
		Plugin::Init();
		Plugin::ScanForPlugins();
		
	}
	
	protected static function SetUser()
	{
		Manager::$uid = (isset($_SESSION['uid'])) ? $_SESSION['uid'] : null;
	}
	
	protected static function SetProject()
	{
		Manager::$pid = (isset($_REQUEST['pid'])) ? $_REQUEST['pid'] : null;
	}
	
	protected static function SetScheme()
	{
		Manager::$sid = (isset($_REQUEST['sid'])) ? $_REQUEST['sid'] : null;
	}
	
	protected static function SetRecord()
	{
		Manager::$rid = (isset($_REQUEST['rid'])) ? $_REQUEST['rid'] : null;
	}
	
	public static function GetUser() { if (Manager::$uid && !Manager::$user) { Manager::$user = new User(); } return Manager::$user; }
	public static function GetProject() { if (Manager::$pid && !Manager::$project) { Manager::$project = new Project(Manager::$pid); } return Manager::$project; }
	public static function GetScheme() { if (Manager::$sid && !Manager::$scheme) { Manager::$scheme = new Scheme(Manager::$pid, Manager::$sid); } return Manager::$scheme; }
	public static function GetRecord() { if (Manager::$rid && !Manager::$record) { Manager::$record = new Record(Manager::$pid, Manager::$sid, Manager::$rid); } return Manager::$record; }
	public static function PluginsExist() { return !empty(Manager::$plugins); }
	
	public static function IsLoggedIn() { return (Manager::GetUser() && Manager::GetUser()->IsLoggedIn()); }
	public static function IsSystemAdmin() { return (Manager::GetUser() && Manager::GetUser()->IsSystemAdmin()); }
	public static function IsProjectAdmin() { return (Manager::GetUser() && Manager::GetUser()->IsProjectAdmin()); }
	
	public static function GetPresetSchemes()
	{
		global $db;
		$presetSchemes = $db->query('SELECT CONCAT(project.name, \'/\', scheme.schemeName) AS name, scheme.schemeid AS id FROM scheme LEFT JOIN project USING (pid) WHERE scheme.allowPreset=1');
		$retval = array();
		if ($presetSchemes)
		{
			while ($p = $presetSchemes->fetch_assoc())
			{ $retval[$p['id']] = $p['name']; }
		}
		return $retval;		
	}
	
	/*
	 * TODO: AJAX Escaping?
	 * Take in a single request and check to make sure the format is correct.
	 * Requests should only be letters, there's no punctuation (usually).
	 * Return scrubbed variable. <-- $scrubbed
	 * If there is a problem, raise flag. return null string("")?
	 */
	public static function ScrubRequest($request)
	{
		$toScrub = str_split($request); //split string into array
		foreach($toScrub as $character)
		{
			//A-Z
			if ( $character >= 'A' && $character <= 'Z' )
			{ //clear
				$scrubBool = true;
			}
			//a-z
			else if ( $character >= 'a' && $character <= 'z' )
			{ //clear
				$scrubBool = true;
			}
			// 0-9
			else if ( $character >= '0' && $character <= '9' )
			{ //clear
				$scrubBool = true;
			}
			// Exceptions: - _
			else if ( $character == '-' || $character == '_')
			{ //clear
				$scrubBool = true;
			}
			//fail
			else
			{
				echo "This is bad: " . $request . '<br>'; //Remove echo after testing
				trigger_error(gettext("Bad Request, can only contain letters, numbers, and -"), E_USER_ERROR);
				return "";
			}
		}
		
		//If pass, return request.
		if ($scrubBool == true)
			return $request;
		else
		{
			trigger_error(gettext("Bad Request, Not a String"), E_USER_NOTICE);
			return "";
		}
	}
	
	public static function CheckRequestsAreSet($indexes)
	{
		foreach($indexes as $index)
		{	
			if(!isset($_REQUEST[$index]))
			{
				return false;
			}
		}
		return true;
	}
	
	// pass in a cid and get the control back in it's type'd class
	public static function GetControl($pid_, $cid_, $rid_ = '', $preid_ = '')
	{
		global $db;
		$cTable = 'p'.$pid_.'Control';
		$ctrltypequery = $db->query("SELECT type FROM $cTable WHERE cid=".escape($cid_));
		if ($ctrltypequery->num_rows == 0) 
		{ Manager::PrintErrDiv('Control not found with cid '.$cid_); return false; }
		$ctype = $ctrltypequery->fetch_assoc();
		$cobj = new $ctype['type']($pid_, $cid_, $rid_, $preid_);
		return $cobj;		
	}
	
	public static function PrintJS()
	{
		ksort(Manager::$js);
		foreach (Manager::$js as $c)
		{
			print '<script type="text/javascript" src="'.$c.'" ></script>'."\n";
		}
	}
	
	public static function PrintCSS()
	{
		ksort(Manager::$css);
		foreach (Manager::$css as $c)
		{
			print '<link href="'.$c.'" rel="stylesheet" type="text/css" />'."\n";
		}
	}
	
	public static function PrintGlobalDiv()
	{
		$out = "<div id='kora_globals' ";
		if (Manager::GetUser()) { $out .= "uid='".Manager::GetUser()->GetUID()."' "; }
		if (Manager::GetProject()) { $out .= "pid='".Manager::GetProject()->GetPID()."' "; }
		if (Manager::GetScheme()) { $out .= "sid='".Manager::GetScheme()->GetSID()."' "; }
		if (Manager::GetRecord()) { $out .= "rid='".Manager::GetRecord()->GetRID()."' "; }
		$out .= "baseURI='".baseURI."' ";
		$out .= "></div>";
		
		print $out;
	}
	
	// TODO: REVIEW THIS FUNCTION
	public static function PrintLoginDiv()
	{ ?>
		<div id="login">
		
		<?php if (!Manager::IsLoggedIn()) { ?>
		    
		    <a href="<?php echo baseURI;?>index.php"><?php echo gettext('Log In');?></a> |
		    <a href="<?php echo baseURI;?>accountRegister.php"><?php echo gettext('Register');?></a> |
		    <a href="<?php echo baseURI;?>accountActivate.php"><?php echo gettext('Activate Account');?></a>
		<?php } else { ?>
			<a href="<?php echo baseURI;?>accountLogout.php"><?php echo gettext('Log Out');?></a> |
		    <a href="<?php echo baseURI;?>accountSettings.php"><?php echo gettext('Update User Info')?></a>
		<?php } ?>
	
		</div>
			<?php if (Manager::IsLoggedIn()) { ?>
			<div class="clear"></div>
	
			<div class="koraglobal_recordSearch_form" id="viewobject"><?php echo gettext('View Record');?>:&nbsp
			<input type="text" class="koraglobal_recordSearch_rid" />
			<div class="koraglobal_recordSearch_error" style="color:red"></div>
			</div>
			
			<?php } //TODO:FORM?>
		</div>
	<?php }
	
	/**
	 * Prints a div tag with proper error classes for styling and jquery handling
	 *
	 * @param $msg_ - the readable error message
	 * @param $global_ - (default true) if true it includes the class 'global_error' which is later appended to the main global error 
	 *                   div displayed at the top of the page, defaults to true, if false tag will just show up inline where it is called
	 */
	public static function PrintErrDiv($msg_, $global_ = true)
	{
		$cl = ($global_) ? 'global_error error' : 'error';
		print "<div class='$cl'>$msg_</div>";
	}

	public static function PrintBreadcrumbs($NoProj=false)
	{
		if(Manager::GetProject()){
			//echo '<a href="selectScheme.php?pid='.Manager::GetProject()->GetPID().'">'.Manager::GetProject()->GetName().'</a>';
			Manager::GetProject()->PrintQuickJump();
		}else if($NoProj){
			$authProjs = Manager::GetUser()->GetAuthorizedProjects();
			if (count($authProjs) > 1) {
				//multiple projects = drop down menu
				?>
				<select class='kpquickjump' size="1">
				<?php
				foreach($authProjs as $apid) {
					$aproj = new Project($apid);
					echo '<option value="'.$aproj->GetPID().'">'.htmlEscape($aproj->GetName()).'</option>';
				}
				?>
				</select>
				<?php
			} else if($authProjs==1){
				//single project = link
				$aproj = new Project($authProjs[0]);
				echo '<a href="'.baseURI.'selectProject.php?pid='.$this->GetPID().'">'.htmlEscape($aproj->GetName()).'</a>';
			}
		}
		if(Manager::GetScheme()){
			echo '&mdash;&gt;';
			Manager::GetScheme()->PrintQuickJump();
		}
		if(Manager::GetRecord()){ // SHOW LINK FOR LIST-ALL AS WELL AS FOR THIS SPECIFIC RID
			echo '&mdash;&gt;<a href="searchResults.php?pid='.Manager::GetProject()->GetPID().'&sid='.Manager::GetScheme()->GetSID().'">'.gettext('records').'</a>';
			echo '&mdash;&gt;<a href="viewObject.php?rid='.Manager::GetRecord()->GetRID().'">'.Manager::GetRecord()->GetRID().'</a>';
		}
	}
	
	// Show a list of current tokens and their permissions
	public static function PrintTokens()
	{
		global $db;
		
		$existingQuery = $db->query('SELECT uid,username FROM user WHERE searchAccount=1 ORDER BY uid');
		if ($existingQuery->num_rows == 0)
		{
			echo gettext('No existing search tokens found').'.<br /><br />';
		}
		else
		{
			// Build up an array of project IDs and names
			$projectQuery = $db->query('SELECT pid,name FROM project ORDER BY name');
			$projectList = array();
			while ($p = $projectQuery->fetch_assoc())
			{
				$projectList[] = $p;
			}
			
			// Build up an array of what tokens have access to what projects
			$accessQuery = $db->query('SELECT uid,pid FROM member WHERE uid IN (SELECT uid FROM user WHERE searchAccount=1)');
			$accessList = array();
			while ($row = $accessQuery->fetch_assoc())
			{
				if (!isset($accessList[$row['uid']]))
				{
					$accessList[$row['uid']] = array();
				}
				// Since associative array indexes are done as a hash table,
				// isset ends up being faster than in_array, so I just use
				// the pid as an index, not as a value.			
				$accessList[$row['uid']][$row['pid']] = true;
			}
			
			
			echo gettext('Existing Tokens').':';
			?>
			<table class="table">
			<tr><td><b><?php echo gettext('Token');?></b></td>
			<td><b><?php echo gettext('Can Search');?>:</b></td>
			<td><b><?php echo gettext('Allow Search Of');?>:</b></td>
			<td><b><?php echo gettext('Delete');?></b></td></tr>
			<?php  		 
			while($token = $existingQuery->fetch_assoc())
			{
				// Populate the list of projects the token has access to and
				// can be granted access to
				
				// empty text fields to begin populating the lists 
				$canSearch = '<table border="0">';
				$allowSearch = '<select id="addProject'.$token['uid'].'" name="addProject'.$token['uid'].'" class="token_proj" >';
				
				// Since the lists are mututally exclusive, iterate through the project list
				// exactly once and populate both fields
				foreach($projectList as $project)
				{
					if (isset($accessList[$token['uid']][$project['pid']]))
					{
						$canSearch .= '<tr class="token_proj_row" tokprojid="'.$project['pid'].'"><td>'.htmlEscape($project['name']).'</td><td><a class="delete token_delproj" >X</a></td></tr>';
					}
					else    // Does not currently have access; add to the allowSearch list
					{
						$allowSearch .= '<option value="'.$project['pid'].'">'.htmlEscape($project['name']).'</option>';
					}
				}
				
				$canSearch .= '</table>';
				$allowSearch .= '</select><br /><input type="button" value="'.gettext('Allow').'" class="token_addproj"/>';
				
				echo '<tr class="token_row" tokid="'.$token['uid'].'"><td class="token_val">'.htmlEscape($token['username']).'</td>';
				echo "<td>$canSearch</td>";  // has access to
				echo "<td>$allowSearch</td>";  // add access to
				echo '<td><a class="delete token_delete" >X</a></td></tr>';	
			}
			echo '</table>';
		}
		echo gettext('Please note that tokens are case-sensitive').'.<br /><br />';
		echo '<input type="button" class="button token_create" value="'.gettext('Create New Token').'" />'; 
	}
	
	public static function PrintSystemManagementForm(){
		echo '<h2>'.gettext('System management').'</h2>';
		?><div id="koraAdminSysManage">
		<div id="ka_admin_result"></div><br>
		<input type="button" class="ka_sysMgt_updateCtrlList" value="<?php echo gettext('Update Control List');?>" /><br /><br />
		<input type="button" class="ka_sysMgt_updateStyleList" value="<?php echo gettext('Update Style List');?>" />
		<br /><br>
		<input type="button" class="ka_sysMgt_updateDatabase" value="<?php echo gettext('Upgrade Database Layout');?>" /><br />
		</div>
		<?php
	}
	
	/**
	* Displays a set of breadcrumb navigation for a page system.  This is like
	* a 'Print' function except it returns the html instead of printing directly
	* to std::out.
	*
	* Parameters:
	*
	* @int maxPage - The final page (assumes first page is 1)
	* @int currentPage - Somewhere in the range of [1, maxPage]
	* @int adjacents - The number of adjacent records to show to the current Page
	* @string pageLink - The href or onclick portion of a link to add the page number
	*                    to using printf syntax inside of an <a> tag, eg:
	*                    href="viewObject.php?rid=%d"
	*/
	public static function GetBreadCrumbsHTML($maxPage, $currentPage, $adjacents, $pageLink, $linkclass = '')
	{
		//2.7.0 pagination forces 5 links out
		$adjacents = 5;
		$crumbs = '';
		if ($maxPage > 1)
		{
			$aclass = ($linkclass != '') ? "class=$linkclass" : '';
		
			// Display "Prev" link
			if ($currentPage == 1)
			{ $crumbs .= gettext('Prev').' | ';	}
			else
			{ $crumbs .= '<a '.$aclass.' '.sprintf($pageLink, ($currentPage - 1) ).'>'.gettext('Prev').'</a> | '; }
		
			if ($maxPage < (7 + $adjacents * 2)) // < 17
			{
				// There's few pages, display them all
				for($i=1; $i <= $maxPage; $i++)
				{
					if ($i != $currentPage)
					{ $crumbs .= '<a '.$aclass.' '.sprintf($pageLink, $i).">$i</a> | ";	}
					else
					{ $crumbs .= "$i | "; }
				}
			}
			else //Use pages
			{
				if ($currentPage < (1 + $adjacents * 2)) // < 11
				{
					// we're near the beginning
					// show the first 10 pages
					for($i=1; $i <= 11; $i++) {
						if ($i != $currentPage) { 
							$crumbs .= '<a '.$aclass.' '.sprintf($pageLink, $i).">$i</a> | ";
						} else {
							$crumbs .= "$i | ";
						}
					}
					
					// show the ... and the last page
					$crumbs .= '... | <a '.$aclass.' '.sprintf($pageLink, $maxPage).'>'.$maxPage.'</a> | ';
				}
				else if ((($maxPage - $adjacents * 2) > $currentPage) && ($currentPage > ($adjacents * 2))) // 10pgs < currentPage < max-10pgs
				{
					// we're in the middle
					// display the first page and ...
					$crumbs .= '<a '.$aclass.' '.sprintf($pageLink, 1).'>1</a> | ... | ';
					
					// display the middle pages by increments of 10
					for($i=$currentPage-($adjacents*10); $i <= ($currentPage + $adjacents*10); $i+=10) {
						if ($i != $currentPage) {	
							if (round($i, -1) == 0) {
								$crumbs .= '<a '.$aclass.' '.sprintf($pageLink, 10).">10</a> | ";
							} else if (round($i, -1) > 0 && $i < $maxPage){
								$crumbs .= '<a '.$aclass.' '.sprintf($pageLink, round($i, -1)).">".round($i, -1)."</a> | ";
							}
						} else {
							$crumbs .= "$i | ";
						}
					}
					
					// show the ... and the last page
					$crumbs .= '... | <a '.$aclass.' '.sprintf($pageLink,$maxPage).'>'.$maxPage.'</a> | ';
				}
				else //last 10 pages
				{
					// we're at the end
					// display the first page and ...
					$crumbs .= '<a '.$aclass.' '.sprintf($pageLink,1).'>1</a> | ... | ';
					
					// display the final 10 pages
					for($i=($maxPage - 11); $i <= $maxPage; $i++) {
						if ($i != $currentPage) {
							$crumbs .= '<a '.$aclass.' '.sprintf($pageLink,$i).">$i</a> | ";
						} else {
							$crumbs .= "$i | ";
						}
					}
				}
			}
			
			// Display "Next" link
			if ($currentPage == $maxPage)
			{ $crumbs .= gettext('Next'); }
			else
			{ $crumbs .= '<a '.$aclass.' '.sprintf($pageLink,($currentPage + 1)).'>'.gettext('Next').'</a>'; }
		}
		
		return $crumbs;
	}

	public static function PrintHeader($NoProj=false)
	{ 
		include_once(basePath.'includes/header.php');
	}
	
	public static function PrintFooter()
	{ 
		include_once(basePath.'includes/footer.php');
	}
	
	public static function AddCSS($css_, $id_ = null)
	{
		if ($id_ !== null) { if (!isset(Manager::$css[$id_])) { Manager::$css[$id_] = $css_; } else { Manager::AddCSS($css_, $id_+1); } }
		else               { if (!in_array($css_, Manager::$css)) { Manager::$css[] = $css_; } }
	}
	
	public static function AddJS($js_, $id_ = null)
	{
		if ($id_ !== null) { if (!isset(Manager::$js[$id_])) { Manager::$js[$id_] = $js_; } else { Manager::AddJS($js_, $id_+1); } }
		else               { if (!in_array($js_, Manager::$js)) { Manager::$js[] = $js_; } }
	}
	
	// Grant a Token access to a project
	public static function addAccess($tokenid, $pid)
	{
		global $db;
		
		// should we check to see if it's a valid pid and a valid tokenid here?  We DO
		// require System Admin to call any of these, but it might be best to play it safe
		// at the expense of a couple more database calls....
		
		$db->query('INSERT INTO member (uid, pid, gid) VALUES ('.escape($tokenid).','.escape($pid).',0)');
	}
	
	// Revoke a token's access to a project
	public static function removeAccess($tokenid, $pid)
	{
		global $db;
		
		echo 'Here';
		
		$db->query('DELETE FROM member WHERE uid='.escape($tokenid).' AND pid='.escape($pid));
		
	}
	
	// Create a new Search Token
	public static function createToken()
	{
		global $db;	
		
		// generate a 24-character hex string
		// I don't believe PHP is capable of handling the concept of
		// 0xffffffffffffffffffffffff, and if it could it'd be ugly to get
		// a random number in that range.  So, when in doubt, loop a simpler problem!
		
		$validToken = false;
		while (!$validToken)
		{
			$token = '';
			for($i = 0; $i < 4; $i++)
			{
				$token .= sprintf("%06x", mt_rand(0x000000, 0xffffff)); 
			}
			
			// See if the token is already taken (what sick person uses hex strings as
			//     usernames anyway?)
			$available = $db->query("SELECT uid FROM user WHERE username='$token' LIMIT 1");
			if ($available->num_rows == 0) 
			{
				$validToken = true;
			}
		}
		
		$query  = "INSERT INTO user (username, password, salt, email, admin, confirmed, searchAccount) ";
		$query .= "VALUES ('$token', '$token', 0, ' ', 0, 0, 1)";
		$db->query($query);
	}
	
	// Delete a Token
	public static function deleteToken($tokenID)
	{
		global $db;
		
		$db->query('DELETE FROM member WHERE uid='.escape($tokenID));
		$db->query('DELETE FROM user WHERE uid='.escape($tokenID));
	}
	
	public static function CheckDatabaseVersion()
	{
		// See if the database is up-to-date
		// THIS SHOULD BE DONE WITH THE FUTURE 'DATABASE' CLASS  OR OTHERWISE W/OUT SESSIONS...
		if (isset($_SESSION['dbVersion']) && Manager::IsSystemAdmin() && version_compare($_SESSION['dbVersion'], LATEST_DB_VERSION, '<'))
		{
			Manager::PrintErrDiv('<a href="'.baseURI.'upgradeDatabase.php" >'.gettext('Your database is out of date; please upgrade it').'</a></div><br />');
		}
	}
	
	/**
	* Redirects to specified page if not logged in
	*
	* @param string $location
	*/
	public static function RequireLogin($location = 'accountLogin.php')
	{
		if (!Manager::IsLoggedIn())
		{
			header("Location: $location");
			die();
		}
	}
	
	/**
	* Redirects to specified page if the currently logged-in user lacks the specified permissions
	* for the currently-selected project
	*
	* @param unsigned_integer $permissions
	* @param string $location
	*/
	public static function RequirePermissions($permissions, $location = 'index.php')
	{
		Manager::RequireLogin($location);
		Manager::RequireProject($location);
		
		if (!Manager::GetUser()->HasProjectPermissions($permissions)) { 
			header("Location: $location");
			die();
		}
	}
	
	/**
	* Redirects to specified page if current user is not a System Admin
	*
	* @param string $location
	*/
	public static function RequireSystemAdmin($location = 'index.php')
	{
		if (!Manager::IsSystemAdmin())
		{
			header("Location: $location");
			die();
		}
	}

	/**
	* Redirects to specified page if current user is not an Admin of the current Project
	*
	* @param string $location
	*/
	public static function RequireProjectAdmin($location = null)
	{
		if (!Manager::IsProjectAdmin())
		{
			// THIS WILL BOUNCE USER TO SELECT PROJECT IF NO PROJECT IS SET
			Manager::RequireProject();
			// ELSE WE WILL DO THE EQUIVALENT OF REQUIRE SCHEME IF LOCATION WAS NOT SPECIFIED
			if (!$location) { $location = 'selectScheme.php?pid='.Manager::GetProject()->GetPID(); }
			header("Location: $location");
			die();
		}
	}
	
	/**
	* Redirects to specified page is no project is currently selected
	*
	* @param string $location
	*/
	public static function RequireProject($location = 'selectProject.php')
	{
		Manager::RequireLogin();
		if (!Manager::GetProject())
		{
			header("Location: $location");
			die();
		}
	}
	
	/**
	* Redirects to specified page if no scheme is currently selected
	*
	* @param string $location
	*/
	public static function RequireScheme($location = null)
	{
		Manager::RequireLogin();
		if (!Manager::GetScheme())
		{
			// THIS WILL BOUNCE USER TO SELECT PROJECT IF NO PROJECT IS SET
			Manager::RequireProject();
			if (!$location) { $location = 'selectScheme.php?pid='.Manager::GetProject()->GetPID(); }
			header("Location: $location");
			die();
		}
	}	

	/**
	* Redirects to specified page if no scheme is currently selected
	*
	* @param string $location
	*/
	public static function RequireRecord($location = null)
	{
		Manager::RequireLogin();
		
		if (Manager::GetRecord() && Manager::GetRecord()->HasData())
		{ return true; }

		// IF CALLER PASSED IN A TARGET BOUNCE LOCATION, BOUNCE THERE
		if ($location)
		{ 
			header("Location: $location");
			die();
		}
		
		// ELSE WE TRY TO BOUNCE THEM TO THE CLOSEST PAGE GIVEN THEIR CURRENT INFO
		if (Manager::GetProject() && Manager::GetScheme())
		{
			header('Location: schemeLayout.php?pid='.Manager::GetProject()->GetPID().'&sid='.Manager::GetScheme()->GetSID());
			die();
		}
		else if (Manager::GetProject())
		{
			header('Location: selectScheme.php?pid='.Manager::GetProject()->GetPID());
			die();
		}
		else
		{
			header('Location: selectProject.php');
			die();
		}
	}

	public static function DoesRecordExist($kid){
		global $db;
	
		$k = explode('-',$kid);
		
		$Query = $db->query("SELECT * FROM p".$k[0]."Data WHERE id='".$kid."'");
		
		if($Query->num_rows > 0){
			return true;
		}else{
			return false;
		}
	}

	/**
	* Remove all files and directories from given file path
	* @param $basedir - file path to remove files and directories from 
	*/
	public static function ClearFiles($baseDir) {
		
		// MAN THIS FUNCTION SEEMED DANGEROUS, SO I PUT
		// THIS IN FOR A LITTLE PEICE OF MIND
		if (strrpos($baseDir,fileDir) !== 0)
		{ Manager::PrintErrDiv("Problem clearing files from [$baseDir], path expected to be subdir if [".fileDir."]"); return false; }
		
		$dirExceptions = array('.','..','.svn');
		$fileExceptions = array('index.php');
		
		$files = scandir($baseDir);
		foreach ($files as $f) {
			if (is_dir($baseDir.$f)) {
				if(!in_array($f,$dirExceptions)) {
					clearUploadedFiles($baseDir.$f."/");
					rmdir($baseDir.$f);
				}
			} else if (!in_array($f,$fileExceptions)) {
				unlink($baseDir.$f);
			}
		}
	}
	
	public static function UpdateControlList(){
		global $db;
	
		// get the list of control files
		$dir = basePath.CONTROL_DIR;
		$controlList = array();
		if(is_dir($dir)) {
			if($dh = opendir($dir)) {
				while(($file = readdir($dh)) !== false) {
					if(filetype($dir.$file) == "file") {
						$controlfile = explode(".",$file);
						if(!in_array($controlfile[0], array('index', 'control', 'controlVisitor'))) {
							$controlList[] = $controlfile[0];
							//require_once($dir.$file);	                        
						}
					}
				}
			}
		}
		
		$dbControls = array();
		$controlList = array_unique($controlList);
		
		foreach($controlList as $control) {
			$controlName = ucfirst($control);
			$controlInstance = new $controlName();
			$dbControls[] = array('name' => $controlInstance->getType(), 'file' => $control.'.php', 'class' => $controlName, 'xmlPacked' => $controlInstance->isXMLPacked() ? '1' : '0');
		}
		
		// clear the controls list
		$db->query("SET SQL_SAFE_UPDATES=0;");
		$db->query("DELETE FROM control");
		$db->query("SET SQL_SAFE_UPDATES=1;");
		// insert the controls into the table
		
		foreach($dbControls as $c) $db->query('INSERT INTO control (name, file, class, xmlPacked) VALUES ('.escape($c['name']).', '.escape($c['file']).', '.escape($c['class']).', '.escape($c['xmlPacked']).')');
		
		echo gettext('Control List Updated');
	}
	
	public static function UpdateStyleList(){
		global $db;
	
		// Make sure any rows currently in the DB still exist
		$styleQuery = $db->query('SELECT styleid, filepath FROM style');
		while ($s = $styleQuery->fetch_assoc())
		{
			if (!file_exists(basePath.'css/'.$s['filepath']))
			{
				// Remove any references that projects had to that styleid
				$db->query('UPDATE project SET styleid=0 WHERE styleid='.$s['styleid']);
				// Delete the row
				$db->query('DELETE FROM style WHERE styleid='.$s['styleid'].' LIMIT 1');
			}
		}
		
		// Scan for any new XML files
		if ($dirHandle = opendir(basePath.'css'))
		{
			// Read all the file names
			while (($filename = readdir($dirHandle)) !== FALSE)
			{
				// See if it's a .XML file
				if (strlen($filename) && substr($filename, -4) == '.xml')
				{
					$xml = simplexml_load_file(basePath.'css/'.$filename);
					// Make sure the necessary components are in place and the
					// file exists
					if (isset($xml->file) && isset($xml->name) && file_exists(basePath.'css/'.(string)$xml->file))
					{
						// Make sure no other record for this file exists, then insert a
						// record
						$testQuery = $db->query('SELECT styleid FROM style WHERE filepath='.escape((string)$xml->file).' LIMIT 1');
						if ($testQuery->num_rows == 0)
						{
							$db->query('INSERT INTO style (description, filepath) VALUES ('.escape((string)$xml->name).','.escape((string)$xml->file).')');
						}
					}
				}
			}
		}
		
		echo gettext('Style List Updated');
	}
}

?>
