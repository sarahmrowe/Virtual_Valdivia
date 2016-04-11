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

// Initial Version: Matt Geimer, 2008
// Refactor: Joe Deming, Anthony D'Onofrio 2013

require_once('includes/includes.php');

Manager::Init();

Manager::RequireLogin();

// NEED TO DO THIS LANG STUFF BEFORE PRINTING HEADER
$u = Manager::GetUser();
$_SESSION['language'] = $u->GetLanguage();

Manager::PrintHeader();

	//TODO: Table!!!!
	?>
	
	<h2><?php echo gettext('Update User Information');?></h2>
	
	<div class='account_error' style="color:red"><?php if(Manager::CheckRequestsAreSet(['submit'])){echo $_REQUEST['submit'];}?></div>
	
	<table class="account_userUpdate">
	   <tr><td align="right"><?php echo gettext('Username').':';?></td><td><?php echo htmlEscape($u->GetLoginName());?></td></tr>
	   <tr><td align="right"><?php echo gettext('E-Mail').':';?></td><td><input type="text" class="account_email" value="<?php echo htmlEscape($u->GetEmail());?>" /></td></tr>
	   <tr><td align="right"><?php echo gettext('Real Name').':';?></td><td><input type='text' class='account_realName' value='<?php echo htmlEscape($u->GetRealName());?>' /></td></tr>
	   <tr><td align="right"><?php echo gettext('Organization').':';?></td><td><input type='text' class='account_organization' value='<?php echo htmlEscape($u->GetOrganization());?>' /></td></tr>
	   <tr><td align="right"><?php echo gettext('New Password').':';?><br />(<?php echo gettext('Leave blank if not changing');?>)</td><td><input type='password' class='account_password1' /></td></tr>
	   <tr><td align="right"><?php echo gettext('Confirm New Password').':';?></td><td><input type='password' class='account_password2' /></td></tr>
	   <tr><td align="right"><?php echo gettext('Language').':'?></td>
	   <td><select class="account_language">
	   <?php foreach($locale_list as $key => $value)
    		  {
    			echo "<option value=\"$key\"";
    			if($key ==  $u->GetLanguage()) echo " selected";
    			echo ">$value</option>";
    	  	  } ?> </select></td></tr>
	   <tr><td colspan="2" align="right"><button class="account_submitUpdate">Submit</button></td></tr>
	</table>

<?php Manager::PrintFooter(); ?>
