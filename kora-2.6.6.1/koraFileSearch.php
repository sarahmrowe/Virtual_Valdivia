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

require_once("includes/includes.php");
Manager::Init();
Manager::RequireLogin();

Manager::PrintHeader();

if (!@$solr_enabled) die("Solr is not enabled in includes/conf.php! KORA File Search cannot work.");
?>

        <h2>Search Contents of Files in KORA</h2>
        <form method="POST" action="koraFileSearch.php">            
            <table class="table">
                <tr>
                       <td>
                        <b>Project</b>
                    </td>
                    <td>
                        <select id="pid" name="pid"></select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>Scheme</b>
                    </td>
                    <td>
                        <select id="sid" name="sid"></select>
                    </td>
                <tr>
                    <td>
                        <b>Keywords</b>
                    </td>
                    <td>
                        <input type="text" name="query" size="30" value="<?php if (Manager::CheckRequestsAreSet(["query"])) echo $_REQUEST["query"];?>">
                    </td>
                </tr>
                <tr><td colspan="2"><input type="submit" value="Search"></td></tr>
               </table>
        </form>
    
    <script type="text/javascript">
        $("#pid").load("listOptions.php"); // Populate the lists initially
        $("#pid").change( function() { updateSIDs($("#pid").val()) } ); // When the PID selector gets changed, update the SID selector
        
        function updateSIDs(pidtemp)
        {
            $("#sid").load("listOptions.php", {"pid": pidtemp}); // AJAX call this page, posting the PID that is selected
        }
    </script>

<?php
$query = null;
$pid = null;
$sid = null;

if ( Manager::CheckRequestsAreSet(["query"]) ) $query = $_REQUEST["query"];
if ( Manager::CheckRequestsAreSet(["pid"]) )
{
    $pid = $_REQUEST["pid"];
    if ($pid == "null") $pid = null; // The null option in the list was selected
    else $pid = dechex($pid);
}
if ( Manager::CheckRequestsAreSet(["sid"]) )
{
    $sid = $_REQUEST["sid"];
    if ($sid == "null") $sid = null; // The null option in the list was selected
    else $sid = dechex($sid);
}


if ( $query != null && @$solr_enabled )
{
    $start_time = microtime(true); // Start a timer
    
    // urlencode to ensure spaces and other special characters don't blow things up
    $query = urlencode(addslashes($_REQUEST["query"]));
    $req_url = solr_url."select/?q=".$query."&fl=id&rows=10000&indent=on";
    
    $ch = curl_init($req_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Return the data instead of true/false
    
    $xml_result = curl_exec($ch); // Execute the search in Solr
    $search_time = microtime(true); // Start a timer for when the search was completed
    
    // If for some reason Curl could not successfully connect, Solr probably isn't running, print an error
    if (curl_errno($ch) != 0)
    {
    	echo "ERROR: Could not connect to Solr. Please ensure that Solr is running on the server.";
    	Manager::PrintFooter();
    	return;
    }
    
    curl_close($ch); // Close the channel
    echo "<strong>Ranked Results:</strong><br /><br />";
    echo "<table style=\"border:0;\" cellspacing=5>";
    echo "<tr><td><strong>Project Name</strong></td><td><strong>Record</strong></td><td><strong>File</strong></td></tr>";
    $xml = new SimpleXMLElement($xml_result); // Parse $xml_result
    
    $fullResults = (int)$xml->result->attributes()->numFound; // Determine number of results to iterate over
    
    $trueResults = 0; // Actual number of results that will be printed to the screen
    
    for($i = 0; $i < $fullResults; $i++)
    {
        $rid_cid = (string)$xml->result->doc[$i]->str; // Extract the RID-CID from each result
        $rid_array = explode('-', $rid_cid); // Make an array
        $rid = "$rid_array[0]-$rid_array[1]-$rid_array[2]"; // Put together the RID
        $cid = $rid_array[3];
        
        // Gets the URL for the file from clientUtilities.php
        // Used to make a link to the file
        $urlToFile = getURLFromRecordID($rid, $cid); 
        

        if ($pid != null) // Search one project only
        {
            if ($sid != null) // Search one scheme only
            {
                if ($pid == $rid_array[0] && $sid == $rid_array[1]) // Print only matching PIDs and SIDs
                {
                    printLink($rid, $cid);
                    $trueResults++;
                }
            }
            else // Searching one project but all schemes
            {
                if ($pid == $rid_array[0])
                {
                    printLink($rid, $cid);
                    $trueResults++;
                }
            }
        }
        else // Searching all projects.
        {
        	if( getUserPermissions(hexdec($rid_array[0])) != 0 || isProjectAdmin() || isSystemAdmin() ) // Check permissions
    		{
        		printLink($rid, $cid);
            	$trueResults++;
    		}
        }
    }
    
    // Stop the timers and spit out some facts about the search
    $end_time = microtime(true);
    $total_time = $end_time - $start_time;
    $total_time = 1000 * round($total_time, 4);
    $search_time = 1000 * round($search_time - $start_time, 4);
    
    $parse_time = round($total_time - $search_time, 4);
    
    echo "</table>";
    
    echo "<br />Generated $trueResults result(s) in $total_time milliseconds.<br />";
    echo "Searching the index required $search_time milliseconds. Parsing results required $parse_time milliseconds.<br />";
}


// Prints the row in the table which is a link to the record and to the file itself
function printLink($rid, $cid)
{
	$rid_array = explode("-", $rid);
	$pid = hexdec($rid_array[0]);
	$ridEscaped = escape($rid);
	
	global $db;
	
	$query = "SELECT name FROM project WHERE pid=$pid LIMIT 1";
	$query = $db->query($query);
	$query = $query->fetch_assoc();
	// The project name for printing in the table
	$projectName = $query["name"];
	
	$query = "SELECT value FROM p{$pid}Data WHERE id=$ridEscaped AND cid=$cid";
	$query = $db->query($query);
    
    // make sure data was returned
    if($query->num_rows == 1)
    {
    	$fileInfo = $query->fetch_assoc();
    	$fileInfo = simplexml_load_string($fileInfo['value']);
    	// The original filename for printing the link below
    	$filename = $fileInfo->originalName;
    	$type = $fileInfo->type;
    	$type = explode(";", $type);
    	$type = $type[0];
    }
	
	// Get the URL to the record (trivial)
	$urlToRecord = "viewObject.php?rid=$rid";
	// Get the URL to the file
	$urlToFile = getURLFromRecordID($rid, $cid);
	
	// Print out the row in the table
	echo "<tr><td>$projectName</td><td><a href=\"$urlToRecord\">$rid</a></td>";
   	echo "<td><img style=\"border:0\" height=18 width=18 src=\"images/";
   	
   	// Prints out a pretty icon appropriate for the document type. Simple type checking.
   	if ($type == "application/msword" ) echo "doc";
   	else if ($type == "application/pdf" || $type == "application/x-pdf") echo "pdf";
   	else echo "txt";
   	
   	echo "Icon.jpg\" alt=Document></img>&nbsp<a href=\"$urlToFile\">$filename</a></td></tr>";
}

Manager::PrintFooter();
?>
