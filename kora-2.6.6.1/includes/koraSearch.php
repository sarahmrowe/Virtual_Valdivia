<?php
//Need access to the orderBy and dictionary tables from KORA_Search in the external sorting function
$KORA_Search_order;
$KORA_Search_dict;
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

require_once(__DIR__.'/../includes/includes.php');

// Note: This should eventually be moved to use namespaces instead of an
// encapsulating class, but namespaces won't be introducted into PHP until
// 5.3.0, which is currently unstable; I'm uncertain when we want to use them
// in production code as that would necessitate updating PHP on all the servers.
//
// Until such time as we successfully Search and Clause into a KORA namespace,
// I'm renaming the functions KORA_Search and KORA_Clause to avoid name conflicts since
// they're both reasonably common terms.  I still believe that eventually namespaces are
// the preferrable solution, however.

/**
 // KORA::Search - An Library for extracting data from KORA.
 //
 // General Format:
 // $results = KORA::Search( $token,
 //                          $pid,
 //                          $sid,
 //                          $query,
 //                          array('field1', 'field2'),
 //                          array(
 //                              array('field' => 'field1', 'direction' => SORT_ASC),
 //                              array('field' => 'field2', 'direction' => SORT_DESC)
 //                          ),
 //                          $limitStart=0,
 //                          $limitNum=0);
 //
 // Where:
 //
 // $token is an authentication token used to verify access to the requested
 //        project.  This will probably be a (salted) password hash.
 // $pid is the ID of the Project being searched
 // $sid is the ID of the Scheme being searched
 // $query is a Clause object
 // The array of fields represents which controls should be returned in the
 //     dataset.  By default, the kid, pid, and scheme id are included with
 //     every record.  Also, the list of records which associate to this
 //     record are included as an array with the key 'linkers'.  You can use
 //     the term 'ALL' to return all fields, but PLEASE avoid using this wherever possible.
 // The sixth argument is an array, in order, of arrays representing the fields by which
 //     to order the results.  Such fields must be among the fields returned and must be
 //     stored in associative arrays with "field" referring to the field to order by and
 //     "direction" set to SORT_ASC or SORT_DESC for ascending or descending search.
 // The final two arguments correspond to the two-argument syntax of MySQL's LIMIT
 //     command.  The first corresponds to the first record from the set to be returned
 //     (assuming that the list is a zero-indexed array), and the second argument
 //     corresponds to the maximum number of results to be returned.
 //     NOTE: Unlike MySQL's LIMIT, using LIMIT here does NOT improve performance in
 //     any way since the records still must be all pulled from the database, ordered in
 //     software, and only THEN can the LIMIT be done.  This is provided solely for
 //     convienience rather than for perf!
 **/

