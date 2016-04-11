<?php                             
// the request url should looks sth like this: 
// subgrid.php?id=2&_search=false&nd=1277597709752&rows=20&page=1&sidx=lineid&sord=asc
require_once('phpGrid.php');
if(!session_id()){ session_start();}  

// s_* indicates subgrid variables
$gridName	= isset($_GET['gn'])  ? $_GET['gn'] :  die('phpGrid fatal error: URL parameter "gn" is not defined');
$s_gridName = isset($_GET['sgn']) ? $_GET['sgn'] : die('phpGrid fatal error: URL parameter "sgn" is not defined');
//$dg =  unserialize($_SESSION[GRID_SESSION_KEY.'_'.$gridName]);
//$sdg= $dg->obj_subgrid;

$grid_sql	= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_sql'];
$sql_key	= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_sql_key'];
$sql_fkey	= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_sql_fkey'];
$sql_table	= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_sql_table'];  
$sql_filter	= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_sql_filter'];       
$db_connection = unserialize($_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_db_connection']);  

$dg = new C_DataGrid($grid_sql, $sql_key, $sql_table, $db_connection);

$s_grid_sql		= $_SESSION[GRID_SESSION_KEY.'_'.$s_gridName.'_sql'];
$s_sql_key		= $_SESSION[GRID_SESSION_KEY.'_'.$s_gridName.'_sql_key'];
$s_sql_fkey		= $_SESSION[GRID_SESSION_KEY.'_'.$s_gridName.'_sql_fkey'];
$s_sql_table	= $_SESSION[GRID_SESSION_KEY.'_'.$s_gridName.'_sql_table'];  
$s_sql_filter	= $_SESSION[GRID_SESSION_KEY.'_'.$s_gridName.'_sql_filter'];       
$s_db_connection= unserialize($_SESSION[GRID_SESSION_KEY.'_'.$s_gridName.'_db_connection']);  

$sdg = new C_DataGrid($s_grid_sql, $s_sql_key, $s_sql_table, $s_db_connection);

//establish db connection
$cn = $dg->get_db_connection();
if(empty($cn)){
	$db = new C_DataBase(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_TYPE, DB_CHARSET);
}
else {       
	$db = new C_DataBase($cn["hostname"],$cn["username"],$cn["password"],$cn["dbname"],$cn["dbtype"],$cn["dbcharset"]);        
}
$dg->db = $db;


$fk     = $s_sql_fkey;
$pk		= $dg->get_sql_key();
$pk_val = (isset($_GET['id']))?urldecode($_GET['id']):null;
$m_fkey = (isset($_GET['m_fkey']))?urldecode($_GET['m_fkey']):-1;

// ***************** database query to obtain the foreign key value from master grid *****************. 
// Doesn't work for composite key yet
$sqlWhere = ' WHERE '. $db->quote_field($dg->get_sql(), $pk, $pk_val);
$sqlFkey = 'SELECT '. $m_fkey .' FROM '. $dg->get_sql_table() .$sqlWhere;  
$result = $db->query_then_fetch_array_first($sqlFkey);
$fk_val = (!empty($result)) ? $result[$m_fkey] : null;


// ***************** database query to obtain the detail grid data using foreign key value obtained previously *****************. 
$page   = (isset($_GET['page']))?$_GET['page']:1; 
$limit  = (isset($_GET['rows']))?$_GET['rows']:20;
$sord   = (isset($_GET['sord']))?$_GET['sord']:'asc'; 
$sidx   = (isset($_GET['sidx']))?$_GET['sidx']:""; 

$sqlWhere   = ' WHERE '. $db->quote_field($sdg->get_sql(), $fk, $fk_val);
// set ORDER BY. Don't use if user hasn't select a sort
$sqlOrderBy = (!$sidx) ? "" : " ORDER BY $sidx $sord";			
// the actual query for the grid data   
if($s_sql_filter != ''){
    $SQL = $sdg->get_sql(). $sqlWhere .' AND '. $s_sql_filter . $sqlOrderBy;
}else{
    $SQL = $sdg->get_sql(). $sqlWhere . $sqlOrderBy;
}

// ****** DEBUG ONLY *****  
/*
echo $sqlFkey ."\n";           
echo $fk_val."\n";
echo $fk."\n";	
echo $pk_val."\n"; 
echo $SQL."\n";  
echo $m_fkey."\n";
*/


// ************************ pagination ************************
$rs    = $db->db_query($SQL);            
$count = $db->num_rows($rs);

// calculate the total pages for the query 
if( $count > 0 && $limit > 0) { 
	$total_pages = ceil($count/$limit); 
}else{ 
	$total_pages = 0; 
} 
 
// if for some reasons the requested page is greater than the total. set the requested page to total page 
if ($page > $total_pages) $page=$total_pages;
 
// calculate the starting position of the rows 
$start = $limit*$page - $limit;
 
// if for some reasons start position is negative set it to 0. typical case is that the user type 0 for the requested page 
if($start <0) $start = 0; 


// ******************* execute query finally *****************
$db->db->SetFetchMode(ADODB_FETCH_BOTH);
$result = $db->select_limit($SQL, $limit, $start);

	  
// *************** return results in XML or JSON ************
$data_type = $sdg->get_jq_datatype();
switch($data_type)
{
	// render xml. Must set appropriate header information. 
	case "xml":
		$data = "<?xml version='1.0' encoding='utf-8'?>";
		$data .=  "<rows>";
		$data .= "<page>".$page."</page>";
		$data .= "<total>".$total_pages."</total>";
		$data .= "<records>".$count."</records>"; 
		$i = 0;
		while($row = $db->fetch_array_assoc($result)) {
			$data .= "<row id='". $row[$sdg->get_sql_key()] ."'>";            
			for($i = 0; $i < $db->num_fields($rs); $i++) {
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
		$response = new stdClass();   // define anonymous object
		$response->page = $page;
		$response->total = $total_pages;
		$response->records = $count;
		$i=0;
		$data = array();              
		while($row = $db->fetch_array_assoc($result)) {
			unset($data);
			$response->rows[$i]['id']=$row[$sdg->get_sql_key()];
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
$sdg= null;
$db = null;
?>