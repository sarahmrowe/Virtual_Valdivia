<?php
require_once("phpGrid.php");
if(!session_id()){ session_start();}

if (!isset($HTTP_POST_VARS) && isset($_POST)){ $HTTP_POST_VARS = $_POST;}  // backward compability when register_long_arrays = off in config 
$col_fileupload  = isset($_GET['col']) ? $_GET['col'] : die('phpGrid fatal error: URL parameter "col" for file upload is not defined');
$upload_folder	 = isset($_GET['folder']) ? urldecode($_GET['folder']) : '';

$msg = "";
$error = "";

$gridName   = isset($_GET['gn']) ? $_GET['gn'] : die('phpGrid fatal error: URL parameter "gn" is not defined');
$grid_sql	= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_sql'];
$sql_key	= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_sql_key'];
$sql_fkey	= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_sql_fkey'];
$sql_table	= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_sql_table'];  
$sql_filter	= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_sql_filter'];       
$db_connection = unserialize($_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_db_connection']);  
$is_debug		= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_is_debug'];       

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

$rs			= $db->select_limit($dg->get_sql(), 1, 1);  
$pk			= $dg->get_sql_key();      // primary key
$pk_val		= $_POST[JQGRID_ROWID_KEY];    
$fm_type	= $db->field_metatype($rs, $db->field_index($rs, $pk));         
if($fm_type != 'I' && $fm_type != 'N' && $fm_type != 'R')     
	$pk_val = "'" . $pk_val ."'"; 

$select_query = "SELECT ". $col_fileupload ." FROM ". $sql_table ." WHERE ". $pk ."=". $pk_val;   
$result = $db->query_then_fetch_array_first($select_query);
$file_name = (!empty($result)) ? $result[$col_fileupload] : null;

// ----------- delete file from file system -----------
$is_deleted = @unlink($upload_folder . $file_name);
if($is_debug)
	$msg .= 'SQL SELECT: '. $select_query;
if(!$is_deleted)
	$error .= 'File remove has failed.';

// ----------- update file name to empty string -----------
$update_query = "UPDATE ". $sql_table ." SET ". $col_fileupload ."=''  WHERE ". $pk ."=". $pk_val;   
$db->db_query($update_query);
if($is_debug)
	$msg .= ' | SQL UPDATE: '. $update_query;
else
	$msg .= ' OK. ';


// ----------- json return -----------
echo '{"error": "' . $error . '", 
	   "msg": "'   . $msg . '"}';

?>