function KORA_Search(
$authenticationToken,
$projectID,
$schemeID,
$queryClause,
$fieldsToReturn,
$orderBy = array(),
$limitStart = 0,
$limitNum = 0
)
{
	global $db;
	global $KORA_Search_order;
	global $KORA_Search_dict;

	$KORA_Search_order = $orderBy;
	// Initial Checks: Make sure fieldsToReturn is a non-empty array,
	//    that the queryClause is an object, and that the authentication token
	//    is valid to search the specified project

	if (!is_array($fieldsToReturn))
	{
		$fieldsToReturn = array($fieldsToReturn);
	}

	if (empty($fieldsToReturn))
	{
		echo gettext('Please specify at least one field to return');
		return;
	}

	if (!is_object($queryClause))
	{
		echo gettext('Query must be a Clause object');
		return;
	}

	// The limit arguments must be non-negative integers
	$limitStart = ((int) $limitStart > 0) ? (int) $limitStart : 0;
	$limitNum = ((int) $limitNum > 0) ? (int) $limitNum : 0;

	// Strip any associative keys from the orderBy array by copying the array
	// The ordering procedure assumes that there are no associative key things and that
	// it can use orderBy[0] to get the first element, etc. so we merely ensure that here
	$newOrderBy = array();
	foreach($orderBy as $key => $o)
	{
		$newOrderBy[] = $o;
	}
	$orderBy = $newOrderBy;
	unset($newOrderBy);

	// Verify the authentication token
	if (!in_array($projectID, getTokenPermissions($authenticationToken)))
	{
		echo gettext('Invalid Authentication to Search').'!';
		return;
	}

	// Build the dictionary of controls.  $dictionary is a mapping of name => cid
	// and reverseDictionary is a mapping of cid => name

	$controlTable = 'p'.$projectID.'Control';
	$dictQuery = "SELECT $controlTable.cid AS cid, $controlTable.name AS name, ";
	$dictQuery .= "$controlTable.type AS type, control.file AS file, control.xmlPacked AS xmlPacked ";
	$dictQuery .= "FROM $controlTable LEFT JOIN control ON ($controlTable.type = control.class) ";
	$dictQuery .= "WHERE $controlTable.schemeid = $schemeID";
	$dictQuery = $db->query($dictQuery);

	$dictionary = array();
	$reverseDictionary = array();
	$controlLibrary = array();


	while ($dictRow = $dictQuery->fetch_assoc())
	{
		$dictionary[$dictRow['name']] = array('cid' => $dictRow['cid'], 'xmlPacked' => $dictRow['xmlPacked'], 'type' =>$dictRow['type']);
		$reverseDictionary[$dictRow['cid']] = $dictRow['name'];
		$controlLibrary[$dictRow['cid']] = array('class' => $dictRow['type'], 'file' => $dictRow['file']);
	}

	$KORA_Search_dict = $dictionary;
	// Build the list of fields to return
	// The initial 0 is for the implied list of reverse associators
	$returnFields = array(0);
	// see if we're supposed to return everything
	if (in_array('ALL', $fieldsToReturn))
	{
		foreach($dictionary as $field)
		{
			$returnFields[] = $field['cid'];
		}
	}
	else
	{
		foreach($fieldsToReturn as $field)
		{
			if (isset($dictionary[$field]))
			{
				$returnFields[] = $dictionary[$field]['cid'];
			}
			else
			{
				echo gettext('Unknown control').": $field";
				return;
			}
		}
	}

	// Get list of ids to return records for
	$idList = $queryClause->queryResult($dictionary, $projectID, $schemeID);
	

	// if there are no results stop processing.
	if(sizeof($idList)==0) return array();

	// Extract the actual Data
	$dataQuery  = 'SELECT id, cid, schemeid, value FROM p'.$projectID.'Data WHERE ';
	$dataQuery .= 'id IN ('.implode(',',$idList).') ';
	$dataQuery .= 'AND cid IN ('.implode(',',$returnFields).') ';
	$dataQuery .= 'ORDER BY id, cid';
	$dataQuery = $db->query($dataQuery);
	
	// assemble the data into a useful form
	$dataRecords = array();
	while($r = $dataQuery->fetch_assoc())
	{
		if (!isset($dataRecords[$r['id']]))
		{
			// Populate each row initially with kid, pid, sid
			$dataRecords[$r['id']] = array('kid' => $r['id'], 'pid' => $projectID, 'schemeID' => $r['schemeid'], 'linkers' => array());
		}
		// Look up the name of the control and use it as the index in the record array.
		// Instantiate an empty instance of the control which this is an instance of
		// and use its method to format the value (potentially XML) to one appropriate
		// for search Results
			
		if ($r['cid'] != 0)
		{
			require_once(basePath.'controls/'.$controlLibrary[$r['cid']]['file']);
			$theControl = new $controlLibrary[$r['cid']]['class'];
			$dataRecords[$r['id']][$reverseDictionary[$r['cid']]] = $theControl->storedValueToSearchResult($r['value']);
		}
		else
		{
			// This is the list of reverse associators
//			print_rr($r['value']);
			$xml = simplexml_load_string($r['value']);
			if (isset($xml->assoc))
			{
				foreach($xml->assoc as $assoc)
				{
					$dataRecords[$r['id']]['linkers'][] = (string)$assoc->kid;
				}
				// remove duplicates
				$dataRecords[$r['id']]['linkers'] = array_unique($dataRecords[$r['id']]['linkers']);
			}
		}
	}
	
	
	// Ensure that all records have at least /something/ for each key
	// The & acts like it would in C++, allowing us to modify the values of the
	// array inside the foreach loop (PHP5 only)
	if (!in_array('ALL', $fieldsToReturn))
	{
		foreach ($dataRecords as &$record)
		{
			foreach($fieldsToReturn as $field)
			{
				if (!isset($record[$field]))
				{
					$record[$field] = '';
				}
			}
		}
		unset($record);     // Because this is a call-by-reference, it's good
		// to unset the variable to avoid any unintential modification
		// later
	}
	else
	{
		foreach($dataRecords as &$record)
		{
			foreach($dictionary as $field)
			{
				if (!isset($record[$reverseDictionary[$field['cid']]]))
				{
					$record[$reverseDictionary[$field['cid']]] = '';
				}
			}
		}
		unset($record);
	}

	if (!empty($orderBy))
	{
		$validSortFields = true;
		// Make sure all the requested order-by fields are valid, as are the sort directions
		foreach($orderBy as $orderField)
		{
			if (!(in_array($orderField['field'], $fieldsToReturn) ||
			(in_array('ALL', $fieldsToReturn) && in_array($orderField['field'], $dictionary)) || $orderField['field'] == 'kid'))
			{
				$validSortFields = false;
			}
			if (!in_array($orderField['direction'], array(SORT_ASC, SORT_DESC)))
			{
				$validSortFields = false;
			}
		}

		if ($validSortFields)
		{
			usort($dataRecords,'multiSort');
			//array_multisort($sortCommand);
			//eval($sortCommand);
			//Now we have to fix the array keys, usort doesn't preserve them
			$dataRecords2 = array();
			foreach($dataRecords as $record)
			{
				$dataRecords2[$record['kid']] = $record;
			}
			$dataRecords = $dataRecords2;
		}
	}

	if ($limitNum > 0 || $limitStart >= 0)
	{
		// handle the limit query
		if ($limitStart >= count($dataRecords))
		{
			// A start point after the end was requested; return an empty
			// set.
			$dataRecords = array();
		}
		else if ($limitNum == 0)
		{
			// Start at the specified point and pull to the end
			$dataRecords = array_slice($dataRecords, $limitStart);
		}
		else
		{
			$dataRecords = array_slice($dataRecords, $limitStart, $limitNum);
		}
	}
	return $dataRecords;
}

//The multi-project kora search.  Uses the same basic KORA_Clause functionality to get
//the record IDs to return, but combines, sorts, and limits the results before performing
//a full data retrieval
/**
 * MPF_Search - An extension of KORA_Search for cross-project searches within the KORA API
 *
 * General Format:
 * $results = MPF_Search( $token,
 *                          array('pid1','pid2'),
 *                          array('sid1','sid2'),
 *                          $query,
 *                          array('field1', 'field2'),
 *                          array(
 *                          	'field' => array('field1','field2')
 *                          	'direction' => SORT_ASC | SORT_DESC
 *                              'byProject' => true | false
 *                              'pattern' => "<perl regular expression>"
 *                          ),
 *                          $limitStart=0,
 *                          $limitNum=10);
 *
 * Where:
 *
 * $token is an authentication token used to verify access to the requested
 * 	project.  It is assumed that the same token provides access to all projects in
 * 	the pid array.
 * The second argument is an array of project IDs to search over.  These projects must all
 * 	contain the fields to return, the fields to sort by, and the fields being searched over.
 * The third argument is an array of scheme IDs; the order must match the order of the project IDs
 * 	(i.e. sid1 is a scheme in pid1, etc).
 * $query is a KORA_Clause object
 * The array of fields represents which controls should be returned in the
 * 	dataset.  By default, the kid, pid, and scheme id are included with
 * 	every record.  Also, the list of records which associate to this
 * 	record are included as an array with the key 'linkers'.  You can use
 * 	the term 'ALL' to return all fields, but PLEASE avoid using this wherever possible.
 * The sixth argument is an array with 2 required and 2 optional entries.  'fields' is an
 *  array of control names to be used for sorting.  The sort function will use the first of
 *  these fields that contains data to sort the containing record. 'direction' is either SORT_ASC
 *  or SORT_DESC, specifying the direction in which the sort should be applied.  The optional
 *  arguments are 'byProject' (a boolean) and 'pattern' (a valid perl regex pattern string).
 *  If byProject is set and true, the sorting will be applied on a per-project bases and the
 *  results concatenated; otherwise all results will be combined and sorted in a cross-project
 *  manner.  If pattern is not empty, preg_match will be applied to the contents of sorting fields
 *  before sorting takes place.
 * The seventh and eighth arguments are a point in the records to start at (0-indexed) and
 * 	the number of records to return, respectively.  This is handled by first sorting all
 * 	possible results then using array_slice and a final query to extract the details for
 * only those records dspecified by these arguments.
 *
 * Sorting Notes:
 * For xmlPacked controls, the contents will be extracted from the xml using the control type's
 *  extraction function; sorting will be done on the first entry in the extracted array. and sorted
 *  based on the first entry in the array returned.
 * Sorting is performed in a case-insensitive manner.
 * For a sort byProject, natsort is used because the current need for by-project sorting
 *  is the alpha-numeric InventoryControlIDs; neither numeric nor string sorting is appropriate
 *  for these.
 **/
