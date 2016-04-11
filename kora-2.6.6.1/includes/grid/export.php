<?php       
require_once('phpGrid.php');
if(!session_id()){ session_start();}  

$gridName = isset($_GET['gn']) ? $_GET['gn'] : die('phpGrid fatal error: URL parameter "gn" is not defined');
// $dg =  unserialize($_SESSION[GRID_SESSION_KEY.'_'.$gridName]);

$grid_sql	= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_sql'];
$sql_key	= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_sql_key'];
$sql_fkey	= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_sql_fkey'];
$sql_table	= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_sql_table'];  
$sql_filter	= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_sql_filter'];       
$db_connection = unserialize($_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_db_connection']);  
$export_type= $_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_export_type'];       
$col_titles	= unserialize($_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_col_titles']);       
$col_hiddens= array_keys(unserialize($_SESSION[GRID_SESSION_KEY.'_'.$gridName.'_col_hiddens']));	// extract the keys only from multiple dimension       

$dg = new C_DataGrid($grid_sql, $sql_key, $sql_table, $db_connection);

// establish db connection
$cn = $dg->get_db_connection();
if(empty($cn)){
    $db = new C_DataBase(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_TYPE, DB_CHARSET);
}
else {       
    $db = new C_DataBase($cn["hostname"],$cn["username"],$cn["password"],$cn["dbname"],$cn["dbtype"],$cn["dbcharset"]);        
}
$dg->db = $db;

// $export_type = $dg->export_type;

// die if export wasn't enabled
if($export_type==null){
    die('Cannot export the grid. Please use enable_export() method to enable this feature.');
}

$sord = (isset($_GET['sord']))?$_GET['sord']:'asc'; 
$sidx = (isset($_GET['sidx']))?$_GET['sidx']:1; 
if(!$sidx) $sidx =1; 

$rs     = $db->select_limit($dg->get_sql(), 1, 1);            

// prepare sql where statement. must check if the key is actual a database field
$sqlWhere = "";
$searchOn = ($_REQUEST['_search']=='true')?true:false;
if($searchOn) {
    $col_dbnames = array();
    $col_dbnames = $db->get_col_dbnames($rs);
    foreach($_REQUEST as $key=>$value) {
        if(in_array($key, $col_dbnames)){
            $fm_type = $db->field_metatype($rs, $db->field_index($rs, $key));
            switch ($fm_type) {
                case 'I':
                case 'N':
                case 'R':
                case 'L':
                    $sqlWhere .= " AND ".$key." = ".$value;
                    break;
                default:
                    $sqlWhere .= " AND ".$key." LIKE '".$value."%'";
                    break;
            }    
        }
        
    }
	//advanced search    
	if(isset($_REQUEST['filters']) && $_REQUEST['filters'] !=''){
		$op = array("eq"=>" ='%s' ","ne"=>" !='%s' ","lt"=>" < %s ",
			"le"=>" <= %s ","gt"=>" > %s ","ge"=>" >= %s ",
			"bw"=>" like '%s%%' ","bn"=>" not like '%s%%' " ,
			"in"=> " in (%s) ","ni"=> " not in (%s) ",
			"ew"=> " like '%%%s' ","en"=> " not like '%%%s' ",
			"cn"=> " like '%%%s%%' ","nc"=> " not like '%%%s%%' ");
		
		$filters = json_decode(stripcslashes($_REQUEST['filters']));
		$groupOp = $filters->groupOp;
		$rules = $filters->rules;
		
		for($i=0;$i<count($rules);$i++){                   
			$sqlWhere .=  $groupOp . " ". $rules[$i]->field .
				sprintf($op[$rules[$i]->op],$rules[$i]->data);              
		}
	}
}

// remove leading sql AND
$pos = strpos($sqlWhere,'AND ');
if ($pos !== false) {
	$sqlWhere = substr_replace($sqlWhere,'',$pos,strlen('AND '));
}

// set ORDER BY. Don't use if user hasn't select a sort
$sqlOrderBy = (!$sidx) ? "" : " ORDER BY $sidx $sord";

      
// ********* prepare the final query ***********************
// $SQL = $dg->get_sql(). (($searchOn)?'  '.$sqlWhere:'') ." ORDER BY $sidx $sord"; //It was original 
if($sql_filter != '' && $searchOn){
	$SQL = $dg->get_sql() .' WHERE '. $sql_filter .' AND '. $sqlWhere . $sqlOrderBy;
}elseif($sql_filter != '' && !$searchOn){
	$SQL = $dg->get_sql() .' WHERE '. $sql_filter . $sqlOrderBy;
}elseif($sql_filter == '' && $searchOn){
	$SQL = $dg->get_sql() .' WHERE '. $sqlWhere . $sqlOrderBy;
}else{	// if($sql_filter == '' && !$searchOn){
	$SQL = $dg->get_sql() . $sqlOrderBy;
}
//echo 'sql_filter: '. $sql_filter;
//echo 'searchOn: '. $searchOn;
//echo $SQL; 

$result     = $db->db_query($SQL);
$row_count  = $db->num_rows($result);
$col_count  = $db->num_fields($result);

// $col_titles = array();
// $col_titles = $col_titles();
$j = 0;

switch($export_type){
    case 'HTML': 
        header("Content-type: text/html");
        header("Content-disposition:  attachment; filename=Grid_". $gridName ."_".date("Y-m-d").".htm");
        header ('Expires: 0');
        header ('Cache-Control: cache, must-revalidate');
        header ('Pragma: public');

        echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>'. "\n";
        
        echo '<table border="1" cellspacing="0" cellpadding="2">' ."\n";  
        echo '<thead>' ."\n";      
        while($row = $db->fetch_array_assoc($rs)) {
            echo '<tr style="background-color:black;color:white">';
            for($j = 0; $j < $db->num_fields($rs); $j++) {
                $col_name = $db->field_name($rs, $j);                             
                if(!in_array($col_name, $col_hiddens)){
                    if(isset($col_titles[$col_name])){
                        echo '<th>'. $col_titles[$col_name] .'</th>';                
                    }else{
                        echo '<th>'. $col_name .'</th>';                                
                    }
                    
                }        
            }
            echo '</tr>' ."\n";            
        }
        echo '</thead>' ."\n";  
            
        echo '<tbody>' ."\n";                  
        while($row = $db->fetch_array_assoc($result)) {
            echo '<tr>';
            for($j = 0; $j < $db->num_fields($result); $j++) {
                $col_name = $db->field_name($result, $j);                             
                if(!in_array($col_name, $col_hiddens))
                    echo '<td>'. $row[$col_name] .'&nbsp;</td>';    
            }
            echo '</tr>' ."\n";            
        }
        echo '</tbody>' ."\n";              
        echo '</table>' ."\n";      
        
        echo '</body></html>';      
		
    break;

	case 'CSV':		
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=Grid_". $gridName ."_".date("Y-m-d").".csv");
		header("Pragma: no-cache");
		header("Expires: 0");

		$rows_all = array();
		$row_header = array();
		while($row = $db->fetch_array_assoc($rs)) {
			for($j = 0; $j < $db->num_fields($rs); $j++) {
				$col_name = $db->field_name($rs, $j);                             
				if(!in_array($col_name, $col_hiddens)){
					if(isset($col_titles[$col_name])){
						$row_header[] = $col_titles[$col_name];                
					}else{
						$row_header[] = $col_name;                       
					}
					
				}        
			}
		}
		$rows_all[] = $row_header;

		while($row = $db->fetch_array_assoc($result)) {
			$row_body = array();
			for($j = 0; $j < $db->num_fields($result); $j++) {
				$col_name = $db->field_name($result, $j);                             
				if(!in_array($col_name, $col_hiddens))
					$row_body[] = $row[$col_name];
			}
			$rows_all[] = $row_body;
		}

		outputCSV($rows_all);
	break;

	case 'PDF':
		$htmloutput = '';
		$htmloutput .= '<table border=1>' ;  
		//$htmloutput .= '<thead>' ;      
		while($row = $db->fetch_array_assoc($rs)) {
			$htmloutput .= '<tr bgcolor=#9CC8FF repeat>';	// repeat this row on every page (header)
			for($j = 0; $j < $db->num_fields($rs); $j++) {
				$col_name = $db->field_name($rs, $j);                             
				if(!in_array($col_name, $col_hiddens)){
					if(isset($col_titles[$col_name])){
						$htmloutput .= '<td>'. $col_titles[$col_name] .'</td>';                
					}else{
						$htmloutput .= '<td>'. $col_name .'</td>';                                
					}
					
				}        
			}
			$htmloutput .= '</tr>' ;            
		}
		//$htmloutput .= '</thead>' ;  
		
		//$htmloutput .= '<tbody>' ;                  
		while($row = $db->fetch_array_assoc($result)) {
			$htmloutput .= '<tr>';
			for($j = 0; $j < $db->num_fields($result); $j++) {
				$col_name = $db->field_name($result, $j);                             
				if(!in_array($col_name, $col_hiddens))
					$htmloutput .= '<td>'. $row[$col_name] .'&nbsp;</td>';    
			}
			$htmloutput .= '</tr>' ;            
		}
		//$htmloutput .= '</tbody>' ;              
		$htmloutput .= '</table>' ;      
		
		$pdf = new PDFTable();
		$pdf->AddPage();
		$pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
		$pdf->SetFont('DejaVu','',7);
		$pdf->htmltable($htmloutput);
		$pdf->output("Grid_". $gridName ."_".date("Y-m-d").".pdf", "I");
    break;

    case 'EXCEL':
        header("Content-type: text/xml");
        header("Content-disposition:  attachment; filename=Grid_". $gridName ."_".date("Y-m-d").".xml");
        header ('Expires: 0');
        header ('Cache-Control: cache, must-revalidate');
        header ('Pragma: public');
        // Excel XML
        // ExpandedColumnCount and ExpandedRowCount must be greater than the actual # of cols and rows.
        echo '<?xml version="1.0"?>
            <?mso-application progid="Excel.Sheet"?>
            <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
             xmlns:o="urn:schemas-microsoft-com:office:office"
             xmlns:x="urn:schemas-microsoft-com:office:excel"
             xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
             xmlns:html="http://www.w3.org/TR/REC-html40">
             <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
              <Author>phpGrid.com</Author>
              <Created></Created>
              <LastSaved></LastSaved>
              <Version></Version>
             </DocumentProperties>
             <ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel">
                <WindowHeight>768</WindowHeight>
                <WindowWidth>1024</WindowWidth>
                <WindowTopX>0</WindowTopX>
                <WindowTopY>0</WindowTopY>
                <ProtectStructure>False</ProtectStructure>
                <ProtectWindows>False</ProtectWindows>
            </ExcelWorkbook>
            <Styles>
                <Style ss:ID="Default" ss:Name="Normal">
                    <Alignment ss:Vertical="Bottom" />
                    <Borders/>
                    <Font ss:FontName="Arial" ss:Size="8" />
                    <Interior/>
                    <NumberFormat />
                    <Protection />
                </Style>
                <Style ss:ID="sHyperlink" ss:Name="Hyperlink">
                    <Font ss:Color="#0000FF" ss:Underline="Single" />
                </Style>
                <Style ss:ID="sDate">
                    <NumberFormat ss:Format="Short Date"/>
                </Style>
                <Style ss:ID="sNumber">
                    <NumberFormat/>
                </Style>                
                <Style ss:ID="sHeader">
                    <Font ss:Family="Arial" ss:Bold="1" />
                </Style>
                <Style ss:ID="sDecimal">
                    <NumberFormat ss:Format="Fixed"/>
                </Style>
            </Styles>';
        echo '<Worksheet ss:Name="Sheet1">
            <Table ss:ExpandedColumnCount="'. $col_count .'" 
              ss:ExpandedRowCount="'. ($row_count+1) .'" x:FullColumns="1"
              x:FullRows="1">';

        // grid header
        while($row = $db->fetch_array_assoc($rs)) {
            echo '<Row>';
            for($j = 0; $j < $col_count; $j++) {
                $col_name = $db->field_name($rs, $j);                          
                if(!in_array($col_name, $col_hiddens)){
                    if(isset($col_titles[$col_name])){
                        echo '<Cell ss:StyleID="sHeader"><Data ss:Type="String">'. str_replace('>', '&gt;', str_replace('<', '&lt;', $col_titles[$col_name])) .'</Data></Cell>';                
                    }else{
                        echo '<Cell ss:StyleID="sHeader"><Data ss:Type="String">'. str_replace('>', '&gt;', str_replace('<', '&lt;', $col_name)) .'</Data></Cell>';                                
                    }
                    
                }        
            }
            echo '</Row>' ."\n";            
        }

        // grid body
        $fm_type = 'C'; // field meta type   
        while($row = $db->fetch_array_assoc($result)) {
            echo '<Row>';
            for($j = 0; $j < $col_count; $j++) {
                $col_name = $db->field_name($result, $j);                             
                if(!in_array($col_name, $col_hiddens)){
                    $fm_type   = $db->field_metatype($result, $db->field_index($result, $col_name));   
                    switch($fm_type){
                        case 'D':
                            echo '<Cell ss:StyleID="sDate"><Data ss:Type="String">'. str_replace('>', '&gt;', str_replace('<', '&lt;', $row[$col_name])) .'</Data></Cell>';   
                            break;
                        case 'I':
                        case 'R':
                            echo '<Cell ss:StyleID="sNumber"><Data ss:Type="Number">'. $row[$col_name] .'</Data></Cell>';   
                            break;
                        case 'N':
                            echo '<Cell ss:StyleID="sDecimal"><Data ss:Type="Number">'. $row[$col_name] .'</Data></Cell>';   
                            break;
                        default:
                            echo '<Cell><Data ss:Type="String">'. str_replace('>', '&gt;', str_replace('<', '&lt;', $row[$col_name])) .'</Data></Cell>';    
                    }
                }
            }
            echo '</Row>' ."\n";            
        }

        echo '</Table>';
        echo '<WorksheetOptions 
              xmlns="urn:schemas-microsoft-com:office:excel">
                <Print>
                    <ValidPrinterInfo />
                    <HorizontalResolution>800</HorizontalResolution>
                    <VerticalResolution>0</VerticalResolution>
                </Print>
                <Selected />
                <Panes>
                    <Pane>
                        <Number>3</Number>
                        <ActiveRow>1</ActiveRow>
                    </Pane>
                </Panes>
                <ProtectObjects>False</ProtectObjects>
                <ProtectScenarios>False</ProtectScenarios>
            </WorksheetOptions>
        </Worksheet>
        </Workbook>';    
    break;
}

function outputCSV($data) {
	$outstream = fopen("php://output", "w");
	function __outputCSV(&$vals, $key, $filehandler) {
		fputcsv($filehandler, $vals); // add parameters if you want
	}
	array_walk($data, "__outputCSV", $outstream);
	fclose($outstream);
}

          
// free resource       
$dg = null;
$db = null;
?>