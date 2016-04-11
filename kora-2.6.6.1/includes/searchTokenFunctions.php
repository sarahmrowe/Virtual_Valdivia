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

require_once('includes.php');

// Initial Version: Brian Beck, 2008
// Refactor: Joe Deming, Anthony D'Onofrio 2013

Manager::Init();
Manager::RequireSystemAdmin();

/**
  * Prints out the html for the search token form
  *
  * @return html output of token form
  */
function PrintTokens()
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
            $allowSearch = '<select id="addProject'.$token['uid'].'" name="addProject'.$token['uid'].'">';
            
            // Since the lists are mututally exclusive, iterate through the project list
            // exactly once and populate both fields
            foreach($projectList as $project)
            {
                if (isset($accessList[$token['uid']][$project['pid']]))
                {
                    $canSearch .= '<tr><td>'.htmlEscape($project['name']).'</td><td><a class="delete" onclick="removeProjectAccess('.$token['uid'].','.$project['pid'].')">X</a></td></tr>';
                }
                else    // Does not currently have access; add to the allowSearch list
                {
                	$allowSearch .= '<option value="'.$project['pid'].'">'.htmlEscape($project['name']).'</option>';
                }
            }
            
            $canSearch .= '</table>';
            $allowSearch .= '</select><br /><input type="button" value="'.gettext('Allow').'" onclick="addProjectAccess('.$token['uid'].')" />';
        	
            echo '<tr><td>'.htmlEscape($token['username']).'</td>';
            echo "<td>$canSearch</td>";  // has access to
            echo "<td>$allowSearch</td>";  // add access to
            echo '<td><a class="delete" onclick="deleteToken('.$token['uid'].')">X</a></td></tr>';	
        }
        echo '</table>';
    }
    echo gettext('Please note that tokens are case-sensitive').'.<br /><br />';
	echo '<input type="button" class="button" value="'.gettext('Create New Token').'" onclick="createToken()" />'; 
}

/**
  * Generates a search token in KORA and saves it into the DB
  *
  * @return void
  */
function createToken()
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
	
	PrintTokens();
}

/**
  * Removes a token and all information related to that token from KORA and the DB
  *
  * @return void
  */
function deleteToken($tokenID)
{
	global $db;
	
	$db->query('DELETE FROM member WHERE uid='.escape($tokenID));
	$db->query('DELETE FROM user WHERE uid='.escape($tokenID));
	
	PrintTokens();
}

/**
  * Assigns a valid project to a specific search token
  *
  * @return void
  */
function addAccess($tokenid, $pid)
{
	global $db;

	// should we check to see if it's a valid pid and a valid tokenid here?  We DO
	// require System Admin to call any of these, but it might be best to play it safe
	// at the expense of a couple more database calls....
	
    $db->query('INSERT INTO member (uid, pid, gid) VALUES ('.escape($tokenid).','.escape($pid).',0)');

    PrintTokens();
}

/**
  * Removes a project from a specific search token
  *
  * @return void
  */
function removeAccess($tokenid, $pid)
{
    global $db;

    $db->query('DELETE FROM member WHERE uid='.escape($tokenid).' AND pid='.escape($pid));
    
    PrintTokens();
}
?>