function MPF_Search($token,
$pids,
$sids,
$clause,
$fieldsToReturn,
$sortby,
$limitStart=0,
$limitNum=0){
	global $db;
	$aggregate_1 = 0;
	
	if (!is_array($fieldsToReturn))
	{
		$fieldsToReturn = array($fieldsToReturn);
	}

	if (empty($fieldsToReturn))
	{
		echo gettext('Please specify at least one field to return');
		return;
	}

	if (!is_object($clause))
	{
		echo gettext('Query must be a Clause object');
		return;
	}

	if(!is_array($sortby) || !is_array($sortby['fields']) || !isset($sortby['direction'])){
		echo gettext('Invalid sorting options');
		return;
	}
	$sortFields = $sortby['fields'];
	$direction = $sortby['direction'];
		
	$extraIDs = array();
	$sortRecords = array();
	$dictionary = array();
	$reverseDictionary = array();
	$controlLibrary = array();

	//Populate the control library - project independent
	$controlQuery = "SELECT control.file AS file, control.class as class, control.xmlPacked as xmlPacked FROM control";
	$controls = $db->query($controlQuery);
	while($con = $controls->fetch_assoc()){
		$controlLibrary[$con['class']] = $con;
	}
	//The controls will have to be instantiated later to extract data from xml formatted controls
	foreach($controlLibrary as $con){
		require_once(basePath.'controls/'.$con['file']);
	}

	$start = microtime(true); //TIMING
	//Begin processing each project
	foreach($pids as $key=>$pid){
		$conTable = "p".$pid."Control";
		$dataTable = "p".$pid."Data";
		$sid = $sids[$key];
		
		// Verify the authentication token
		if (!in_array($pid, getTokenPermissions($token)))
		{
			echo gettext('Invalid Authentication to Search').'!';
			return;
		}

		//Get a control dictionary, converting control names to internal cids
		//Also a reverse dictionary to convert cids to control names
		$dictQuery = "SELECT $conTable.cid AS cid, $conTable.name AS name, ";
		$dictQuery .= "$conTable.type AS type ";
		$dictQuery .= "FROM $conTable ";
		$dictQuery .= "WHERE $conTable.schemeid = $sid";
		$dictQuery = $db->query($dictQuery);
		
		while ($dictRow = $dictQuery->fetch_assoc())
		{
			$dictionary[$pid][$dictRow['name']] = array('cid' => $dictRow['cid'], 'xmlPacked'=>$controlLibrary[$dictRow['type']]['xmlPacked'],'type' =>$dictRow['type']);
			$reverseDictionary[$pid][$dictRow['cid']] = $dictRow['name'];
		}

		$sortMap = array();
		$sortOrder = array(); //Decide which records to keep for sorting process
		foreach($sortFields as $order=>$sf){
			if(!isset($dictionary[$pid][$sf])){
				echo gettext('Control '.$sf.' not present in project '.$pid);
				return;
			}
			$sortMap[$sf] = $dictionary[$pid][$sf]['cid'];
			$sortOrder[$dictionary[$pid][$sf]['cid']] = $order;
		}
		
		$now = microtime(true); //TIMING
		// Get list of ids to return records for
		$ids = $clause->queryResult($dictionary[$pid], $pid, $sids[$key]);
		$then = microtime(true); //TIMING
		$aggregate_1 += $then - $now; //TIMING
		
		//Project has no records matching the search criteria, skip it
		if(empty($ids)){
			continue;
		}

		$sortQuery = "SELECT id,cid,value FROM $dataTable WHERE cid IN ('".
		implode("','",$sortMap)."') AND id IN (".
		implode(",",$ids).")";
		$result = $db->query($sortQuery);

		//This means this project has matching records but no data for the field(s) to sort on,
		// so it's going to end up at the end of the sorted list
		if(!is_object($result) || $result->num_rows < 1){
			foreach($ids as $key=>$id){
				$ids[substr($id,1,-1)] = '';
				unset($ids[$key]);
			}
//			$extraIDs = array_merge($extraIDs,$extras);
			continue;
		}
		
		$tempSort = array();
		//Trying to create the id->value list correctly in one pass ... looks messy but seems necessary
		while($res = $result->fetch_assoc()){
			//If the value is xml-encoded, extract a more usable format
			$control = $reverseDictionary[$pid][$res['cid']];
			if($dictionary[$pid][$control]['xmlPacked']){
				$theControl = new $dictionary[$pid][$control]['type'];
				$value = $theControl->storedValueToSearchResult($res['value']);
				if(is_array($value))$value = trim(current($value));
			}
			else{
				$value = trim($res['value']);
			}
			
			//If a pattern is supplied, apply it here
			if(!empty($sortby['pattern'])){
				$matches = array();
				$didMatch = preg_match($sortby['pattern'],$value,$matches);
				if($didMatch)
				{
					$value = $matches[1];
				}
				else $value = ''; //Give the sorting field a high comparison value so it's put at the end
			}
			
			//Skip multi-list/multi-text entries with no data and records that don't match a pattern, if present
			if(empty($value))continue;
			//Improve quality of sorting comparison
			$value = strtolower($value);
			$kid = $res['id'];
			
			//Check whether a value with a higher precedence (lower order) is already present
			foreach($sortOrder as $so){
				//This means my data is the highest priority read so far, so store in the result array
				if ($so == $sortOrder[$res['cid']]){
					if(!empty($sortby['byProject']) && $sortby['byProject'] == true){
						$projRecords[$kid] = $value;
					}
					else{
						$sortRecords[$kid] = $value;
					}
					//Store a copy of the record that retains a reference to which cid it came from
					$tempSort[$kid][$so] = $value;
				}
				//If there is data in tempSort for a higher priority cid, break out of this loop
				else if(isset($tempSort[$kid][$so]))break;
			}
		}
		//If sort is by-project, do it now
		//Use natsort/natcasesort to try and deal with the huge variety of id types used by different projects
		if(!empty($sortby['byProject']) && $sortby['byProject'] == true){
			natcasesort($projRecords);
			if($direction == SORT_DESC){
				$projRecords=array_reverse($projRecords,true);//True preserves the keys
			}
			$sortRecords = array_merge($sortRecords,$projRecords);
		}
		//Fix the format of the ids from SQL-ready to returnable ... see about avoiding this step?
		foreach($ids as $key=>$id){
			$ids[substr($id,1,-1)] = '';
			unset($ids[$key]);
		}
		$extras = array_diff_key($ids,$tempSort);
		$extraIDs = array_merge($extraIDs,$extras);
	}
	//sortRecords now contains the complete list of kid-value pairs to be sorted
	//while extraIDs contains the ids which have no data for the sort fields
	
	if(empty($sortby['byProject']) || $sortby['byProject'] == false){
		//sort the sortable records
		if($direction == SORT_ASC){
			asort($sortRecords);
		}
		else{
			arsort($sortRecords);
		}
	}
	
	//merge these so the extraIDs end up at the end
	$allRecords = array_merge($sortRecords,$extraIDs);
	
	//now take the desired limit
	$limitRecords = array();
	if ($limitNum > 0 || $limitStart >= 0)
	{
		if ($limitNum == 0)
		{
			// Start at the specified point and pull to the end
			$limitRecords = array_slice($sortRecords, $limitStart);
		}
		else if ($limitStart < count($sortRecords))
		{
			// So long as the start point requested isn't outside the possible records
			$limitRecords = array_slice($sortRecords, $limitStart, $limitNum);
		}
	}
	
	$finalIDs = array();
	//Separate the records to return by project
	foreach($limitRecords as $kid=>$lim){
		$record = Record::ParseRecordID($kid);
		$pid = $record['project'];
		if(!isset($finalIDs[$pid])){
			$finalIDs[$pid] = array($kid);
		}
		else{
			$finalIDs[$pid][] = $kid;
		}
	}
	
	//Retrieve the final data for records to return
	$finalRecords = array();
	foreach($finalIDs as $pid=>$kids){
		$cids = array();
		if (!in_array('ALL', $fieldsToReturn)) 
		{
			foreach($fieldsToReturn as $f)
			{
				$cids[] = $dictionary[$pid][$f]['cid'];
			}
		}
		else {
			foreach($dictionary[$pid] as $f)
			{ 
				$cids[] = $f['cid'];
			}
		}
		
		$dataTable = "p".$pid."Data";
		$query = "SELECT * FROM $dataTable WHERE ";
		$query .= "cid IN ('".implode("','",$cids)."')";
		$query .= " AND id IN ('".implode("','",$kids)."')";
		$dataQuery = $db->query($query);
		$dataRecords = array();
		while($r = $dataQuery->fetch_assoc())
		{
			if (!is_array($limitRecords[$r['id']]))
			{
				// Populate each row initially with kid, pid, sid
				$limitRecords[$r['id']] = array('kid' => $r['id'], 'pid' => $pid, 'schemeID' => $r['schemeid'], 'linkers' => array());
			}
			// Look up the name of the control and use it as the index in the record array.
			// Instantiate an empty instance of the control which this is an instance of
			// and use its method to format the value (potentially XML) to one appropriate
			// for search Results

			if ($r['cid'] != 0)
			{
				$name = $reverseDictionary[$pid][$r['cid']];
				$type = $dictionary[$pid][$name]['type'];
				$theControl = new $type;
				$limitRecords[$r['id']][$reverseDictionary[$pid][$r['cid']]] = $theControl->storedValueToSearchResult($r['value']);
			}
			else
			{
				// This is the list of reverse associators
				$xml = simplexml_load_string($r['value']);
				if (isset($xml->assoc))
				{
					foreach($xml->assoc as $assoc)
					{
						$limitRecords[$r['id']]['linkers'][] = (string)$assoc->kid;
					}
					// remove duplicates
					$limitRecords[$r['id']]['linkers'] = array_unique($limitRecords[$r['id']]['linkers']);
				}
			}
		}

		//$finalRecords = array_merge($finalRecords, $dataRecords);
	}
	// Ensure that all records have at least /something/ for each key
	// The & acts like it would in C++, allowing us to modify the values of the
	// array inside the foreach loop (PHP5 only)
	if (!in_array('ALL', $fieldsToReturn))
	{
		foreach ($limitRecords as &$record)
		{
			foreach($fieldsToReturn as $field)
			{
				if (!isset($record[$field]))
				{
					$record[$field] = '';
				}
			}
		}
		unset($record);     // Because this is a call-by-reference, it's good
		// to unset the variable to avoid any unintential modification
		// later
	}
	else
	{
		foreach($limitRecords as &$record)
		{
			$pid = $record['pid'];
			foreach($dictionary[$pid] as $field)
			{
				if (!isset($record[$reverseDictionary[$pid][$field['cid']]]))
				{
					$record[$reverseDictionary[$pid][$field['cid']]] = '';
				}
			}
		}
		unset($record);
	}

	//Return the total # of results that match the search for display purposes
	//This should probably be unset by the calling function to limit interaction
	//with existing display code
	$limitRecords['count'] = count($allRecords);
	return $limitRecords;
}

