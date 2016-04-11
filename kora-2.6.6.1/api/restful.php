<?php 

/** Copyright (2008) Matrix: Michigan State University

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
along with this program.  If not, see <http://www.gnu.org/licenses/>. **/

//allow other servers
header('Access-Control-Allow-Origin: *');

define('KORA_RESTFUL_MAX_QUERY_RECURSE', 5);
define('KORA_RESTFUL_MAX_QUERY_CHAIN', 5);
define('KORA_RESTFUL_TN_SMALL', 250);
define('KORA_RESTFUL_TN_LARGE', 1000);

//require_once('../includes/exportCSV.php');
require_once('../includes/koraSearch.php');
require_once('../includes/grid/conf.php');
require_once('../model/manager.php');
// INCLUDE ALL CONTROL CLASSES AS WE MAY NEED THEM
foreach (glob("../controls/*.php") as $filename){
	if ($filename != '../controls/index.php') {
		require_once($filename);
	}
}
	
	/* 
	 * ///////////////////////////////////////////////////////////////////////////
	 * ////////database.php///////////////////////////////////////////////////////
	 * ///////////////////////////////////////////////////////////////////////////
	 * 
	 * REST Web Application API for KORA. Allows direct interaction with database.
	 * 
	 * ///////////////////////////////////////////////////////////////////////////
	 * ///////////////////////////////////////////////////////////////////////////
	 * 
	 * Requirements (sent by HTML-GET):
	 * --------------------------------
	 * 	request - Type of API request to execute (GET/INSERT/UPDATE/DELETE)
	 * 	pid     - ID of the requested project
	 * 	sid     - ID of the requested scheme
	 * 	token   - Token key to authenticate access to project
	 * 
	 *  
	 * GET Specific Options
	 * --------------------
	 *	query   - Optional query to limit your returned records (i.e. provide search parameters), 
	 *                if query is not specified it returns all records in this schema
	 *		  Syntax should be like this:
	 *		  	[(]ControlName,MatchOperator,Value[)][,BoolOperator,[[(]ControlName,MatchOperator,Value[)]]...
	 *                Here are some formatting examples (using MySQL like syntax for descriptions):
	 *			[simple query to return a single record with KID=XX-XX-XX]
	 *			query=KID,=,XX-XX-XX 
	 *			[returns records where (Control named FieldName1 is LIKE '%example%') AND (Control named FieldName2 NOT EQUAL 'badname') 
	 *			query=(FieldName1,LIKE,example),and,(FieldName2,!=,badname) 
	 *			[complicated example where ((Control FieldName1 EQUAL 'value1') OR (((FieldName2 EQUAL 'value2') OR (FieldName2 EQUAL 'value2')) AND (FieldName3 LIKE '%value4%))
	 *			query=(FieldName1,eq,value1),or,(((FieldName2,eq,value2),or,(FieldName2,eq,value3)),and,(FieldName3,like,value4))
	 *		
	 *		  ControlName: Name of the Control in Kora Schema
	 *		  Value: String to compare to value of Control in Kora Schema
	 *		  MatchOperators (case-insensitive): 
	 *			'=','==','eq': control matches exactly value
	 *			'!=','ne': control does not match exactly value
	 *			'like': control contains value
	 *		  BoolOperators (case-insensitive):
	 *			'&&','and': clause1 AND clause2 are both true
	 *			'||','or': clause1 OR clause2 is true
	 *
	 *		  Further notes on 'query'
	 *		  Note1: You can chain several clauses together at a single nesting level, however see Note2 
	 *                       [i.e. (Control1,eq,value1),or,(Control1,eq,value2),or,(Control3,like,value3)...]
	 *                Note2: If you chain several queries at the same level you cannot mix/match and/or or it will error out
	 *                       [i.e. This is INVALID (Control1,eq,value1),or,(Control1,eq,value2),and,(Control3,like,value3)...]
	 *                       because mixing and/ors at the same priority level is ambiguous
	 *
	 *	fields	- Optional comma-delimited list of Kora Controls to return,
	 *		  if fields is not specified it returns Controls that are set up in Kora flagged as 'Display In Results'
	 *		  Example1: ControlName (returns only values for ControlName)
	 *		  Example2: ControlName1,ControlName2,ControlName3 (returns values for ControlName1,ControlName2,ControlName3)
	 *		  Example3: ALL (special keyword to return all fields in the schema)
	 *		  Example4  (if this field is omitted, it will return the list of fields selected as 'showinsearch' from Kora schema settings
	 *                Note: All options for 'fields' interact accordingly with the 'showkid', 'showpid', etc options that follow
	 *
	 *	showkid - 'yes' => show 'kid' as field in results | 'no' => don't show 'kid' as field in results (default: yes)
	 *	showpid - 'yes' => show 'pid' as field in results | 'no' => don't show 'pid' as field in results (default: no)
	 *	showsid - 'yes' => show 'schemeID' as field in results | 'no' => don't show 'schemeID' as field in results (default: no)
	 *	showlinkers - 'yes' => show 'linkers' as field in results | 'no' => don't show 'linkers' as field in results (default: no)
	 *	showsystimestamp - 'yes' => show 'systimestamp' as field in results | 'no' => don't show 'systimestamp' as field in results (default: no)
	 *	showrecordowner - 'yes' => show 'recordowner' as field in results | 'no' => don't show 'recordowner' as field in results (default: no)
	 *
	 *  sort    - Single optional Kora Control to sort by,
	 *        If not specified, Results will be sorted by KID
	 *        Example1: ControlName
	 *  order   - If you use sort, you'll also have to use order to pick how to show the sort, forwards or backwards
	 *        Example1: SORT_ASC  <- Forwards (A,B,C,...)
	 *        Example2: SORT_DESC <- Backwards (Z,Y,X,...)       
	 *
	 *  first    - first record you want returned similar to MySQL LIMIT X,Y where 'start' == X
	 *  count    - number of record you want returned similar to MySQL LIMIT X,Y where 'count' == Y
	 * 
	 *  Grid output specific options
	 * 	  gr_title     - Title of the graph
	 * 	  gr_pagesize  - Number of records per page (default=1)
	 * 	  gr_theme     - Grid theme style (default=dot-luv)
	 * 	               --(dot-luv,absolute,aristo,black-beauty,blackandred,clean,cupertino,dark-round,duck,excite-bike,
	 *  	                 flick,overcast,pepper-grinder,purple-haze,redmond,smoothness,start,tiffany,ui-darkness,
	 *  	                 ui-lightness)
	 * 	  gr_height    - Height of grid in pixels (default=300)
	 * 	  gr_width     - Width of grid in pixels (default=400)
	 * 	  gr_search    - Make the graph searchable (default=No,Yes)
	 *
	 *  HTML output specific options
	 *    html_showempty - Show control titles with empty divtags when value == '' (default: no)
	 *
	 * INSERT / UPDATE Specific Options
	 * -----------------------
	 * 	xml      - The complete xml for a record that you want to ingest or update
	 *   Ex 1: <?xml version="1.0" encoding="ISO-8859-1"?>
     *         <Data><ConsistentData/><Record><id>2-9-0</id><systimestamp>2013-04-05T14:55:47-04:00</systimestamp>
     *         <recordowner>koraadmin</recordowner><Title>Yosemite</Title><Taken>4/5/2000 CE</Taken>
     *         <Photo originalName="yosemite-stream.jpg">2-9-0-42-yosemite-stream.jpg</Photo><Categories>landscape</Categories>
     *         <Categories>lake</Categories></Record></Data>
	 *  zipFile  - The .zip filepath containing files and images for the record
	 *        Example1: FILE_PATH/API_Demo-demo_Scheme-files_1.zip
	 *		   Example2: You may also pass the zipfile through the $_FILE stream
	 *
	 * UPDATE / DELETE Specific Options
	 * -----------------------
	 * 	rid - Specify the Record ID (RID) to delete
	 *        Example1: 1
	 *        Example1: 2A (KORA uses hexadecimal digits, be careful about getting the right RID)
	 * 
	 *
	 * ///////////////////////////////////////////////////////////////////////////
	 * ///////////////////////////////////////////////////////////////////////////
	 * ///////////////////////////////////////////////////////////////////////////
	 * 
	 */

	// FIRST CHECK SECURITY FUNDAMENTAL INFO TO SEE IF WE CAN CONTINUE
	if (!isset($_REQUEST['pid'])) { die("No project id specified and is required for all actions"); }
	if (!isset($_REQUEST['sid'])) { die("No scheme id specified and is required for all actions"); }
	if (!isset($_REQUEST['token'])) { die("No authentication token specified and is required for security"); }
	if (!isset($_REQUEST['request'])) { die("RESTful api requires request method, see documentation"); }
	Manager::Init();
	if (Manager::CheckRequestsAreSet(['pid', 'sid'])) {
	}
	$pid = $_REQUEST['pid'];
	$sid = $_REQUEST['sid'];
	$tok = $_REQUEST['token'];
	$req = $_REQUEST['request'];
	
	// Check Request
	if (!in_array(strtolower($req), array('get', 'insert', 'update', 'delete'))) { die("Invalid request method specified."); }
	
	// Check KORA Token
	if (!in_array($pid, getTokenPermissions($tok))) {
		echo gettext('Invalid Authentication to Search').'!'."<br>";
		return false;
	}
	 
	$controls = array();
	
	/////////////////////////////////
	////GET REQUEST//////////////////
	/////////////////////////////////
	// pull information from the kora database
	// example url (w/o options): ........../database.php?request=GET&pid=1&sid=1&token=*********&size=1
	// example url (w/ options) : ........../database.php?request=GET&pid=1&sid=1&token=*********&size=1&title=graph&rpp=1&theme=dot-luv&height=300&width=400&search=No&sort=Title&order=SORT_ASC      
	if($req == 'GET'){
	
		// FIND CLAUSES
		$qstr = isset($_REQUEST['query']) ? $_REQUEST['query'] : 'KID,!=,\'\'';
	
		// FIND DISPLAY TYPE
		$dtype = isset($_REQUEST['display']) && in_array($_REQUEST['display'], array('grid','json','xml','csv','plugin','html','tn','detail')) ? $_REQUEST['display'] : 'html';

		// ON FAILURE TO PARSE QUERY, SET QUERY KID == '' WHICH SHOULD ALWAYS YEILD 0 RESULTS, DISPLAY THEN HANDLES RETURN APPROPRIATELY
		$query = ParseQuery($qstr) or $query = new KORA_Clause('KID','==','');
		
		// GET REQUESTED FIELDS USING FUNCTION
		$fields = GetRequestFields();
		if (empty($fields))
			$fields = 'ALL';
		
		// FIND SORT BY OPTIONS
		/* Only doing simple sort, meaning one control. KORA_Search can handle more controls to make it more exact.
		 * TODO: Find a way to give more than one control to $_REQUEST['sort'] and then iterate through.
		 * then basically make more $sortArray# variables and append those to $simpleSortArray.
		 * Once you start doing that, rename $simpleSortArray to $AdvSortArray or something -JG
		 */
		if (isset($_REQUEST['sort'])) {
			$sort = $_REQUEST['sort'];
			//if order isn't set, assume sort by ascending order
			if ($_REQUEST['order'] == 'SORT_DESC') {
				$order = SORT_DESC;
			} else {
				$order = SORT_ASC;
			}
			$sortArray1 = array('field'=>$sort, 'direction'=>$order);
		}
		// If not set then no sort is empty array
		$simpleSortArray = isset($sortArray1) ? array($sortArray1) : array();
		
		// LIMITS
		$first = isset($_REQUEST['first']) ? $_REQUEST['first'] : 0;
		$count = isset($_REQUEST['count']) ? $_REQUEST['count'] : 0;
		
		// NOW DO A SIMPLE KORA SEARCH AS WE HAVE GATHERED ALL OF OUR COMPONENTS
		$results = KORA_Search($tok,$pid,$sid,$query,$fields,$simpleSortArray,$first,$count);
		
		DisplayRecords($results, $dtype);
	}
	
	/////////////////////////////////
	////INSERT REQUEST///////////////
	/////////////////////////////////
	//  post information to the kora database - CREATE
	//  Files - There are two methods to passing files:
	//		Via $_FILES: Passing through file stream using an html form
	//		Via $_REQUEST (GET/POST): Passing the file path to file as a string
	// example url: .../database.php?request=INSERT&pid=1&sid=1&token=*********&xml="<record>...</record>"&zipFile="Path/Filename.zip"
	else if ($req == 'INSERT'){
		if (Manager::CheckRequestsAreSet(['xml'])) {
			//init
			$xml="";
			$zipFile="";
			$ziptype="";
			//grab requsted
			$xml = $_REQUEST['xml'];
			if (isset($_FILES['zipFile'])) {
				$zipFile = $_FILES['zipFile'];
				$ziptype="FILES";
			} else if (isset($_REQUEST['zipFile'])) {
				$zipFile = fopen($_REQUEST['zipFile'], 'r');
				$ziptype="REQUEST";
			}
			//ingest
			XMLimport($xml, $zipFile, $ziptype);
		}
	}
	
	/////////////////////////////////
	////UPDATE REQUEST///////////////
	/////////////////////////////////
	///put information into the kora database - UPDATE
	//  Files - There are two methods to passing files:
	//		Via $_FILES: Passing through file stream using an html form
	//		Via $_REQUEST (GET/POST): Passing the file path to file as a string
	// example url: .../database.php?request=UPDATE&pid=1&sid=1&&rid=1&token=*********&xml="<record>...</record>"&zipFile="Filename.zip"
	else if ($req == 'UPDATE'){
		if (Manager::CheckRequestsAreSet(['xml'])) {
			//init
			$xml="";
			$zipFile="";
			$ziptype="";
			//grab requsted
			$xml = $_REQUEST['xml'];
			if (isset($_FILES['zipFile'])) {
				$zipFile = $_FILES['zipFile'];
				$ziptype="FILES";
			} else if (isset($_REQUEST['zipFile'])) {
				$zipFile = fopen($_REQUEST['zipFile'], 'r');
				$ziptype="REQUEST";
			}
			//ingest (same function as POST/INSERT)
			XMLimport($xml, $zipFile, $ziptype);
		}
	}
	
	/////////////////////////////////
	////DELETE REQUEST///////////////
	/////////////////////////////////
	///delete information from the kora database
	// example url: .../database.php?request=UPDATE&pid=1&sid=1&&rid=1&token=*********
	else if ($req == 'DELETE'){
		if (Manager::CheckRequestsAreSet(['pid']) && Manager::CheckRequestsAreSet(['sid']) && Manager::CheckRequestsAreSet(['rid'])) {
			echo '<script src="http://code.jquery.com/jquery-1.11.0.min.js"></script>';
			echo '<script src="api.js"></script>';
			echo '<script type="text/javascript">
				MappingManager.deleteSubmit("'.$_REQUEST['pid'].'","'.$_REQUEST['sid'].'","'.$_REQUEST['rid'].'");
				</script>';	
		}
	}
	
	///invalid request response
	else{
		
		echo "Error: Invalid API request type given.";
		
	}
	
	function DisplayRecords($koradata_, $dtype)
	{
		global $pid,$controls,$fields;

		// ARRAY OF FIELDS THAT ARE RETURNED FROM KORA_SEARCH THAT AREN'T NORMAL CONTROLS
		$spfields = array('kid','pid','schemeID','linkers','systimestamp','recordowner');
		
		// CLEAN OUT SPECIAL FIELDS THAT AREN'T REQUESTED FOR DISPLAY (KID DEFAULTS TO TRUE, OTHERS TO FALSE)
		$showkid = (isset($_REQUEST['showkid']) && (strtoupper($_REQUEST['showkid']) == 'NO')) ? false : true;
		$showpid = (isset($_REQUEST['showpid']) && (strtoupper($_REQUEST['showpid']) == 'YES')) ? true : false;
		$showsid = (isset($_REQUEST['showsid']) && (strtoupper($_REQUEST['showsid']) == 'YES')) ? true : false;
		$showlinkers = (isset($_REQUEST['showlinkers']) && (strtoupper($_REQUEST['showlinkers']) == 'YES')) ? true : false;
		$showsystimestamp = (isset($_REQUEST['showsystimestamp']) && (strtoupper($_REQUEST['showsystimestamp']) == 'YES')) ? true : false;
		$showrecordowner = (isset($_REQUEST['showrecordowner']) && (strtoupper($_REQUEST['showrecordowner']) == 'YES')) ? true : false;
		
		foreach ($koradata_ as &$res)
		{
			if (!$showkid) { unset($res['kid']); }
			if (!$showpid) { unset($res['pid']); }
			if (!$showsid) { unset($res['schemeID']); }
			if (!$showlinkers) { unset($res['linkers']); }
			if (!$showsystimestamp) { unset($res['systimestamp']); }
			if (!$showrecordowner) { unset($res['recordowner']); }
		}
				
		switch ($dtype)
		{
		case 'grid':
			// GRID SPECIFIC OPTIONS
			$gridThemes = array('dot-luv','absolute','aristo','black-beauty','blackandred','clean','cupertino',
				'dark-round','duck','excite-bike','flick','overcast','pepper-grinder','purple-haze','redmond',
				'smoothness','start','tiffany','ui-darkness','ui-lightness');

			$title = isset($_REQUEST['gr_title']) ? $_REQUEST['gr_title'] : 'KORA DB GRAPH';
			$rpp   = isset($_REQUEST['gr_pagesize']) && is_numeric($_REQUEST['gr_pagesize']) && ($_REQUEST['gr_pagesize']>0) ? $_REQUEST['gr_pagesize'] : 10;
			$theme = isset($_REQUEST['gr_theme']) && in_array($_REQUEST['gr_theme'], $gridThemes) ? $_REQUEST['gr_theme'] : 'dot-luv';
			$height = isset($_REQUEST['gr_height']) && is_numeric($_REQUEST['gr_height']) ? $_REQUEST['gr_height'] : 300;
			$width = isset($_REQUEST['gr_width']) && is_numeric($_REQUEST['gr_width']) ? $_REQUEST['gr_width'] : 400;
			$search = isset($_REQUEST['gr_search']) && (strtolower($_REQUEST['gr_search']) == 'yes') ? 'Yes' : 'No';

			$records = array();
			foreach ($koradata_ as $kid => $res)
			{
				// NEED TO TURN ALL ARRAYS INTO STRINGS FOR GRID'S SAKE
				foreach ($res as $ctrl => &$ctl)
				{
					// HANDLE 'LINKERS' FIELD SPECIFICALLY
					if ($ctrl == 'linkers')
					{ $ctl = implode($ctl,','); continue; }
					// LEAVE DATA FOR SPECIAL FIELDS ALONE
					elseif (in_array($ctrl,$spfields)) { continue; }
					// ELSE, FOR SPECIAL FIELDS CONVERT IT TO NORMAL OUTPUT VIEW
					$ctlDisplay = new $controls[$ctrl]['type']($pid,$controls[$ctrl]['cid'],$kid);
					$ctl = $ctlDisplay->showData();
				}
				
				array_push($records, $res);
			}
			
			///process and echo results as a table
			$dg = new C_DataGrid($records);
			
			$dg->set_caption($title);
			$dg->set_pagesize($rpp);
			$dg->set_theme($theme);
			$dg->set_dimension($width, $height, true);
			if($search=='Yes'){
				$dg->enable_search(true);
			}else{
				$dg->enable_search(false);
			}
			$dg->display();
			break;
		case 'json':
			print json_encode($koradata_);
			break;
		case 'xml':
			$xmlout = new SimpleXMLElement("<?xml version=\"1.0\"?><kora_data></kora_data>");
			ArrayToXML($koradata_,$xmlout);
			print $xmlout->asXML();
			break;
		case 'csv':
			$csvout = '';
			$csvfields = array();
			// LOOP THROUGH ALL CREATE MASTER LIST OF POTENTIAL FIELDS
			foreach ($koradata_ as $res) 
			{ 
				foreach (array_keys($res) as $key)
				{
					if (!in_array($key, $csvfields))
					{
						$csvout .= "$key,";
						$csvfields[] = $key;
					}
				}
			}
			$csvout .= "\n";
			foreach ($koradata_ as $res)
			{
				foreach ($csvfields as $csvfield)
				{
					// LIST EACH FIELD VALUE IN ORDER, OR OUTPUT A ',' FOR A COMPLETELY EMPTY/MISSING FIELD FROM THIS RECORDS ARRAY
					if (isset($res[$csvfield]))
					{
						// NEED TO TURN ALL ARRAYS INTO STRINGS FOR CSV'S SAKE
						if (is_array($res[$csvfield])) { $res[$csvfield] = implode(' ', $res[$csvfield]); }
						// NEED TO REPLACE SOME CHARACTERS THAT CONFUSE CSV
						$res[$csvfield] = preg_replace('/\n/','&#0A;',$res[$csvfield]);
						$res[$csvfield] = preg_replace('/\r/','',$res[$csvfield]);
						$res[$csvfield] = preg_replace('/,/','&#44;',$res[$csvfield]);
						$csvout .= $res[$csvfield].",";
					}
					else
					{
						$csvout .= ',';
					}
					
				}
				$csvout .= "\n";
			}
			print $csvout;
			break;
		case 'html':
			$htmlout = '';
			$showempty = (isset($_REQUEST['html_showempty']) && (strtoupper($_REQUEST['html_showempty']) == 'YES')) ? true : false;
			foreach ($koradata_ as $kid => $koraobj)
			{
				$htmlout .= "<div class='koraobj_container'>\n";
				// OUTPUT THE KID ALSO
				$htmlout .= "\t<div class='koraobj_control koraobj_control_kid' ><div class='koraobj_control_label'>KID</div>";
				$htmlout .= "<div class='koraobj_control_value'>".$kid."</div></div>\n";
				foreach ($fields as $dfield)
				{
					if (isset($koraobj[$dfield]))
					{
						// SKIP THIS ROW UNLESS SHOWEMPTY HAS BEEN SET TO TRUE
						if ((!$showempty) &&  $koraobj[$dfield] == '') { continue; }
						$ctlDisplay = new $controls[$dfield]['type']($pid,$controls[$dfield]['cid'],$kid);
						$htmlout .= "\t<div class='koraobj_control koraobj_control_".htmlentities($controls[$dfield]['name'],ENT_QUOTES)."' ><div class='koraobj_control_label'>".$controls[$dfield]['name']."</div>";
						$htmlout .= "<div class='koraobj_control_value'>" . 
							     $ctlDisplay->showData() . 
							     "</div></div>\n";
					}
				}
				$htmlout .= "</div>\n";
			}
			
			print $htmlout;
			break;
		//New case same as html, but with additional plugin options, such as a checkbox to select the container
		case 'plugin':
			$htmlout = '';
			$showempty = (isset($_REQUEST['html_showempty']) && (strtoupper($_REQUEST['html_showempty']) == 'YES')) ? true : false;
			//$count = 1;
			foreach ($koradata_ as $kid => $koraobj)
			{
				$htmlout .= "<div class='koraobj_container' id='".$kid."'>\n";
				//$htmlout .= "<input type='checkbox' id='$kid' value='$kid' name='checked[]'>";
				//$count += 1;
				// OUTPUT THE KID ALSO
				$htmlout .= "\t<div class='koraobj_control koraobj_control_kid' ><div class='koraobj_control_label'>KID</div>";
				$htmlout .= "<div class='koraobj_control_value'>".$kid."</div></div>\n";
				$htmlouttemp = "";
				foreach ($fields as $dfield)
				{
					if (isset($koraobj[$dfield]))
					{
						// SKIP THIS ROW UNLESS SHOWEMPTY HAS BEEN SET TO TRUE
						if ((!$showempty) &&  $koraobj[$dfield] == '') { continue; }
						$ctlDisplay = new $controls[$dfield]['type']($pid,$controls[$dfield]['cid'],$kid);
						// Want to display the image first so check to see if it's type ImageControl
						if(get_class($ctlDisplay) == "ImageControl"){
							$htmlout .= "\t<div class='koraobj_control koraobj_control_".htmlentities($controls[$dfield]['name'],ENT_QUOTES)."' ><div class='koraobj_control_label'>".$controls[$dfield]['name']."</div>";
							$htmlout .= "<div class='koraobj_control_value'>" . 
							     $ctlDisplay->showData() . 
							     "</div></div>\n";
						}
						else{
							$htmlouttemp .= "\t<div class='koraobj_control koraobj_control_".htmlentities($controls[$dfield]['name'],ENT_QUOTES)."' ><div class='koraobj_control_label'>".$controls[$dfield]['name']."</div>";
							$htmlouttemp .= "<div class='koraobj_control_value'>" . 
							     $ctlDisplay->showData() . 
							     "</div></div>\n";
						}
					}
				}
				$htmlout .= $htmlouttemp;
				$htmlout .= "</div>\n";
			}
			print $htmlout;
			break;
		case 'detail':
			$htmlout = '';
			$htmlpictureout = '';
			$showempty = (isset($_REQUEST['html_showempty']) && (strtoupper($_REQUEST['html_showempty']) == 'YES')) ? true : false;
			foreach ($koradata_ as $kid => $koraobj)
			{
				$htmlout .= "<div class='koraobj_container'>\n";
				// OUTPUT THE KID ALSO
				$htmlout .= "\t<div class='koraobj_control koraobj_control_kid' ><div class='koraobj_control_label'>KID</div>";
				$htmlout .= "<div class='koraobj_control_value'>".$kid."</div></div>\n";
				
				foreach ($fields as $dfield)
				{	
				
					if (isset($koraobj[$dfield])){
						// SKIP THIS ROW UNLESS SHOWEMPTY HAS BEEN SET TO TRUE
						if ((!$showempty) &&  $koraobj[$dfield] == '') { continue; }
						$ctlDisplay = new $controls[$dfield]['type']($pid,$controls[$dfield]['cid'],$kid);
						if(is_a($ctlDisplay,'ImageControl')){
							$htmlout .= "\t<div class='koraobj_control koraobj_control_".htmlentities($controls[$dfield]['name'],ENT_QUOTES)."' ><div class='koraobj_control_label'>".$controls[$dfield]['name']."</div>";
							$imagecontrolstring = $ctlDisplay->showData();
							$imagepos = strrpos($imagecontrolstring,"<div class");
							$htmlout .= "<div class='koraobj_control_value'>" . 
							     substr($imagecontrolstring,0, $imagepos) . 
							     "</div></div>\n";
							$htmlpictureout .= "<div class='koraobj_control_value'>" . 
							     substr($imagecontrolstring,$imagepos) . 
							     "</div>";
						}
						else {
							$htmlout .= "\t<div class='koraobj_control koraobj_control_".htmlentities($controls[$dfield]['name'],ENT_QUOTES)."' ><div class='koraobj_control_label'>".$controls[$dfield]['name']."</div>";
							$htmlout .= "<div class='koraobj_control_value'>" . 
							     $ctlDisplay->showData() . 
								"</div></div>\n";
						}
					}
				}
				$htmlout .= "</div>\n";
			}
			$htmlpictureout = str_replace("/thumbs/", "/", $htmlpictureout);
			print $htmlpictureout;
			print $htmlout;
			break;
		case 'tn':
			// TODO: THIS ARRAY SHOULD REALLY BE A 'ThumbnailAvailable()' TYPE FUNCTION IN EACH CONTROL ABSTRACT CLASS OR
			// ELSE IN THE FUTURE IF WE ADD NEW CONTROL TYPES THAT CAN USE THUMBNAILS WE WILL HAVE TO UPDATE THIS ARRAY HERE
			$validctrls = ['ImageControl'];
			$tnlarge = (isset($_REQUEST['tn_large']) && (strtoupper($_REQUEST['tn_large']) == 'YES')) ? true : false;
			$tnsize = ($tnlarge) ? KORA_RESTFUL_TN_LARGE : KORA_RESTFUL_TN_SMALL;
			$tnclass = ($tnlarge) ? 'koraobj_tn_small' : 'koraobj_tn_large';
			$htmlout = '';
			// TODO: THESE SHOULD REALLY BE A CLASS FUNCTION TO GET PATH(S) ALSO FOR CLASSES WHERE WE SAVE FILES
			// HOPEFULLY USERS WILL USUALLY JUST REQUEST ONE FIELD AND OFTEN JUST ONE OBJ, BUT TREAT IT AS A LOOP ANYWAY
			foreach ($koradata_ as $kid => $koraobj)
			{
				foreach ($fields as $dfield)
				{
					if (isset($koraobj[$dfield]) && is_array($koraobj[$dfield]) && in_array($controls[$dfield]['type'], $validctrls))
					{
						// TODO: PARTS OF THIS TN GENERATION THIS SHOULD BE A PROCEDURE PROBABLY, UTILTIY OR INSIDE CONTROL CLASS
						$ridparts = Record::ParseRecordID($kid);
						$origpath = basePath . "files/{$ridparts['project']}/{$ridparts['scheme']}/{$koraobj[$dfield]['localName']}";
						$thumbspath = basePath . "files/{$ridparts['project']}/{$ridparts['scheme']}/thumbs/{$koraobj[$dfield]['localName']}";
						// APPEND THE DIMENSIONS OF THIS THUMB TO THUMBPATH
						$thumbspath = preg_replace('/(\.[^.]+)$/i',"_x$tnsize".'${1}',$thumbspath);
						// SOME SANITY CHECKING BEFORE TRYING COMPLICATED FILE CREATION CALLS
						if (!file_exists($origpath)) { throw new Exception("File not found for kid [$kid], record is broken."); }
						if (!file_exists($thumbspath) || (filemtime($thumbspath) < filemtime($origpath)))
						{
							if (file_exists($thumbspath)) { unlink($thumbspath); }
							createThumbnail($origpath, $thumbspath, $tnsize, $tnsize);
							if (!file_exists($thumbspath)) { throw new Exception("Error creating thumbnail for [$kid]."); }
						}
						$thumbsurl = baseURI . "files/{$ridparts['project']}/{$ridparts['scheme']}/thumbs/{$koraobj[$dfield]['localName']}";
						$thumbsurl = preg_replace('/(\.[^.]+)$/i',"_x$tnsize".'${1}',$thumbsurl);
						$thumbsurl = preg_replace('/ /','%20',$thumbsurl);
						$imageclip = isset($_REQUEST['tn_imageclip']) ? $_REQUEST['tn_imageclip'] : false;
						if($imageclip == true){
						$htmlout .= "<div class='koraobj_container'>\n";
						$htmlout .= "\t<a href='$thumbsurl' class='kgfs_imgclip' style='background-image: url(".'"'.$thumbsurl.'"'.")' />\n</div>";
						}
						else
							$htmlout .= "<img class='koraobj_tn $tnclass' src='$thumbsurl' />";
					}
				}
			}
			print $htmlout;
			break;
		}
		
	}
	
	function GetRequestFields()
	{
		global $pid,$sid,$fields,$db;
		// THIS IS FILLED IN FOR THE GLOBAL SCOPE
		global $controls;
		
		// DEFAULT FIELDS TO false IF NOT SET, 
		$fields = isset($_REQUEST['fields']) ? explode(',', $_REQUEST['fields']) : false;
		// ALSO IF IT IS SET WITH VALUE == 'ALL' HANDLE THAT AS TO NOT PASS IT IN AS A FIELD NAMED ALL IN ARRAY (I.E. HANDLE THIS AS A SPECIAL-CASE VARIABLE)
		if (isset($_REQUEST['fields']) &&  (strtoupper($_REQUEST['fields']) == 'ALL')) { $fields = 'ALL'; }
		
		// GET THE LIST OF CONTROLS FOR THIS SCHEME FOR LATER DISPLAY USE
		$controlQuery = 'SELECT * FROM p'.$pid.'Control';
		$controlQuery .= ' WHERE schemeid IN ('.$sid.') ';
		$controlQuery .= ' ORDER BY schemeid, cid';
		$controlQuery = $db->query($controlQuery);
		while ($ctl = $controlQuery->fetch_assoc())
		{
			$controls[$ctl['name']] = $ctl;
		}
		
		$dfields = array();
		// IF USER SPECIFICALLY REQUESTED ALL FIELDS, INCLUDE THEM ALL
		if ($fields == 'ALL')
		{
			foreach ($controls as $ctl)
			{ $dfields[] = $ctl['name']; }
		}
		// ELSE, IF REQUESTED FIELDS IS DEFAULTED TO ALL, DEFAULT THE DISPLAY OF THE OBJECT TO DEFAULT KORA SHOWINDISPLAY VALUES
		elseif ($fields === false)
		{
			foreach ($controls as $ctl)
			{ if ($ctl['showInResults'] == '1') { $dfields[] = $ctl['name']; } }
		}
		// ELSE, SHOW ONLY THE ARRAY OF FIELDS THE USER REQUESTED
		else { $dfields = $fields; }
		
		return $dfields;
	}
	
	function ParseQuery($qp_, $rlev=0)
	{
		//print "Procesing $qp_ <br/>";
		if ($rlev > KORA_RESTFUL_MAX_QUERY_RECURSE)
		{ trigger_error("Query nesting level too complex, please limit depth to ".KORA_RESTFUL_MAX_QUERY_RECURSE." at level $qp_", E_USER_ERROR); }

	    	$validouttops = array('AND','OR');
	    	$validintops = array('=','!=','LIKE');
		$qsplit = preg_split('/,/',$qp_);
		
		// IF THIS SPLIT == 3 THEN WE SHOULD BE AT A FINAL OPERATION
		if (sizeof($qsplit) == 3) 
		{ 
			$op = GetOperator($qsplit[1]);
			if (!in_array($op, $validintops)) 
			{ trigger_error("Invalid operator [" . $qsplit[1] . "] at level $qp_", E_USER_ERROR); }
			
			// IF OPERATOR == LIKE, WE APPEND THE WRAPPING '%' FOR KORA/SQL SAKE
			if ($op == 'LIKE') { $qsplit[2] = '%'.$qsplit[2].'%'; }

			//var_dump(new KORA_Clause($qsplit[0], $qsplit[1], $qsplit[2]));
			return new KORA_Clause($qsplit[0], $op, $qsplit[2]);
		}

		// HERE IS THE MAGIC!
		preg_match_all("/\((([^()]*|(?R))*)\)/",$qp_,$matches);
		
		// ITERATE THROUGH AND PERFORM RECURSIVE SEARCHES
		if (count($matches) > 1)
		{
			// REPLACE ORIG STRING WITH PLACEHOLDERS FOR ACCURATE PROCESSING HERE
			$qpr = $qp_;
			$qpm = array();
			for ($i = 0; $i < count($matches[0]); $i++) { $qpr = preg_replace("/\Q".$matches[0][$i]."\E/","[[[m$i]]]",$qpr); }
			for ($i = 0; $i < count($matches[1]); $i++) { $qpm[] = ParseQuery($matches[1][$i], $rlev+1); }
			$qsplit = preg_split('/,/',$qpr);
			//var_dump($qpr);
			//var_dump($qpm);
			$qprsplit = preg_split('/,/',$qpr);
			//var_dump($qprsplit);
			// A PROPER COMPLEX/NESTED QUERY SHOULD HAVE AN ODD NUMBER OF ARGUMENTS AFTER REPLACING THE NESTING STRINGS
			if (sizeof($qprsplit) % 2 != 1) { trigger_error("Invalid parsing at level $qp_, check comma delimiters carefully.", E_USER_ERROR); }
			// IF NUMBER OF 'CHAINS' TOGETHER IN THIS PARATHETIC LEVEL IS > MAX ALLOWED, RETURN FALSE NOW
			if ((sizeof($qprsplit) / 2) > KORA_RESTFUL_MAX_QUERY_CHAIN)
			{ trigger_error("Query chainig level too complex, please limit max and/or at same level to ".KORA_RESTFUL_MAX_QUERY_CHAIN." at level $qp_", E_USER_ERROR); }
			
			// CHECK THE SANITY OF OPERATORS, CAN'T MIX AND MATCH AND/OR AND ALL SHOULD BE AND/OR
			$lastop = GetOperator($qprsplit[1]);
			if (!in_array($lastop, $validouttops)) { trigger_error("Invalid operator [" . $lastop . "] at level $qp_", E_USER_ERROR); }
			for ($i=3; $i<count($qprsplit); $i=$i+2)
			{
				$op = GetOperator($qprsplit[$i]);
				// IF GETOPERATOR RETURNED FALSE OR IT'S NOT AN AND/OR, IT'S INVALID
				if (!in_array($op, $validouttops)) { trigger_error("Invalid operator [" . $op . "] at level $qp_", E_USER_ERROR); }
				// IF LASTOP != THIS OP, WE ARE MIXING AND MATCHING AND/ORS, NO GOOD EITHER
				if ($op != $lastop) { trigger_error("Invalid operator cannot mix AND/OR at level $qp_ it is ambigous.  Use parenthesis to group.", E_USER_ERROR); };
				
				$lastop = $op;
			}
			
			// THIS SHOULD CHECK FOR ANYWHERE DOWN THE CHAIN WE RETURNED FALSE, WE SHOULD HAVE SPIT OUT ERROR ALREADY, NOTHING FURTHER TO REPORT
			foreach ($qpm as $kc) { if (!$kc) { return false; } }
		
			// FINALLY IF WE ARE HERE, WE SHOULD BE ABLE TO JOINKORACLAUSES FOR ALL RETURNED CLAUSES WITH THE LASTOP OPERATOR
			return joinKORAClauses($qpm, $lastop);
			
		}	    
	}
	
	// TURN THE QUERYSTRING OP INTO KORA EQUIV
	function GetOperator($op_)
	{
		$retval = false;
		switch (strtolower($op_))
		{
		case 'eq':
		case '=':
		case '==':
			$retval = '=';
			break;
		case 'ne':
		case '!=':
			$retval = '!=';
			break;
		case 'like':
			$retval = 'LIKE';
			break;
		case 'and':
		case '&&':
			$retval = 'AND';
			break;
		case 'or':
		case '||':
			$retval = 'OR';
			break;
		}
		
		return $retval;
	}

	// function defination to convert array to xml
	function ArrayToXML($data_, &$xml_) {
		foreach($data_ as $key => $value) {
			if(is_array($value)) {
				$key = preg_replace('/ /','_',$key);
				if((!is_numeric($key)) && (!is_numeric($key[0]))){
					// SPECIAL CONDITION FOR US TO HANDLE KORA KIDS
					if (preg_match('/^[\da-f]+-[\da-f]+-[\da-f]+$/i',$key))
					{ $key = "kid$key"; }
					$subnode = $xml_->addChild("$key");
					ArrayToXML($value, $subnode);
				}
				else{
					// SPECIAL CONDITION FOR US TO HANDLE KORA KIDS
					if (preg_match('/^[\da-f]+-[\da-f]+-[\da-f]+$/i',$key))
					{ $key = "kid$key"; }
					else
					{ $key = "item$key"; }
					$subnode = $xml_->addChild("$key");
					ArrayToXML($value, $subnode);
				}
			}
			else {
				if((is_numeric($key)) || (is_numeric($key[0]))) { $key = "item$key"; }
				$xml_->addChild("$key",htmlspecialchars($value));
			}
	    }
	}	
	
	//function to import data from XML files and zip folders (INSERT -OR- UPDATE)
	function XMLimport($xml, $zipFile, $zipType) {
		if ($xml !="") {
			//If there's a zip file, unzip and extract
			if (isset($zipFile)){
				$uploadedFiles = false;
				$zipFiles = true;
				//if a zip folder was uploaded (ie error field != 4), extract files
				if ($zipType=="FILES" && $zipFile['error'] != 4) {
					$uploadedFiles = true;
					$zipFiles = extractZipFolder($zipFile['tmp_name'],$zipFile['name']);
					if(!$zipFiles){
						print '<div class="error">'.gettext('**ERROR: Could not extract from Zip file.').'</div>';
					}
				} else if($zipType=="REQUEST"){
					$uploadedFiles = true;
				}
				else {
					echo "error";
					$uploadedFiles = false;
				}
			} else {
				$uploadedFiles = false;
			}
			
			//IF XML STRING CAN BE VALIDATED, CONTINUE
			if (ValidateXML($xml)){
				$xmlObject = simplexml_load_string($xml);
			} else {
				print '<div class="error">'.gettext('**ERROR: XML file could not be validated.').'</div>';
			}
			
			
			//load record to .js
			if ($xmlObject) {
				//create xml data handler
				$importer = new Importer($_REQUEST['pid'],$_REQUEST['sid'],$uploadedFiles);
				
				//Record data is contained within the Data tag only - other parts of the file
				//are used for scheme import
				//If statement is for backwards compatibility - previously Data was the document root
				if($xmlObject->getName() == 'Scheme')$xmlObject = $xmlObject->Data;
				
				//load data from XML
				if ($xmlObject->ConsistentData) {
					$importer->loadConsistentData($xmlObject->ConsistentData);
				}
	
				$recordArray = array();
				for ($i=0; $i<count($xmlObject->Record); $i++) {
					$importer->loadSpecificData($xmlObject->Record[$i]);
					$recordArray[] = $importer->getRecordData();
				}
				
				//Move to .js for mapping and AJAX call to handleRecord.php
				if(!Manager::CheckRequestsAreSet(['rid'])){
				//INSERT ONLY - new record
					echo '<script src="http://code.jquery.com/jquery-1.11.0.min.js"></script>';
					echo '<script src="'.baseURI.'api/api.js"></script>';
					echo '<script type="text/javascript">
						MappingManager.postSubmit("'.$_REQUEST['pid'].'","'.$_REQUEST['sid'].'",'. json_encode($recordArray).',"'.baseURI.'");
						</script>';		
				} else {
				//UPDATE ONLY - update record
					echo '<script src="http://code.jquery.com/jquery-1.11.0.min.js"></script>';
					echo '<script src="'.baseURI.'api/api.js"></script>';
					echo '<script type="text/javascript">
						MappingManager.putSubmit("'.$_REQUEST['pid'].'","'.$_REQUEST['sid'].'","'.$_REQUEST['rid'].'",'. json_encode($recordArray).',"'.baseURI.'");
						</script>';		
				}
			}
		} 
		else {
			print '<div class="error">'.gettext('**ERROR: $_REQUEST[xml] is not set.').'</div>';
		}
	}
	
	function ValidateXML($xmlstr) {
		//TODO: VALIDATE XML STRINGS
		return true;
	}

?>