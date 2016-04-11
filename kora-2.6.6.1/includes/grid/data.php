<?php          
require_once('phpGrid.php');
if(!session_id()){ session_start();}  

$gridName = isset($_GET['gn']) ? $_GET['gn'] : die('phpGrid fatal error: ULR parameter "gn" is not defined.');
//$dg =  unserialize($_SESSION[GRID_SESSION_KEY.'_'.$gridName]);

$grid_sql	= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_sql'];
$sql_key	= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_sql_key'];
$sql_fkey	= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_sql_fkey'];
$sql_table	= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_sql_table'];  
$sql_filter	= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_sql_filter'];       
$db_connection = unserialize($_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_db_connection']);  

$dg = new C_DataGrid($grid_sql, $sql_key, $sql_table, $db_connection);

//establish db connection
$cn = $dg->get_db_connection();
if(empty($cn)){
    $db = new C_DataBase(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_TYPE, DB_CHARSET);
}
else {       
    $db = new C_DataBase($cn["hostname"],$cn["username"],$cn["password"],$cn["dbname"],$cn["dbtype"],$cn["dbcharset"]);        
}
$dg->db = $db;

//print_r($dg->col_hiddens);

$page = $_GET['page']; 
$limit = $_GET['rows'];  // get how many rows we want to have into the grid - rowNum parameter in the grid 
$sidx = $_GET['sidx'];  // get index row - i.e. user click to sort. At first time sortname parameter - // after that the index from colModel 
$sord = $_GET['sord'];  // sorting order - at first time sortorder 
   
$rs     = $db->select_limit($dg->get_sql(), 1, 1);            

// prepare sql where statement. 
$sqlWhere = "";
$searchOn = ($_REQUEST['_search']=='true')?true:false;
if($searchOn) {
    $col_dbnames = array();
    $col_dbnames = $db->get_col_dbnames($rs);        
    
	//print_r($col_dbnames);
	// check if the key is actual a database field. If true, add it to SQL Where (sqlWhere) statement
    foreach($_REQUEST as $key=>$value) {
		//echo 'key:'. $key ."\n";
        if(in_array($key, $col_dbnames)){
            $fm_type = $db->field_metatype($rs, $db->field_index($rs, $key));
            switch ($fm_type) {
                case 'I':
                case 'N':
                case 'R': case 'SERIAL':
                case 'L':
                    $sqlWhere .= " AND ".$key." = ".$value;
                    break;
                default:
                    $sqlWhere .= " AND ".$key." LIKE '".$value."%'";
                    break;
            }			
        }
        
    }

    //integrated toolbar and advanced search    
    if(isset($_REQUEST['filters']) && $_REQUEST['filters'] !=''){
        $op = array("eq"=>" ='%s' ","ne"=>" !='%s' ","lt"=>" < %s ",
            "le"=>" <= %s ","gt"=>" > %s ","ge"=>" >= %s ",
            "bw"=>" like '%s%%' ","bn"=>" not like '%s%%' " ,
            "in"=> " in (%s) ","ni"=> " not in (%s) ",
            "ew"=> " like '%%%s' ","en"=> " not like '%%%s' ",
            "cn"=> " like '%%%s%%' ","nc"=> " not like '%%%s%%' ");
            
        $filters = json_decode(stripcslashes($_REQUEST['filters']));
        $groupOp = $filters->groupOp;	// AND/OR
        $rules = $filters->rules;
		        
        for($i=0;$i<count($rules);$i++){                   
            $sqlWhere .=  $groupOp . " ". $rules[$i]->field .
                sprintf($op[$rules[$i]->op],$rules[$i]->data);              
        }
    }
}

// remove leading sql AND/OR
$pos = strpos($sqlWhere,'AND ');
if ($pos !== false) {
	$sqlWhere = substr_replace($sqlWhere,'',$pos,strlen('AND '));
}
$pos = strpos($sqlWhere,'OR ');
if ($pos !== false) {
	$sqlWhere = substr_replace($sqlWhere,'',$pos,strlen('OR '));
}
//$sqlWhere = preg_replace('/AND\s/', '', $sqlWhere, 1);	// remove leading sql AND
//$sqlWhere = preg_replace('/OR\s/', '', $sqlWhere, 1);	// remove leading sql OR

// set ORDER BY. Don't use if user hasn't select a sort
$sqlOrderBy = (!$sidx) ? "" : " ORDER BY $sidx $sord";



// ********* prepare the final query ***********************
if($sql_filter != '' && $searchOn){
	$SQL = $dg->get_sql() .' WHERE '. $sql_filter .' AND '. $sqlWhere . $sqlOrderBy;
}elseif($sql_filter != '' && !$searchOn){
	$SQL = $dg->get_sql() .' WHERE '. $sql_filter . $sqlOrderBy;
}elseif($sql_filter == '' && $searchOn){
	$SQL = $dg->get_sql() .' WHERE '. $sqlWhere . $sqlOrderBy;
}else{ // if($sql_filter == '' && !$searchOn){
	$SQL = $dg->get_sql() . $sqlOrderBy;
}

// ******************* execute query finally *****************
// calculate the starting position of the rows 
$start = $limit*$page - $limit;
// if for some reasons start position is negative set it to 0. typical case is that the user type 0 for the requested page 
if($start <0) $start = 0; 
$db->db->SetFetchMode(ADODB_FETCH_BOTH);
$result = $db->select_limit($SQL, $limit, $start);

//echo 'sql_filter: '. $sql_filter;
//echo 'searchOn: '. $searchOn;
//echo $SQL . '; LIMIT: '. $limit .'; START: '. $start;
//echo '<pre>';
//print_r($result);
//echo '<pre>';

// ************************ pagination ************************
$count = $db->num_rows($db->db_query($SQL)); // total record count used for pagination (unfortunate performance penality).                 
// calculate the total pages for the query 
if( $count > 0 && $limit > 0) { 
	$total_pages = ceil($count/$limit); 
}else{ 
	$total_pages = 0; 
} 
// if for some reasons the requested page is greater than the total set the requested page to total page 
if ($page > $total_pages) $page=$total_pages;



// *************** return results in XML or JSON ************
$data_type = $dg->get_jq_datatype();
switch($data_type)
{
    // render xml. Must set appropriate header information. 
    case "xml":
        $data = "<?xml version='1.0' encoding='utf-8'?>";
        $data .=  "<rows>";
        $data .= "<page>".$page."</page>";
        $data .= "<total>".$total_pages."</total>";
        $data .= "<records>".$count."</records>"; 
        while($row = $db->fetch_array_assoc($result)) {
            $data .= "<row id='". $row[$dg->get_sql_key()] ."'>";            
            for($i = 0; $i < $db->num_fields($result); $i++) {
                $col_name = $db->field_name($result, $i);             
                    $data .= "<cell>". $row[$col_name] ."</cell>";    
            }  
            $data .= "</row>";       
        }
        $data .= "</rows>";    

        header("Content-type: text/xml;charset=utf-8");
        echo $data;   
        break;
                 
    case "json":
        $response = new stdClass();   // define anonymous objects
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $count;
        $i=0;
        $data = array();              
        while($row = $db->fetch_array_assoc($result)) {
            unset($data);
            $response->rows[$i]['id']=$row[$dg->get_sql_key()];
            for($j = 0; $j < $db->num_fields($result); $j++) {
                $col_name = $db->field_name($result, $j);                             
                    $data[] = $row[$col_name];    
            }            
            $response->rows[$i]['cell'] = $data;
//            $response->rows[$i]['cell']=array($row[id],$row[invdate],$row[name],$row[amount],$row[tax],$row[total],$row[note]);
            $i++;
        }        
        echo json_encode($response);  
//      echo C_Utility::indent_json(json_encode($response));  
        break;  
} 
          
// free resource       
$dg = null;
$db = null;
//$_SESSION[GRID_SESSION_KEY] = null;


?>