/**
 // KORA_Scheme_Layout: Gets the collection/control layout of a KORA scheme - for use
 // in output formatting
 // Format:
 // $layout = KORA_Scheme_Layout($token,
 // 							 $pid,
 //								 $sid);
 // Where:
 //
 // $token is an authentication token used to verify access to the requested
 //        project.  This will probably be a (salted) password hash.
 // $pid is the ID of the Project
 // $sid is the ID of the Scheme for which the layout will be returned
 //
 // Returns:
 //
 // A 2 dimensional array - the keys of the outer array are collection names,
 // the values are arrays which contain all controls in that collection, in sequence order
 //
 // Notes:
 //       1) This function will not return collections which contain no controls
 // 	  2) Collection id 0 and any controls assigned to it are not returned
 *
 * @param string $authenticationToken
 * @param int $projectID
 * @param int $schemeID
 * @return array(2 dimensional)
 */

function KORA_Scheme_Layout(
$authenticationToken,
$projectID,
$schemeID)
{
	global $db;
	$conTable = "p".$projectID."Control";

	// Verify the authentication token
	if (!in_array($projectID, getTokenPermissions($authenticationToken)))
	{
		echo gettext('Invalid Authentication to Search').'!'."<br>";
		return false;
	}

	//Token is valid - execute query to get collection-control mapping for this scheme
	//Note that controls with collid 0 will not be returned because they have no entry in
	//the collections table - this is a good thing
	$colConQuery = "SELECT col.name AS 'Col',con.name AS 'Con' ";
	$colConQuery .= "FROM collection AS col, $conTable AS con ";
	$colConQuery .= "WHERE col.collid = con.collid AND con.schemeid = $schemeID ";
	$colConQuery .= "ORDER BY col.sequence, con.sequence;";

	$colCon = $db->query($colConQuery);
	if(!is_object($colCon) || $colCon->num_rows < 1)
	{
		echo gettext('No Collections Exist For This Scheme').'.';
		return false;
	}

	//Iterate over the results building the array to return
	$ret = array();
	while($control = $colCon->fetch_assoc())
	{
		$col = $control['Col'];
		if(!isset($ret[$col]))
		{
			$ret[$col] = array();
		}
		$ret[$col][] = $control['Con'];
	}

	return $ret;
}


/**
 // KORA_Associated_Records: Gets the KIDs of any records that have an association to the specified record
 //
 // Format:
 // $options = KORA_Associated_Records($token,
 // 							 $pid,
 //								 $sid, 
 //								 $kid,
 //								 $controlName);
 // Where:
 //
 // $token is an authentication token used to verify access to the requested
 //        project.  This will probably be a (salted) password hash.
 // $pid is the ID of the Project
 // $sid is the ID of the Scheme
 // $kid is the ID of the Record that we need to find which records are associated to it
 // $controlName is the control (must be an associator) to search over
 //
 // Returns:
 //
 // An array of the kids which have an associations with $kid
 */
function KORA_Associated_Records(
$authenticationToken,
$projectID,
$schemeID,
$recordID,
$controlName)
{
	global $db;
	$conTable = "p" . $projectID . "Control";
	$dataTable = "p" . $projectID . "Data";

	// Verify the authentication token
	if (!in_array($projectID, getTokenPermissions($authenticationToken)))
	{
		echo gettext('Invalid Authentication to Search') . '!' . "<br>";
		return false;
	}

	//Execute query to get the options string of the selected control
	$optionsQuery = "SELECT cid, options, type FROM $conTable WHERE schemeid = $schemeID AND name='$controlName'";
	$opts = $db->query($optionsQuery);
	if(!is_object($opts) || $opts->num_rows != 1)
	{
		echo gettext('Selected Control')." $controlName ".gettext('Does Not Exist');
		return false;
	}

	//Control names are unique, so only 1 row should be returned
	$options = $opts->fetch_assoc();

	if($options['type'] != 'AssociatorControl')
	{
		echo gettext('Selected Control') . " $controlName " . gettext('Is Not an Associator Control');
		return false;
	}
	
	$targetCid = $options['cid'];

	$query = "SELECT id FROM $dataTable WHERE schemeid = $schemeID AND cid=$targetCid AND value LIKE '%$recordID%'";
	$results = $db->query($query);
	$recordsWithAnAssociation = $results->fetch_assoc();

	return $recordsWithAnAssociation;

}


/**
 // KORA_Scheme_Layout: Gets the options for a list or multi-list control
 //
 // Format:
 // $options = KORA_Scheme_Layout($token,
 // 							 $pid,
 //								 $sid,
 //								 $controlName);
 // Where:
 //
 // $token is an authentication token used to verify access to the requested
 //        project.  This will probably be a (salted) password hash.
 // $pid is the ID of the Project
 // $sid is the ID of the Scheme
 // $controlName is the control (which must be a list or multi-list) to return options for
 //
 // Returns:
 //
 // An array of the options available at ingestion for $controlName
 //
 // Notes:
 //

 */

function KORA_List_Options(
$authenticationToken,
$projectID,
$schemeID,
$controlName)
{
	global $db;
	$conTable = "p".$projectID."Control";

	// Verify the authentication token
	if (!in_array($projectID, getTokenPermissions($authenticationToken)))
	{
	echo gettext('Invalid Authentication to Search').'!'."<br>";
		return false;
	}

	//Execute query to get the options string of the selected control
	$optionsQuery = "SELECT options, type FROM $conTable WHERE schemeid = $schemeID AND name='$controlName'";
	$opts = $db->query($optionsQuery);
	if(!is_object($opts) || $opts->num_rows != 1)
	{
		echo gettext('Selected Control')." $controlName ".gettext('Does Not Exist');
		return false;
	}

	//Control names are unique, so only 1 row should be returned
	$options = $opts->fetch_assoc();

	if($options['type'] != 'ListControl' && $options['type'] != 'MultiListControl')
	{
		echo gettext('Selected Control')." $controlName ".gettext('Is Not a List or Multi-List');
		return false;
	}

	//Parse the options string to extract the array of options
	$toReturn = array();
	$xml = simplexml_load_string($options['options']);
	foreach($xml->option as $xmlOption)
	{
		$toReturn[] = (string)$xmlOption;
	}
	return $toReturn;
}
/**
 // KORA_Clause: Useful for building up queries such as the following (note that "IN"
 // is case sensitive)
 //
 // $clause1 = new KORA_Clause('myText', 'LIKE', '%foo%');
 // $clause2 = new KORA_Clause('myText', 'LIKE', '%bar%');
 // $clause3 = new KORA_Clause($clause1, 'OR', $clause2);
 // $clause4 = new KORA_Clause('anotherText', '=', 'happy');
 // $clause5 = new KORA_Clause($clause3, 'AND', $clause4);
 // $clause6 = new KORA_Clause($clause4, 'IN', array('1', 'text'));
 //
 // If the field name is "ANY" (case-sensitive), then the search will look for any
 // field which matches the criterion.  Note that you CANNOT use "ANY" and "IN" in
 // the same query.
 //
 // KORA_Search also supports searching by KID (Kora Identifier) in any
 // of the following formats (note that "KID" and "IN" are case sensitive):
 //
 // $clause6 = new KORA_Clause('KID', '=', '1-1-1');
 // $clause7 = new KORA_Clause('KID', '!=', '1-1-1');
 // $clause8 = new KORA_Clause('KID', 'IN', array('1-1-1', '1-1-2'));
 //
 // Quirks:
 //
 // Control Names are case sensitive
 // Boolean operators must be AND or OR (case sensitive)
 // queryResult is expected to be called only from within KORA::Search, so it does
 //     not escape any data.  It expects all inputs to be SQL-safe.
 **/
class KORA_Clause
{
	function __construct($argument1, $op, $argument2)
	{
		$this->arg1 = $argument1;
		$this->arg2 = $argument2;
		$this->operator = $op;


		if ((is_string($argument1) && is_string($argument2)) || ($op == 'IN' && is_array($argument2) && !empty($argument2)))
		{
			if (($op == 'IN') & !is_array($argument2))
			{
				$this->arg2 = array($this->arg2);
			}
			$this->clauseType = 'Logical';
		}
		else if (is_object($argument1) && is_object($argument2) && (in_array($op, array('AND', 'OR'))) )
		{
			$this->clauseType = 'Boolean';
		}
		else
		{
			$this->clauseType = 'Undefined';
		}
	}

	public function isGood()
	{
		return (in_array($this->clauseType, array('Logical', 'Boolean')));
	}

	//Returns an associative array of KIDs that meet the query's conditions.
	//If the query clause is logical, an sql query is executed to retrieve the
	//related id results.  Otherwise the clause is a boolean, so merge
	//the results of its left and right arguments appropriately
	public function queryResult($controlDictionary = array(), $projectID, $schemeID){
      
		global $db;
		$ret_array = array();
        
 
		// == operator should have the same functionality as the = operator in this situation.
		// mySQL only has the = operator.
		if($this->operator == "==")
		{
			$this->operator = "=";
		}

		$dataTable = "p$projectID"."Data";
		$query = "SELECT DISTINCT id FROM $dataTable WHERE schemeid = '$schemeID' AND ";
		if($this->clauseType == 'Logical'){
			if ($this->arg1 == 'ANY')
			{
				if ($this->operator == '=')
				{
					$query .= ' ((value '.$this->operator." '".$this->arg2."') OR ";
					$query .= ' (value LIKE \''.KORA_Clause::xmlFormatted($this->arg2, false).'\' ))';
				}
				else if (in_array($this->operator, array('!=', '<>')))
				{
					$query .= ' ((value '.$this->operator." '".$this->arg2."') AND ";
					$query .= ' (value NOT LIKE \''.KORA_Clause::xmlFormatted($this->arg2, false).'\' ))';
				}
				else
				{
					$query .= ' (value '.$this->operator." '".$this->arg2."') ";
				}
			}
            
            
			elseif (strtoupper($this->arg1) == 'KID')
			{
				// fix arg2 so that queries like kid != '' will work correctly.
//				if (empty($this->arg2)) $this->arg2 = "''";
				
				if ($this->operator == 'IN')
				{
					// escape the terms for use in the query
					foreach($this->arg2 as &$arg) 
					{
						if ($arg[0] != "'" && $arg[1] != "'")
							$arg = "'".$arg."'";
					}
					$query .= ' (id IN ('.implode(',', $this->arg2).')) ';
				}
				else
				{
					$query .= ' (id '.$this->operator." '".$this->arg2."') ";
				}
			}
			elseif (isset($controlDictionary[$this->arg1]))
			{
              
     
                
				if (strtoupper($this->operator) == 'IN')
				{
					$query .= ' (cid = '.$controlDictionary[$this->arg1]['cid'];
					$query .= ' AND (';
					$i = 0;
					foreach($this->arg2 as $arg)
					{
						if ($i > 0) $query .= ' OR ';
						$query .= " value LIKE '";
						if ($controlDictionary[$this->arg1]['xmlPacked'])
						{
							$query .= KORA_Clause::xmlFormatted($arg, false);
						}
						else
						{
							$query .= $arg;
						}
						$query .= "'";
						$i++;
					}
					$query .= ')) ';
				}
                
				else
				{
					if ($this->operator == '=')
					{
						// handle XML-packed data fields
						if ($controlDictionary[$this->arg1]['xmlPacked'])
						{
							// We presume that people using exact operators realize
							// such operators don't care about things like %, so we just
							// throw the brackets right on the outside
							$query  .= ' (cid = '.$controlDictionary[$this->arg1]['cid'];
							if(!empty($this->arg2)){
								$query .= ' AND value LIKE \''.KORA_Clause::xmlFormatted($this->arg2, false)."') ";
							}else{
								// 'empty' xmlPacked controls will not have a row set in the db.
								$query .= ' AND id NOT IN (SELECT DISTINCT id FROM p'.$projectID.'Data WHERE cid='.$controlDictionary[$this->arg1]['cid'].') )';
							}
						}
						else
						{
							$query  .= ' (cid = '.$controlDictionary[$this->arg1]['cid'];
							$query .= ' AND value '.$this->operator." '".$this->arg2."') ";
						}
					}
					else if (in_array($this->operator, array('<>')))
					{
						$query  .= ' (cid = '.$controlDictionary[$this->arg1]['cid'];
						// handle XML-packed data fields
						if ($controlDictionary[$this->arg1]['xmlPacked'])
						{
							if(!empty($this->arg2)){
								// We presume that people using exact operators realize
								// such operators don't care about things like %, so we just
								// throw the brackets right on the outside
								$query .= ' AND value NOT LIKE \''.KORA_Clause::xmlFormatted($this->arg2, false)."') ";
							}else{
								// We can't check if xmlPacked controls are 'not empty' with the same method
								// because the generated string will look like "NOT LIKE '% ><%'",
								// which will match all xml with more than one tag in it.
								// Instead, we just check if the row exists because an 'empty'
								// control will not be set in the db.
								$query .=' )';
							}
						}
						else
						{
							$query .= ' AND value '.$this->operator." '".$this->arg2."') ";
						}
						// catch records where no data is filled in for that control
						if (empty($this->arg2))
						{
							$query  = $query.' OR ';
							$query .= ' id NOT IN (SELECT DISTINCT id FROM p'.$projectID.'Data WHERE cid='.$controlDictionary[$this->arg1]['cid'].')';
						}
						
						echo $query;
					}
					else if ($this->operator == "!="){
						$query  .= ' (cid = '.$controlDictionary[$this->arg1]['cid'];
						// handle XML-packed data fields
						if ($controlDictionary[$this->arg1]['xmlPacked'])
						{
							if(!empty($this->arg2)){
								// We presume that people using exact operators realize
								// such operators don't care about things like %, so we just
								// throw the brackets right on the outside
								$query .= ' AND value NOT LIKE \''.KORA_Clause::xmlFormatted($this->arg2, false)."') ";
							}else{
								// We can't check if xmlPacked controls are 'not empty' with the same method
								// because the generated string will look like "NOT LIKE '% ><%'",
								// which will match all xml with more than one tag in it.
								// Instead, we just check if the row exists because an 'empty'
								// control will not be set in the db.
								$query .=' )';
							}
						}
						else
						{
							$query .= ' AND value '.$this->operator." '".$this->arg2."') ";
						}
					}
					else if (strtoupper($this->operator) == 'NOT LIKE')
					{
						// If they're using LIKE syntax we assume they know what they're doing
						// with percentage signs, brackets, etc. and don't alter it
						$query  .= ' ( cid = '.$controlDictionary[$this->arg1]['cid'];
						$query .= ' AND value NOT LIKE \''.$this->arg2."') ";
						// catch records where no data is filled in for that control
						if (empty($this->arg2))
						{
							$query = $query.' OR ';
							$query .= ' id NOT IN (SELECT DISTINCT id FROM p'.$projectID.'Data WHERE cid='.$controlDictionary[$this->arg1]['cid'].')';
						}
						
					}
                 
                    else if(strtoupper($this->operator)=='LIKE'){
						//If it s LIKE. find a cid because it's control specific
						$controlQuery = "SELECT cid FROM p".$projectID."Control WHERE schemeid=".$schemeID." AND name = '$this->arg1'";
						$controlResult = $db->query($controlQuery)->fetch_assoc();
						//add specific cid for LIKE comparison to main query
						$query .='cid = "'.$controlResult['cid'].'" AND ';
						
						//Convert special chars to match the encoded values in the db.
						$encoded_keyword = preg_replace_callback('/[\x{80}-\x{10FFFF}]/u', function ($m) {
						$char = current($m);
						$utf = iconv('UTF-8', 'UCS-4', $char);
						return sprintf("&#x%s;", ltrim(strtoupper(bin2hex($utf)), "0"));
						}, $this->arg2);
						
						$query .= ' (value LIKE '.escape($this->arg2).' OR value LIKE '.escape($encoded_keyword).') ';
							
					
                    }
              
					else // we assume they're using something else where
					{    // they know what they're doing
                      
						$query .= ' (cid = '.$controlDictionary[$this->arg1]['cid'];
						$query .= ' AND value '.$this->operator." '".$this->arg2."') ";
                     
					}
                    
				}
			}
			else
			{
				echo gettext('Unknown control').': '.$this->arg1."<br/>\n";
//				echo "<pre>",print_r(array_keys($controlDictionary)),"</pre>";
				$query .= '(1=1)';
			}
			
			//If clause is logical, execute the mysql query
//			echo "<br>$query<br><br>";
			$result = $db->query($query);
			if (!$result){
				echo "query: $query<br>";
				echo $db->error."<br><br />";
				echo "Query Error, please check your KORA Clauses.<br/>";
			}else{
				while($r = $result->fetch_assoc()){
					$ret_array[] = "'".$r['id']."'";
				}
			}
//			echo "<pre>";print_r($ret_array);echo "</pre>";
		}
		else if ($this->clauseType == 'Boolean')
		{
			//To do an array union, merge the arrays then remove duplicates
			if ($this->operator != 'AND')
			{
				$array_left = $this->arg1->queryResult($controlDictionary, $projectID, $schemeID);
				$array_right = $this->arg2->queryResult($controlDictionary, $projectID, $schemeID);
				$ret_array = array_merge($array_left, $array_right);
				$ret_array =  array_unique($ret_array);
			}
			//An and of results is just an intersection of the id results
			else
			{
				$array_left = $this->arg1->queryResult($controlDictionary, $projectID, $schemeID);
				$array_right = $this->arg2->queryResult($controlDictionary, $projectID, $schemeID);
				$ret_array = array_intersect($array_left, $array_right);
			}
		}
		else
		{
			echo gettext('Error').': '.gettext('Bad Search Clause')."<br/>\n";
//			echo "arg1: ",var_dump($this->arg1),"<br>";
//			echo "op: ",var_dump($this->operator),"<br>";
//			echo "arg2: ",var_dump($this->arg2),"<br>";
		}

		return $ret_array;
	}

	private function xmlFormatted($arg, $operatorIsLIKE)
	{
		if ($operatorIsLIKE)
		{
			if (in_array($arg[0], array('%', '_')))
			{
				$arg = $arg[0].'>'.substr($arg, 1);
			}
			else
			{
				$arg = '>'.$arg;
			}

			if (in_array($arg[strlen($arg) - 1], array('%', '_')))
			{
				$arg = substr($arg, 0, strlen($arg) - 1).'<'.substr($arg, -1, 1);
			}
			else
			{
				$arg = $arg.'<';
			}
		}
		else
		{
			$arg = '%>'.$arg.'<%';
		}

		return $arg;
	}

	private $arg1;
	private $operator;
	private $arg2;
	private $clauseType;
}

/**
 * Merges an arbitrary number of KORA_Clause objects with a Boolean Joined.
 * Returns FALSE if invalid array of clauses or invalid boolean given
 *
 * object joinKORAClauses(array(KORA_Clause), $boolean = ('AND' || 'OR'))
 */
function joinKORAClauses($clauses, $boolean)
{
	if (!is_array($clauses) || empty($clauses) || !in_array($boolean, array('AND', 'OR')))
	{
		return false;
	}

	// Otherwise, we presume we're good
	//
	// Basic Algorithm: While there's more than one
	// Query left in the array, pop two, merge them,
	// and add them to the end
	while(count($clauses) > 1)
	{
		$clause1 = array_shift($clauses);
		$clause2 = array_shift($clauses);
		$clauses[] = new KORA_Clause($clause1, $boolean, $clause2);
	}

	// There should be only one clause left, which is what we need
	return array_pop($clauses);
}

//Use this to do complex sorting
/**
 * This function is to be called by usort.  It is to be used to do sorting on KORA_Search results.
 * It requires that the order array is made available at the global scope.  It also expects a global
 * dictionary array so that it can check for Date controls and process them specially.
 *
 */
function multiSort($left, $right)
{
	global $KORA_Search_order;
	global $KORA_Search_dict;

	//Test the pair of search results according to each of the sort criteria
	foreach($KORA_Search_order as $ord)
	{
		//If the criteria is the kid, split and compare using just the record piece as an integer
		if(strtolower($ord['field']) == 'kid'){
			$ridl = Record::ParseRecordID($left['kid']);
			$ridr = Record::ParseRecordID($right['kid']);
			if($ord['direction'] == SORT_ASC)
			{
				if((int)($ridl['record']) < (int)($ridr['record']))	return -1;
				else if((int)($ridl['record']) > (int)($ridr['record']))return 1;
			}
			else
			{
				if((int)($ridl['record']) > (int)($ridr['record']))	return -1;
				else if((int)($ridl['record']) < (int)($ridr['record']))return 1;
			}
		}
		//If the criteria field is a date control, do special processing
		else if($KORA_Search_dict[$ord['field']]['type'] == 'DateControl')
		{
			$l = $left[$ord['field']];
			$r = $right[$ord['field']];
			//Direction to move result depends on the sort direction for this criteria
			if($ord['direction'] == SORT_ASC){

				// we assume that an era not set is CE
				if(empty($l['era']) && !empty($r['era']) && $r['era'] == 'BCE' ) return 1;
				else if(empty($r['era']) && !empty($l['era']) && $l['era'] == 'BCE' ) return -1;
				else if(!empty($r['era']) && !empty($l['era'])){
					if($l['era']  == 'BCE' && $r['era'] == 'CE')return -1;
					else if($l['era'] == 'CE' && $r['era'] == 'BCE' )return 1;
					else if($l['era'] == 'BCE' && $r['era'] == 'BCE' ){
						// BCE dates are ordered in reverse, so we switch arguments.
						return DateControl::CompareDates($l,$r);
					}
				}
				return DateControl::CompareDates($r,$l);
			}
			else if($ord['direction'] == SORT_DESC){

				// we assume that an era not set is CE
				if(empty($l['era']) && !empty($r['era']) && $r['era'] == 'BCE' ) return -1;
				else if(empty($r['era']) && !empty($l['era']) && $l['era'] == 'BCE' ) return 1;
				else if(!empty($r['era']) && !empty($l['era'])){
					if($l['era']  == 'BCE' && $r['era'] == 'CE')return 1;
					else if($l['era'] == 'CE' && $r['era'] == 'BCE' )return -1;
						else if($l['era'] == 'BCE' && $r['era'] == 'BCE' ){
						// BCE dates are ordered in reverse, and SORT_DESC is the reverse of SORT_ASC,
						// so we call compareDate() normally.
						return DateControl::CompareDates($r,$l);
					}
				}

				// compareDate() sorts in SORT_ASC, so we reverse the arguments.
				return DateControl::CompareDates($l,$r);
			}
		}
		//If the sort field isn't kid or date - handle arrays, and do simple comparison sorting
		else{
			if(is_array($left[$ord['field']])){
				$l = $left[$ord['field']][0];
			}
			else
			{
				$l = $left[$ord['field']];
			}
			if(is_array($right[$ord['field']]))
			{
				$r = $right[$ord['field']][0];
			}
			else
			{
				$r = $right[$ord['field']];
			}

			if($ord['direction'] == SORT_ASC){

				if($l < $r)return -1;
				else if($l > $r)return 1;
			}
			else
			{
				if($l > $r)return -1;
				else if($l < $r)return 1;
			}
		}
	}
	return 0;
}

/**
 * Boolean search function for KORA.  This function returns record ids for any
 * record where any control specified in $fieldsToSearch matches the boolean
 * conditions in $keywordString. This function should be used in all front-end
 * search boxes.
 *
 * This function only returns an array of ids. A normal KORA_Search call must
 * be used to get record information, and can be used to further limit results.
 *
 * For 'advanced search' style searches where you want to check if a specific
 * control matches a specific value, it is recommended that you do a
 * KORA_BooleanSearch on every individual control and then aggregate the
 * results. This will allow you to use the keyword parsing functionality in
 * every input box.
 *
 * Results from this function should be used in a KORA_Clause like this:
 * $clause = new KORA_Clause('KID','IN',$results).
 *
 * Valid operators for $keywordString (boolean operators are case insensitive):
 *
 * AND  All terms will be joined with AND if no operator is specified. There is
 * 		no need to use this operator (ever) as all space characters are treated
 * 		as AND. However if the search term 'and' is desired, double quotation
 * 		marks will have to be used for it.
 *
 * OR   Groups any terms between OR operators.
 *
 * ""   Double quotation marks signify a literal string to be matched.
 * 		Non-matching quotation marks will be treated as part of the search terms.
 *
 * -    The minus operator may be used to negate terms.
 *
 * @param String $authenticationToken
 * @param Integer $projectID
 * @param Integer $schemeID
 * @param String $keywordString - Boolean keyword string. Does not need to be
 * 		escaped. It will be unescaped regardless so that the boolean terms may
 * 		be processed correctly, then each search term will be escaped.
 * @param Array $fieldsToSearch - Controls to search over.
 * @param Array $keywords - You may pass an array for this argument to get the
 * 		processed list of keywords used in the search. Negated keywords will not
 * 		be added.  (Useful for match highlighting.)
 * @return Array $idList - An array of matching record ids.
 */
function KORA_BooleanSearch(
$authenticationToken,
$projectID,
$schemeID,
$keywordString,
$fieldsToSearch = array(),
&$keywords = array()
)
{
	global $db;
	
	$projectID = (int)$projectID;
	$schemeID = (int)$schemeID;
	
	if(!is_string($keywordString)){
		$keywordString = '';
	}
	
	if (!is_array($fieldsToSearch))
	{
		$fieldsToSearch = array($fieldsToSearch);
	}

	// Verify the authentication token
	if (!in_array($projectID, getTokenPermissions($authenticationToken)))
	{
		echo gettext('Invalid Authentication to Search').'!';
		return;
	}

	// Build the dictionary of controls.  $dictionary is a mapping of name => cid
	// and reverseDictionary is a mapping of cid => name

	$controlTable = 'p'.$projectID.'Control';
	$dictQuery = "SELECT $controlTable.cid AS cid, $controlTable.name AS name, ";
	$dictQuery .= "$controlTable.type AS type, control.file AS file, control.xmlPacked AS xmlPacked ";
	$dictQuery .= "FROM $controlTable LEFT JOIN control ON ($controlTable.type = control.class) ";
	$dictQuery .= "WHERE $controlTable.schemeid = $schemeID";
	$dictQuery = $db->query($dictQuery);

	$dictionary = array();
	$reverseDictionary = array();
	$controlLibrary = array();

	while ($dictRow = $dictQuery->fetch_assoc())
	{
		$dictionary[$dictRow['name']] = array('cid' => $dictRow['cid'], 'xmlPacked' => $dictRow['xmlPacked'], 'type' =>$dictRow['type']);
		$reverseDictionary[$dictRow['cid']] = $dictRow['name'];
		$controlLibrary[$dictRow['cid']] = array('class' => $dictRow['type'], 'file' => $dictRow['file']);
	}

	// Build the list of fields to search over
	$searchFields = array();
	// see if we're supposed to return everything
	if (!in_array('ALL', $fieldsToSearch) && !empty($fieldsToSearch))
	{
		foreach($fieldsToSearch as $field)
		{
			if (isset($dictionary[$field]))
			{
				$searchFields[] = $dictionary[$field]['cid'];
			}
			else
			{
				echo gettext('Unknown control').": $field";
				return;
			}
		}
	}
	
	// Get list of ids to return records for
	$idList = booleanKeywordSearch($keywordString,$projectID,$schemeID,$searchFields,$keywords);

	return $idList;
}
?>
