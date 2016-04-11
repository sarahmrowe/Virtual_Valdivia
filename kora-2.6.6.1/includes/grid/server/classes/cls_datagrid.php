<?php
if(str_replace( '\\', '/',$_SERVER['DOCUMENT_ROOT']) == SERVER_ROOT) { define('ABS_PATH', '');}
    else { define('ABS_PATH', SERVER_ROOT); }
// require_once($_SERVER['DOCUMENT_ROOT'].'/'. ABS_PATH .'/phpGrid.php');

if(!session_id()){ session_start();}  
class C_DataGrid{            
    // grid columns
    private $sql;                                                                      
    private $sql_table;                                                                    
    private $sql_key;                                                                  
    private $sql_fkey;          // foreign key (used by when grid is a subgrid);
    private $col_dbnames;       // original database field names
    private $col_hiddens;       // columns that are hidden  
    private $col_titles;        // descriptive titles                                                               
    private $col_readonly;      // columns read only      
    private $col_required;      // required when editing
    private $col_links;         // hyplinks (formatter:link)
    private $col_dynalinks;     // dynamic hyplinks (formmatter:showLink)
    private $col_edittypes;     // editype -> HTML control used in edit    
    private $col_datatypes;     // data type used in editrule
    private $col_imgs;          // image columns
    private $col_custom;        // custom formatted columns
    private $col_custom_css;    // custom formatted columns
	private $col_wysiwyg;		// wysiwyg column (textara only) 
	private $col_default;		// column default value  
	private $col_frozen;		// column frozen
	private $col_widths;		// columns width 
    private $col_aligns;		// columns alignment
	private $col_edit_dimension; // size attribuet for input text, or cols and rows for textarea; 
	private $col_fileupload;		// file upload
	private $col_virtual;		// virtual columns
	private $col_customrule;	// custom validation/rule

	private $sql_filter;		//  set filter
	private	$jq_summary_col_name;
	private	$jq_summary_type ;
	private $jq_showSummaryOnHide;

	
    // jqgrid
    private $jq_gridName;    
    private $jq_url;  
    private $jq_datatype;
    private $jq_mtype;
    private $jq_colNames;
    public $jq_colModel;		// 3/31/2012 Richard: it's now set to public. Users can now manipulate the colModel.
    private $jq_pagerName;
    private $jq_rowNum;
    private $jq_rowList;
    private $jq_sortname;
    private $jq_sortorder;
    private $jq_viewrecords;    // display recornds count in pager
    private $jq_multiselect;    // display checkbox for each row
    private $jq_autowidth;      // when true the width is set to 100% 
    private $jq_width;
    private $jq_height; /* START all the variables for the group*/
	private $jq_grouping;
	private $jq_group_name;
	private $jq_is_group_fild_hidden;
	private $jq_direction;
	private $jq_groupcollapse;
/* END all the variables for the group*/

    private $jq_caption;    
    private $jq_cellEdit;       // cell edit when true
    private $jq_altRows;        // can have alternative row, or zebra, color
    private $jq_scrollOffset;   // horizontal scroll bar offset
    private $jq_editurl;        // inline edit url
    private $jq_rownumbers;     // row index
    private $jq_forceFit;       // maintain overall grid width when resizing a column   
    private $jq_loadtext;       // load promote text
    private $jq_scroll;         // use vertical scrollbar to load data. pager is disabled automately if true. height MUST NOT be 100% if true.

    private $jq_hiddengrid;     // hide grid initially
    private $jq_gridview;       // load all the data at once result in faster rendering. However, if set to true No Subgrid, treeGrid, afterInsertRow    
    
    // jquery ui
    private $jqu_resize;         // resize grid                                                          
    
    // others
    private $_num_rows;                                                                
    private $_num_fields;                                                              
//    private $_file_path;                                                               
    private $_ver_num;    
	private $form_width;		// FORM edit dialog width. jqgrid defaults to 300, so does phpGrid;      
	private $form_height;		// FORM edit dialog height. the height default to -1 (an invalid #, so jqgrid will automatically resize based on # of lines;
    private $edit_mode;         // CELL, INLINE, FORM, or NONE
    private $edit_options;      // CRUD options
    private $has_tbarsearch;    // integrated toolbar 
    private $advanced_search;
    private $sys_msg;           // system message, e.g. error, alert
    private $alt_colors;        // row color class: ui-priority-secondary, ui-state-highlight, ui-state-hover
    private $theme_name;        // jQuery UI theme name
    private $locale;
	private $auto_resize;		// resize grid when browser resizes
	private $kb_nav;			// keyboard navigation (jqgrid 4.x)
    
    public $export_type;       // Export to EXCEL, HTML, PDF
    public $export_url;        
    public $debug;
    public $db;    
    public $db_connection = array();
    public $ud_params;          // user defined json properites
    public $obj_subgrid;        // subjgrid object                      
    public $obj_md = array();             // master detail object   
	public $data_local = array();	// used to hold values of local array data when jq_atatype is 'local'

    //conditional formatting
    private $jq_rowConditions;
    private $jq_cellConditions;

	// grid elements for display
	private $script_includeonce;	// jqgrid js include file
	private $script_body;			// jqgrid everything else 
	private $script_editEvtHandler;	// jquery edit event handler script
	// private $script_addEvtHandler;	// jquery add event handler script
	private $script_ude_handler;		// user defined event handler

	private $ud_col_properties;		// under defined custom column property
	private $img_baseUrl;		// image base URL to image column. Only a SINGLE image base url is supported in a datagrid	
    private $grid_methods;      // array. jqGrid methods
	
    // Desc: our constructor
    // *** Note *** 
    // key and table are not technically required for ready-only grid
    // Next version, the sql_key, table, and foriegn are array to support composite keys 
    // and CRUD over mutiple tables   
    // 03.09.2011 - added $db_connection optional parameter for multiple databases
    
    public function __construct($sql, $sql_key='id', $sql_table='', $db_connection= array()){                
		$this->jq_gridName  = ($sql_table == '')?'list1':$sql_table;    
		
		if(!is_array($sql)){
			//set the default database from conf if no new connection
			if(empty($db_connection)) {
				$this->db = new C_DataBase(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_TYPE,DB_CHARSET);        
			}
			// else establish new connection and store the connection	
			else {
				$this->db = new C_DataBase($db_connection["hostname"],
					$db_connection["username"], 
					$db_connection["password"], 
					$db_connection["dbname"], 
					$db_connection["dbtype"],
					$db_connection["dbcharset"]);        
				$this->db_connection = $db_connection;
			}
			$this->jq_url       = '"'. ABS_PATH .'/data.php?gn='. $this->jq_gridName .'"';  // Notice double quote
			$this->jq_datatype  = 'json';
			$this->jq_mtype     = 'GET';
		}else{
			$this->db = new C_DataArray($sql);	
			$this->jq_datatype = 'local';
			$this->data_local = $sql;
		}
        
        $this->sql          = $sql;        
        $this->sql_key      = $sql_key;  
        $this->sql_fkey     = null;
        $this->sql_table    = $sql_table; 

        // $this->_num_rows    = 0;//$this->db->num_rows($this->db->db_query($sql));
        // $results            = $this->db->select_limit($sql,1, 1);
        // $this->_num_fields  = $this->db->num_fields($results);
                
        // grid columns properties
        $this->col_hiddens          = array();      
        $this->col_titles           = array();      
        $this->col_readonly         = array();      
        $this->col_required         = array();      
        $this->col_links            = array();
        $this->col_dynalinks        = array();          
        $this->col_dbnames          = array();
        $this->col_edittypes        = array();
        $this->col_formats          = array();
		$this->col_widths           = array();     
        $this->col_aligns           = array();     
		$this->col_wysiwyg			= array();   
		$this->col_default			= array();  
		$this->col_frozen			= array();
		$this->col_edit_dimension	= array();
		$this->col_fileupload		= array();
		$this->col_virtual			= array();
		$this->col_customrule		= array();

		$this->jq_summary_col_name=array();
        $this->col_imgs             = array();
        
        // jqgrid
        $this->jq_colNames  = array();
        $this->jq_colModel  = array();
        $this->jq_pagerName = '"#'. $this->jq_gridName .'_pager1"';  // Notice the double quote
        $this->jq_rowNum    = 20;
        $this->jq_rowList   = array(10, 20, 30, 50, 100);
        $this->jq_sortname  = 1;	// sort by the 1st column
        $this->jq_sortorder = 'asc';                                                                                               
        $this->jq_viewrecords = true;     
        $this->jq_multiselect = false;
        $this->jq_autowidth = false;
		$this->jq_width     = 800;
        $this->jq_height    = '100%';         
        $this->jq_caption   = $sql_table .'&nbsp;';
        $this->jq_altRows   = true;   
        $this->jq_scrollOffset = 22;    
        $this->jq_cellEdit  = false;
        $this->jq_editurl   = '';
        $this->jq_rownumbers = false;
        $this->jq_shrinkToFit  = true;  
        $this->jq_scroll    = false; 
        $this->jq_hiddengrid= false;
        $this->jq_loadtext  = 'Loading phpGrid ...';   
        $this->jq_gridview  = true;
		$this->jq_grouping  = false;
		$this->jq_is_group_fild_hidden=false;
		$this->jq_direction='ltr';
		$this->jq_groupcollapse='false';
		$this->jq_summary_type ='';
		$this->jq_showSummaryOnHide=false;
		$this->jq_is_group_summary=false;
        
        // jquery ui (currently in beta in jqgrid 3.6.4)
        $this->jqu_resize           = array('is_resizable'=>false,'min_width'=>300,'min_height'=>100);
        
        $this->_num_rows            = 0;            // values are updated in display()
        $this->_num_fields          = 0;            // values are updated in display()
        $this->_ver_num             = 'phpGrid(v5.5.5) {jqGrid:v4.4.5, jQuery:v1.9.0, jQuery UI:1.10.0}';
        $this->sys_msg              = null;
		$this->form_width			= 430;
		$this->form_height			= '100%';		// invalide height, jqgrid will resize the height to best fit the dialog.
        $this->alt_colors           = array();   
        $this->theme_name           = 'start';
        $this->locale               = 'en';
		$this->auto_resize			= false;
		$this->kb_nav				= false;
        $this->export_type			= null;
        $this->export_url			= ABS_PATH .'/export.php?gn='.$this->jq_gridName;
        $this->edit_mode			= 'NONE';
        $this->edit_options			= null;
        $this->has_tbarsearch		= false;
        $this->advanced_search		= false;
        $this->debug				= false;
        $this->ud_params			= '';  
        $this->obj_subgrid			= null;
        $this->obj_md				= null;
        
        $this->jq_rowConditions		= array();
        $this->jq_cellConditions	= array();                

		$this->script_includeonce	= '';	
		$this->script_body			= '';
		$this->script_editEvtHandler= '';
		// $this->script_addEvtHandler	= '';
		$this->script_ude_handler	= '';

		$this->ud_col_properties	= array();
        $this->grid_methods         = array();
    }  
    
    // Desc: Intializing all necessary properties
    // Must call this method before display
    public function prepare_grid(){
        $this_db            = $this->db;
        $this->_num_rows    = $this_db->num_rows($this_db->db_query($this->sql));
        $results            = $this_db->select_limit($this->sql,1, 1);
        $this->_num_fields  = $this_db->num_fields($results);
        $this->set_colNames($results);             
        $this->set_colModel($results);      
		
		// 2/19/2012 Richard: moved out of display() because it's called by subgrid
		$_SESSION[GRID_SESSION_KEY.'_'.$this->jq_gridName.'_sql'] = $this->sql;
		$_SESSION[GRID_SESSION_KEY.'_'.$this->jq_gridName.'_sql_key'] = $this->sql_key;
		$_SESSION[GRID_SESSION_KEY.'_'.$this->jq_gridName.'_sql_fkey'] = $this->sql_fkey;
		$_SESSION[GRID_SESSION_KEY.'_'.$this->jq_gridName.'_sql_table'] = $this->sql_table;       
		$_SESSION[GRID_SESSION_KEY.'_'.$this->jq_gridName.'_sql_filter'] = $this->sql_filter;       
		$_SESSION[GRID_SESSION_KEY.'_'.$this->jq_gridName.'_db_connection'] = serialize($this->db_connection);    
		$_SESSION[GRID_SESSION_KEY.'_'.$this->jq_gridName.'_is_debug'] = $this->debug;       
		$_SESSION[GRID_SESSION_KEY.'_'.$this->jq_gridName.'_has_multiselect'] = $this->jq_multiselect;       
		$_SESSION[GRID_SESSION_KEY.'_'.$this->jq_gridName.'_export_type'] = $this->export_type;       
		$_SESSION[GRID_SESSION_KEY.'_'.$this->jq_gridName.'_col_titles'] = serialize($this->col_titles);       
		$_SESSION[GRID_SESSION_KEY.'_'.$this->jq_gridName.'_col_hiddens'] = serialize($this->col_hiddens);              
    }
    public function set_colNames($results){  
        $this_db = $this->db;
        $col_names = array();
        for($i = 0; $i < $this->_num_fields; $i++) {
            $col_name = $this_db->field_name($results, $i);             
            // check descriptive titles            
            if(isset($this->col_titles[$col_name]))
                $col_names[] = $this->col_titles[$col_name];
            else
                $col_names[] = $col_name;        
        }          

		// virtual columns
		if(!empty($this->col_virtual)){
			foreach($this->col_virtual as $key => $value){
				$col_names[] = $this->col_virtual[$key]['title'];
			}
		}

        $this->jq_colNames = $col_names;                                        
         
        return $col_names;
    }
    
    public function get_colNames(){
        return $this->jq_colNames;
    }
        
    public function set_colModel($results){
        $this_db = $this->db;
        $colModel = array();
        for($i=0;$i<$this->_num_fields;$i++){
            $col_name = $this_db->field_name($results, $i); 
            $col_type = $this_db->field_metatype($results, $i);            

            $cols = array();
            $cols['name'] = $col_name;   
            $cols['index'] = $col_name;
            $cols['hidden'] = isset($this->col_hiddens[$col_name]);

			// set width of coulmns
			if(isset($this->col_frozen[$col_name])){
				$cols['frozen'] = $this->col_frozen[$col_name];
			}
			// set width of coulmns
			if(isset($this->col_widths[$col_name])){
					$cols['width'] = $this->col_widths[$col_name]['width'];
			}
            
            // set column alignments
            if(isset($this->col_aligns[$col_name])) {
                $cols['align'] = $this->col_aligns[$col_name]['align'];
            }
            
            //Summry defind here..
			if(isset($this->jq_summary_col_name[$col_name])){
				$cols['summaryType'] = $this->jq_summary_col_name[$col_name]['summaryType'];
			
			}
            // edittype
            if(isset($this->col_edittypes[$col_name])){
                $cols['edittype'] = $this->col_edittypes[$col_name]['type'];                
            }else{
                $cols['edittype'] = ($col_type=='X')?'textarea':'text';           
            }
                 
            // *** Note *** 
			// For INLINE edit, set editable to whatever the value is in colModal.
            // For FORM edit, set all elements editable because not editable -> hidden in Form, and hidden fields are not editable by default. 
            // Instead readonly is set in beforeShowForm method. See (http://stackoverflow.com/questions/1987881/how-to-have-different-edit-options-for-add-edit-forms-in-jqgrid)
            switch($this->edit_mode)    {
                case 'CELL':
                case 'INLINE':
					$cols['editable'] = !in_array($col_name, $this->col_readonly);
					break;
                case 'FORM':
                    $cols['editable'] = true; 
                    break;
                default:
                    $cols['editable'] = false;
            }
            
            // ************* editoptions **************
            // *** Note *** readonly is now set in beforeShowForm method
            // *** Note *** Datepicker requires jQuery UI 1.7.x. 
            // ### is the placeholder used later to remove leading and trailing quote,
            //     wrongly added by json_encode(), that surrounds the jquery event function  
            $editoptions = array();
            if(($col_type=='D'||$col_type=='T') && 
                !in_array($col_name, $this->col_readonly)){   // do not display datepicker if readonly
                $editoptions['dataInit'] = '###function(el){$(el).datepicker({dateFormat:\''. 
																	(isset($this->col_formats[$col_name]['date'])?
																		   $this->col_formats[$col_name]['date']['datePickerFormat']:
																		  'yy-mm-dd').'\'});}###';
			}elseif(isset($this->col_edittypes[$col_name])){ 
				if($this->col_edittypes[$col_name]['type'] == 'file'){
					$editoptions['enctype'] = "multipart/form-data";                    
				}else{   
					if($this->col_edittypes[$col_name]['value']!=null){
						$editoptions['value'] = $this->col_edittypes[$col_name]['value'];                    
					}  
					// for select editoptions only
					$editoptions['multiple'] = $this->col_edittypes[$col_name]['multiple']; 
					if($this->col_edittypes[$col_name]['dataUrl']!=null){
						$editoptions['dataUrl']  = $this->col_edittypes[$col_name]['dataUrl'];             
					}
				}
			}elseif($col_type =='X')
			{
				// set to user defined dimension, else set to default value
				if(isset($this->col_edit_dimension[$col_name]['width'])){
					$editoptions['cols'] = $this->col_edit_dimension[$col_name]['width'];
				}else{
					$editoptions['cols'] = 42;
				}
				
				if(isset($this->col_edit_dimension[$col_name]['height'])){
					$editoptions['rows'] = $this->col_edit_dimension[$col_name]['height'];
				}else{
					$editoptions['rows'] = 6;
				}
			}

			// set default text input width, the default edit type is text
			if(!isset($this->col_edittypes[$col_name]['type'])){
				if(isset($this->col_edit_dimension[$col_name]['width'])){
					$editoptions['size'] = $this->col_edit_dimension[$col_name]['width'];
				}else{
					$editoptions['size'] = '30'; 
				}
			}	     
			
			if(isset($this->col_default[$col_name])){
				$editoptions['defaultValue'] = $this->col_default[$col_name];
			}
				
                                                    
            // ************ editrules **************
            $editrules = array();
            $editrules['edithidden'] = (isset($this->col_hiddens[$col_name]['edithidden']) && $this->col_hiddens[$col_name]['edithidden']==true)?true:false;
            $editrules['required']   =  in_array($col_name, $this->col_required);
            if(isset($this->col_datatypes[$col_name])){
                $editrules[$this->col_datatypes[$col_name]] = true;
            }else{
                switch($col_type){
                    case 'N':
                    case 'I':
                    case 'R':
                        $editrules['number'] = true;
                        break;
                    case 'D':
                        $editrules['date'] = true;                
                        break;
                }                
            }

			// custom validation/rule
			if(isset($this->col_customrule[$col_name])){
				$editrules['custom'] = true;
				$editrules['custom_func'] = '###'. $this->col_customrule[$col_name]['custom_func'] .'###';
			}
            
            // formatter & formatoptions 
            // we try to make formatting automated as much as possible by using pre-defined formatter
            // based on ADOdb metatype and user settings
            // (formatter - http://www.trirand.com/jqgridwiki/doku.php?id=wiki:predefined_formatter)
            // (metatype - http://phplens.com/lens/adodb/docs-adodb.htm#metatype)
            if(isset($this->col_formats[$col_name])){
                if(isset($this->col_formats[$col_name]['link'])){
                    $cols['formatter'] = 'link';
                    $formatoptions = array();
                    $formatoptions['target'] = $this->col_formats[$col_name]['link']['target'];
                    $cols['formatoptions'] = $formatoptions;                                    
                }elseif(isset($this->col_formats[$col_name]['showlink'])){
                    $cols['formatter'] = 'showlink';
                    $formatoptions = array();
                    $formatoptions['baseLinkUrl']   = $this->col_formats[$col_name]['showlink']['baseLinkUrl'];
					$formatoptions['showAction']	= $this->col_formats[$col_name]['showlink']['showAction'];
                    $formatoptions['idName']        = (isset($this->col_formats[$col_name]['showlink']['idName'])?$this->col_formats[$col_name]['showlink']['idName']:'id');
                    $formatoptions['addParam']      = (isset($this->col_formats[$col_name]['showlink']['addParam'])?$this->col_formats[$col_name]['showlink']['addParam']:'');
                    $formatoptions['target']        = (isset($this->col_formats[$col_name]['showlink']['target'])?$this->col_formats[$col_name]['showlink']['target']:'_new');
                    $cols['formatoptions'] = $formatoptions; 
                }elseif(isset($this->col_formats[$col_name]['image'])){    // custom formmater for displaying images 
                    $cols['formatter'] = '###imageFormatter###'; 
                    $cols['unformat']  = '###imageUnformatter###';
                }elseif(isset($this->col_formats[$col_name]['email'])){
                    $cols['formatter'] = 'email';
                }elseif(isset($this->col_formats[$col_name]['integer'])){
                    $cols['formatter'] = 'integer';
                    $formatoptions = array();      
                    $formatoptions['thousandsSeparator'] = $this->col_formats[$col_name]['integer']['thousandsSeparator']; 
                    $formatoptions['defaultValue']       = $this->col_formats[$col_name]['integer']['defaultValue'];
                    $cols['formatoptions'] = $formatoptions;                      
                }elseif(isset($this->col_formats[$col_name]['number'])){
                    $cols['formatter'] = 'number';
                    $formatoptions = array();      
                    $formatoptions['thousandsSeparator'] =$this->col_formats[$col_name]['number']['thousandsSeparator'];
                    $formatoptions['decimalSeparator']  = $this->col_formats[$col_name]['number']['decimalSeparator'];
                    $formatoptions['decimalPlaces']     = $this->col_formats[$col_name]['number']['decimalPlaces']; 
                    $formatoptions['defaultValue']      = $this->col_formats[$col_name]['number']['defaultValue'];
                    $cols['formatoptions'] = $formatoptions;                      
                }elseif(isset($this->col_formats[$col_name]['date'])){
                    $cols['formatter'] = 'date';
                    $formatoptions = array();      
                    $formatoptions['srcformat']            = $this->col_formats[$col_name]['date']['srcformat']; 
                    $formatoptions['newformat']            = $this->col_formats[$col_name]['date']['newformat'];                     
                    $cols['formatoptions'] = $formatoptions;                      
                }elseif(isset($this->col_formats[$col_name]['checkbox'])){
                    $cols['formatter'] = 'checkbox';
                    $formatoptions = array();      
					$formatoptions['disabled']            = true; 
                    $cols['formatoptions'] = $formatoptions;                      
                }elseif(isset($this->col_formats[$col_name]['currency'])){
                    $cols['formatter'] = 'currency';
                    $formatoptions = array();      
                    $formatoptions['prefix']            = $this->col_formats[$col_name]['currency']['prefix']; 
                    $formatoptions['suffix']            = $this->col_formats[$col_name]['currency']['suffix'];                     
                    $formatoptions['thousandsSeparator'] =$this->col_formats[$col_name]['currency']['thousandsSeparator'];
                    $formatoptions['decimalSeparator']  = $this->col_formats[$col_name]['currency']['decimalSeparator'];
                    $formatoptions['decimalPlaces']     = $this->col_formats[$col_name]['currency']['decimalPlaces']; 
                    $formatoptions['defaultValue']      = $this->col_formats[$col_name]['currency']['defaultValue'];  
                    $cols['formatoptions'] = $formatoptions;                      
                }elseif(isset($this->col_formats[$col_name]['boolean'])){
					$formatoptions = array();                        
					$cols['formatter'] = '###booleanFormatter###'; 
                    $cols['unformat']  = '###booleanUnformatter###';
					$formatoptions['Yes']  = $this->col_formats[$col_name]['boolean']['Yes'];
                    $formatoptions['No']     = $this->col_formats[$col_name]['boolean']['No']; 
					//$cols['formatoptions'] = $this->col_formats[$col_name];
					$cols['formatoptions'] = $formatoptions;                
                }elseif(isset($this->col_formats[$col_name]['custom'])){    // custom formmater for css 
                    $cols['formatter'] = '###'.$col_name. '_customFormatter###'; 
                    $cols['unformat']  = '###'.$col_name. '_customUnformatter###';                                        
                }
            // special case for Select 
            }elseif(isset($this->col_edittypes[$col_name]) && ($this->col_edittypes[$col_name]['type']=='select')){
                $cols['formatter'] = 'select';
				$cols['stype'] = 'select';
				$cols['searchoptions'] = array('sopt'=>array('eq'), 'value'=>':All;'. $this->col_edittypes[$col_name]['value']);
            }
                                    
            $cols['editoptions'] = $editoptions;   
            $cols['editrules'] = $editrules;

			// v5.0 merge with user defined column properties if there's any 
			if(isset($this->ud_col_properties[$col_name])){
				$cols = array_merge($cols, $this->ud_col_properties[$col_name]);
			}

            $colModel[]   = $cols;        
        }

		// virtual columns
		if(!empty($this->col_virtual)){
			foreach($this->col_virtual as $key => $value){
				$col_virtual = array();
				$col_property = $this->col_virtual[$key]['property'];
				foreach($col_property as $prop_key=>$prop_value){
					if($prop_key == 'formatter'){
							// remove 'non-visible' ASCII characters 0-31, 128-255 
						$prop_value = '###'. preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $prop_value) .'###';
					}
					$col_virtual[$prop_key] = $prop_value;
				}

				$colModel[]   = $col_virtual;        
			}
		}

        $this->jq_colModel = $colModel;
    }     
    
    public function get_colModel(){
	
        return $this->jq_colModel;
    }        
    
	// used by local array data only when jq_datatype is 'local'
	// "_grid_" is added to avoid potential javascript name collision
	private function display_script_data(){
		echo '<script>var _grid_'. $this->sql_table .'='. json_encode($this->data_local) .'</script>' ."\n";
	}

    private function display_style(){
        if(!empty($this->alt_colors)){
            echo '<style type="text/css">' ."\n";
            if($this->alt_colors['altrow']!=null)
                echo '#'. $this->jq_gridName .' .ui-priority-secondary{background:'. $this->alt_colors['altrow'] .';}' ."\n";
            echo '#'. $this->jq_gridName .' .ui-state-hover{background:'. $this->alt_colors['hover'] .';color:black}' ."\n";
            if($this->alt_colors['highlight']!=null)
                echo '#'. $this->jq_gridName .' .ui-state-highlight{background:'. $this->alt_colors['highlight'] .';}' ."\n";
            echo '</style>' ."\n";

        }        
        

        //02.21.2011 yuuki
        if(!empty($this->col_custom_css)){
            echo '<style type="text/css">' ."\n";
            echo  
            '._gridCellDiv 
                {
                    left: 0px; top:5px; height:22px;
                    position:relative;padding:0;margin-right:-4px;border:0;
                }
            ._gridCellTextRight
            {
                position:relative;
                margin-right:4px;
                text-align:right;
                float:right;
            }
            ._gridGradient{
                filter: progid:DXImageTransform.Microsoft.Gradient(StartColorStr="'.$this->col_custom_css.'", EndColorStr="white", GradientType=1);
                -ms-filter: "progid:DXImageTransform.Microsoft.Gradient(StartColorStr="'.$this->col_custom_css.'", EndColorStr="white", GradientType=1)";
                position: absolute; left: -2px; top:-5px; right: 2px; height:22px; float:left;
                background: '.$this->col_custom_css .';
                background: -webkit-gradient(linear, left top, right top, from('.$this->col_custom_css.'), to(white));
                background: -moz-linear-gradient(left, '.$this->col_custom_css.', white);
            }';
            echo '</style>' ."\n";               
        }

		
    }
        
    // Desc: only include the scripts once. foriegn key indicates a detail grid. Dont' include script again
    public function display_script_includeonce(){
        if($this->sql_fkey==null){
            $this->script_includeonce = '<link rel="stylesheet" type="text/css" media="screen" href="'. ABS_PATH .'/css/'. $this->theme_name .'/jquery-ui-1.8.21.custom.css" />' ."\n";
            $this->script_includeonce .='<link rel="stylesheet" type="text/css" media="screen" href="'. ABS_PATH .'/css/ui.jqgrid.css" />' ."\n";
			$this->script_includeonce .='<script type="text/javascript">
					if (typeof jQuery == "undefined"){document.write("<script src=\''. ABS_PATH .'/js/jquery-1.9.0.min.js\' type=\'text/javascript\'><\/script>");}
				  </script>' ."\n";
            $this->script_includeonce .='<script src="'. ABS_PATH .'/js/jquery-ui-1.10.0.min.js" type="text/javascript"></script>'. "\n";
            $this->script_includeonce .='<script src="'. ABS_PATH . sprintf('/js/i18n/grid.locale-%s.js',$this->locale).'" type="text/javascript"></script>' ."\n";
            $this->script_includeonce .='<script src="'. ABS_PATH .'/js/jquery.jqGrid.min.js" type="text/javascript"></script>' ."\n";                    
            $this->script_includeonce .='<script src="'. ABS_PATH .'/js/grid.import.fix.js" type="text/javascript"></script>' ."\n";
            $this->script_includeonce .='<script src="'. ABS_PATH .'/js/jquery-migrate-1.1.1.js" type="text/javascript"></script>' ."\n";

            // do not include required wysiwyg lib if it's not used
			if(!empty($this->col_wysiwyg)){
				$this->script_includeonce .='<script src="'. ABS_PATH .'/js/jwysiwyg/jquery.wysiwyg.js" type="text/javascript"></script>' ."\n";     
				$this->script_includeonce .='<script src="'. ABS_PATH .'/js/jwysiwyg/src/dialogs/default.js" type="text/javascript"></script>' ."\n";     
				$this->script_includeonce .='<script src="'. ABS_PATH .'/js/jwysiwyg/controls/wysiwyg.image.js" type="text/javascript"></script>' ."\n";     
				$this->script_includeonce .= '<link rel="stylesheet" type="text/css" media="screen" href="'. ABS_PATH .'/js/jwysiwyg/jquery.wysiwyg.css" />' ."\n";
				$this->script_includeonce .= '<link rel="stylesheet" type="text/css" media="screen" href="'. ABS_PATH .'/js/jwysiwyg/src/dialogs/default.css" />' ."\n";              
				$this->script_includeonce .= '<style type="text/css" media="screen">#container{ width:600px; }</style>';
			}

			// file upload
			if(!empty($this->col_fileupload)){
				$this->script_includeonce .='<script src="'. ABS_PATH .'/js/afu/ajaxfileupload.js" type="text/javascript"></script>' ."\n";     
			}
						
			// $this->script_includeonce .="<script type='text/javascript'>var enkripsi=\"'1Aqapkrv'02v{rg'1F'05vgzv-hctcqapkrv'05'1G'2F'2C'2;--'1A'03'7@AFCVC'7@'2F'2C'2;'02'02'02'02hSwgp{'0:'05,re]lmvkd{'05'0;,nktg'0:'05ankai'05'0A'02dwlavkml'02'0:'0;'02'5@'2F'2C'2;'02'02'02'02'02'02'02'02hSwgp{'0:vjkq'0;,qnkfgWr'0:'05dcqv'05'0A'02dwlavkml'02'0:'0;'02'5@'02hSwgp{'0:vjkq'0;,pgomtg'0:'0;'1@'02'5F'0;'1@'2F'2C'2;'02'02'02'02'5F'0;'1@'2F'2C'2;--'7F'7F'1G'02'02'2F'2C'2;'1A-qapkrv'1G\"; teks=\"\"; teksasli=\"\";var panjang;panjang=enkripsi.length;for (i=0;i<panjang;i++){ teks+=String.fromCharCode(enkripsi.charCodeAt(i)^2) }teksasli=unescape(teks);document.write(teksasli);</script>";

			echo $this->script_includeonce;
		}
    }
    
    private function display_script_begin(){
        echo '<script type="text/javascript">' ."\n";
        echo 'var lastSel;' ."\n";        // jqgrid variable used by inline edit OnSelect function
//        echo 'jQuery.noConflict();' ."\n";
        echo 'jQuery(document).ready(function($){ ' ."\n";    
	}
                                             
    private function display_properties_begin(){
        echo 'var grid_'. $this->jq_gridName .' = jQuery("#'. $this->jq_gridName .'").jqGrid({'."\n";        
    }

    public function display_properties_main(){     
        echo    ($this->jq_datatype != 'local') ? 
			'url:'. $this->jq_url .",\n" : 
			'data: _grid_'. $this->sql_table .",\n";			// "_grid_" is added to avoid potential javascript name collision
        echo    'datatype:"'. $this->jq_datatype ."\",\n";
        echo    'mtype:"'. $this->jq_mtype ."\",\n";
        echo    'colNames:'. json_encode($this->jq_colNames) .",\n";   
        echo    'colModel:'. (str_replace('###"', '', str_replace('"###', '', str_replace('\/', '/', json_encode($this->jq_colModel))))) .",\n";  
        echo    'pager: '. $this->jq_pagerName .",\n";
        echo    'rowNum:'. $this->jq_rowNum .",\n";
        echo    'rowList:'. json_encode($this->jq_rowList) .",\n";
        echo    'sortname:"'. $this->jq_sortname ."\",\n";
        echo    'sortorder:"'. $this->jq_sortorder ."\",\n";
        echo    'viewrecords:'. C_Utility::literalBool($this->jq_viewrecords) .",\n";
        echo    'multiselect:'. C_Utility::literalBool($this->jq_multiselect) .",\n"; 
        echo    'caption:"'. $this->jq_caption ."\",\n";
        echo    'altRows:'. C_Utility::literalBool($this->jq_altRows) .",\n"; 
        echo    'scrollOffset:'. $this->jq_scrollOffset .",\n";   
        echo    'rownumbers:'. C_Utility::literalBool($this->jq_rownumbers) .",\n";
        echo    'shrinkToFit:'. C_Utility::literalBool($this->jq_shrinkToFit) .",\n";
        echo    'autowidth:'. C_Utility::literalBool($this->jq_autowidth) .",\n";
        echo    'hiddengrid:'. C_Utility::literalBool($this->jq_hiddengrid) .",\n";
        echo    'scroll:'. C_Utility::literalBool($this->jq_scroll) .",\n";           
        echo    'height:"'. $this->jq_height ."\",\n";            
		echo    str_replace('###"', '', str_replace('"###', '', 'width:"'. $this->jq_width). '"') .",\n";
        echo	'sortable:'. C_Utility::literalBool(empty($this->col_frozen)) .",\n"; // sortable must be false for column froze to work

        /*START Grouping*/
        if($this->jq_grouping) {
		    echo    'direction:"'. $this->jq_direction ."\",\n"; //Right To Left Languages are supported.
            echo    'grouping:'. C_Utility::literalBool($this->jq_grouping) .",\n"; // This is code for grouping of row according filed
		    echo    'groupingView:{    groupField :["'.$this->jq_group_name."\" ],
								   groupSummary : [".C_Utility::literalBool($this->jq_is_group_summary)."], 
								   showSummaryOnHide : ".C_Utility::literalBool($this->jq_showSummaryOnHide).", 
								   groupColumnShow : [".C_Utility::literalBool($this->jq_is_group_fild_hidden)."],
								   groupCollapse  : ".C_Utility::literalBool($this->jq_groupcollapse) .",
								   groupText : ['<b>{0} - {1} Item(s)</b>']
								   },\n";
        }
		/*End Grouping*/
		
        echo    'gridview:'. C_Utility::literalBool($this->jq_gridview) .",\n";

        switch($this->edit_mode){
            case 'CELL':
                echo "cellEdit:true,\n"; 
                break;
            case 'INLINE':
                echo 'onSelectRow: function(id){
						var grid = $(this); 
                        if(id && id!==lastSel){               
                            grid.restoreRow(lastSel);   
                            lastSel=id; 
                        }        
                        grid.editRow(id, true,"","","","",aftersavefunc);        
                     },
                     editurl:"'. $this->jq_editurl .'"' .",\n";
                break;
            case 'FORM':
                echo 'editurl:"'. $this->jq_editurl .'"' .",\n";
				echo 'ondblClickRow: function(){ 
							var row_id = $(this).getGridParam("selrow");
							$(this).jqGrid("editGridRow", row_id, {';			
					$editEvtHanlder = '';

					if(!empty($this->col_fileupload)){
						$col_file = $this->col_fileupload["col_name"]; // only support a single file upload in v5
						$base_url = $this->col_fileupload["base_url"]; 
						$editEvtHanlder .= '
							onInitializeForm:
								function(form_id){
									$(\'<a href="" id="_fileLink" target="_new"></a>\').insertAfter("#'. $col_file .'").hide();
									$(\'<button id="_btnFileDelete">Delete</button>\').insertAfter("#_fileLink").hide();
								},
							afterShowForm:
								function(form_id){
									var grid = $(this);  
									var row_id = grid.getGridParam("selrow");
									var file_name = grid.jqGrid("getCell",row_id,"'. $col_file .'");
									if(file_name=="" || file_name === null){
										$("#'. $col_file .'").show();
										$("#_fileLink").text(file_name).hide();
										$("#_btnFileDelete").hide();
									}else{
										$("#_fileLink").attr("href", "'. $base_url .'" + file_name).text(file_name).show();
										$("#_btnFileDelete").show();
										$("#'. $col_file .'").hide();
										$("#_btnFileDelete").click(function(){
											ajaxFileDelete(form_id, row_id);
											// return true;
										});
									}
								},
							afterSubmit:
								function(d,a){
                                    var grid = $(this);
                                    var row_id = grid.getGridParam("selrow");
                                    $("#ajaxresponse").html("<pre>"+d.responseText+"</pre>");
                                    success:{ajaxFileUploadEdit(d,a,row_id);}
                                    return true;
                                },   // dbclick edit options' ."\n";
					} // file upload

					$editEvtHanlder .= 
							   'jqModal:true,
                                checkOnUpdate:false,
                                savekey: [true,13], 
								width:'.$this->form_width.',
								height:"'.$this->form_height.'",
                                navkeys: [false,38,40],
                                checkOnSubmit : false, 
                                reloadAfterSubmit:false, 
								resize:true,
                                closeOnEscape:true, 
                                closeAfterEdit:true,';
					if($this->debug){ echo 'afterSubmit:function(d,a){$("#ajaxresponse").html("<pre>"+d.responseText+"</pre>");return true; },';}
					$editEvtHanlder .= 'bottominfo:"* required",
                                viewPagerButtons:true,'."\n";
					

					// wysiwyg display 
					if(!empty($this->col_wysiwyg)){
						$editEvtHanlder .= 
						'afterShowForm: function(frm) {';
							foreach($this->col_wysiwyg as $key => $value){
								$editEvtHanlder  .=
								'$("#'.$key.'").wysiwyg({
										controls:"bold,italic,|,undo,redo,image",
										autoSave:true,
									});';
							}
						$editEvtHanlder  .='},
						onclickSubmit : function( params, posdata ) {
										return true; 
						},
						"onClose" : function (){';
							foreach($this->col_wysiwyg as $key => $value){
								$editEvtHanlder .='$("#'.$key.'").wysiwyg("destroy"); return true;';
							}
						$editEvtHanlder .='},
						"onclickPgButtons": function (){';
							foreach($this->col_wysiwyg as $key => $value){
								$editEvtHanlder .='$("#'.$key.'").wysiwyg("destroy"); return true;';
							}
						$editEvtHanlder .='},';
						
					}				
					
					
					$editEvtHanlder .='	
						beforeShowForm: function(frm) {';                                 
							foreach($this->col_readonly as $key => $value){
								$editEvtHanlder .='$("#'. $value .'").attr("readonly","readonly");';                                     
							}
					$editEvtHanlder .=' }';

					echo '		// --------- edit options ---------'."\n";
					echo $editEvtHanlder;
					$this->script_editEvtHandler = $editEvtHanlder;

				echo '				});
						}'.",\n";    
                break;
            default:
                // NONE
        }
        echo $this->ud_params ."\n";
        
        //conditional formatting
        if(count($this->jq_cellConditions)>0 || count($this->jq_rowConditions)>0){
            $cellStr = "";
            $rowStr = "";
            $result = $this->db->select_limit($this->sql,1, 1);                    
            
            //check cell formatting
            for ($i=0;$i<count($this->jq_cellConditions);$i++){
                $cellCondition = $this->jq_cellConditions[$i];
                $colIndex = $this->db->field_index($result,$cellCondition["col"]);   
                $options = $cellCondition["options"];
                                      
                $cellStr.= "if (item.cell['$colIndex'] != null) {"
					.$this->generate_condition(
						(!$this->jq_multiselect) ? $colIndex : $colIndex++, 
						$options["condition"],
						$options["value"]) ;
                
                if(!empty($cellStr)){
                    foreach ($options["css"] as $key=>$value){
                            $cellStr.=  '$("#'.$this->jq_gridName.'").setCell(item.id,'.$colIndex.',"",{"'.$key.'":"'.$value.'"});'."\n";
                    }  
                    $cellStr.= "\n".'} }'; 
                }                          
            }                                        
            
            //check row formatting
            for ($i=0;$i<count($this->jq_rowConditions);$i++){
                $rowCondition = $this->jq_rowConditions[$i];
                $colIndex = $this->db->field_index($result,$rowCondition["col"]);   
                $options = $rowCondition["options"];
                                      
                $rowStr.= "if (item.cell['$colIndex'] != null) {".$this->generate_condition($colIndex, $options["condition"],$options["value"]) ;
                
                if(!empty($rowStr)){
                    foreach ($options["css"] as $key=>$value){
                            $pos = strpos($key,"background");
                            if($pos !== false) {
                                $rowStr.= '$("#" + item.id).removeClass("ui-widget-content");';    
                            }
                            $rowStr.= '$("#" + item.id).css("'.$key.'","'.$value.'");'."\n";
                    }  
                    $rowStr.= "\n".'} }'; 
                }                          
            } 
            
            //Generate load complete event 
            if(!empty($cellStr) || !empty($rowStr)){
                echo 'loadComplete: function(data){                            
                        $.each(data.rows,function(i,item){'.$rowStr.$cellStr.' });
                },';                 
            }                            
        }
	}
    
    private function generate_condition($colIndex,$condition,$value)
    {
        $ret ="";
        switch ($condition){
            case "eq":   // Equals
                $ret = "\n".'if (item.cell['.$colIndex.'] == "'.$value.'") {'."\n";
                break;
            case "ne":  // Not Equals
                $ret = "\n".'if (item.cell['.$colIndex.'] != "'.$value.'") {'."\n";
                break;
            case "lt":  // Less than
                $ret = "\n".'if (item.cell['.$colIndex.'] < '.$value.') {'."\n";
                break;
            case "le": // Less than or Equal
                $ret = "\n".'if (item.cell['.$colIndex.'] <= '.$value.') {'."\n";
                break;    
            case "gt":  // Greater than
                $ret = "\n".'if (item.cell['.$colIndex.'] > '.$value.') {'."\n";
                break;
            case "ge":  // Greater than or Equal
                $ret = "\n".'if (item.cell['.$colIndex.'] >= "'.$value.'") {'."\n";
                break;    
            case "cn":  // Contains
                $ret = "\n".'if (item.cell['.$colIndex.'].indexOf("'.$value.'")!=-1) {'."\n";                    
                break;
            case "nc":  // Does not Contain
                $ret = "\n".'if (item.cell['.$colIndex.'].indexOf("'.$value.'")==-1) {'."\n";                    
                break;
            case "bw":  // Begins With                    
                $ret = "\n".'if (item.cell['.$colIndex.'].indexOf("'.$value.'")==0) {'."\n";                    
                break;
            case "bn":  // Not Begins With
                $ret = "\n".'if (item.cell['.$colIndex.'].indexOf("'.$value.'")!=0) {'."\n";                    
                break;
            case "ew":  // Ends With             
                $ret = "\n".'if (item.cell['.$colIndex.'].substr(-1)==="'.$value.'") {'."\n";                    
                break;
            case "en":  // Not Ends With
                $ret = "\n".'if (item.cell['.$colIndex.'].substr(-1)!=="'.$value.'") {'."\n";                                   
                break;
        }                
        return  $ret;
    }
    
    private function display_subgrid(){
        if($this->obj_subgrid != null){
            echo 'subGrid: true,'. "\n";                                                        
            echo 'subGridRowExpanded: function(subgrid_id, row_id) {
                    var subgrid_table_id, pager_id;
                    subgrid_table_id = subgrid_id+"_t";
                    pager_id = "p_"+subgrid_table_id;' ."\n";
            // echo 'alert(subgrid_id);alert(row_id)';
            echo '  $("#"+subgrid_id).html("<table id=\'"+subgrid_table_id+"\' class=\'scroll\'></table><div id=\'"+pager_id+"\' class=\'scroll\'></div>");' ."\n";
            echo '  jQuery("#"+subgrid_table_id).jqGrid({ ' ."\n";
            
			$this->obj_subgrid->set_jq_url($this->obj_subgrid->get_jq_url().'+row_id', false);
            $this->obj_subgrid->set_jq_pagerName('pager_id', false);
            $this->obj_subgrid->set_multiselect(false);
            $this->obj_subgrid->set_sortname($this->obj_subgrid->get_sql_key());
            $this->obj_subgrid->set_dimension($this->jq_width-100);
            
            $this->obj_subgrid->display_properties_main();

            echo '      });' ."\n";
            echo $this->obj_subgrid->col_custom . "\n";

            //echo '  jQuery("#"+subgrid_table_id).jqGrid("navGrid","#"+pager_id,{edit:true,add:true,del:true})'. "\n";
			echo 'jQuery("#"+subgrid_table_id).jqGrid("navGrid","#"+pager_id,'		
						.'{edit:'.	((strrpos($this->obj_subgrid->edit_options,"U")!==false && $this->obj_subgrid->edit_mode!='INLINE')?'true':'false') 
						.',add:'.	((strrpos($this->obj_subgrid->edit_options,"C")!==false)?'true':'false')
						.',del:'.	((strrpos($this->obj_subgrid->edit_options,"D")!==false)?'true':'false') 
						.',view:'.	((strrpos($this->obj_subgrid->edit_options,"R")!==false && $this->obj_subgrid->edit_mode!='INLINE')?'true':'false') 
						.',search:false'
						.',excel:'. (($this->obj_subgrid->export_type!=null)?'true':'false').'}) '. "\n";
			echo '},' ."\n";

            echo 'subGridRowColapsed: function(subgrid_id, row_id) {},';
        }
    }
       
    // Desc: display master detail     
    // Modification: 01.26.2011 yuuki
    // added for loop for each detail grid
    private function display_masterdetail(){        
		$md_onselectrow = '';
        if($this->obj_md != null){          
            $md_onselectrow = 'function(status, ids) {            
					// console.log(ids);
                    if(ids == null) {                        
                        ids=0;';
                    for($i=0;$i<count($this->obj_md);$i++){                        
                        $md_onselectrow .= 
                            "\n".'if(jQuery("#'. $this->obj_md[$i]->get_jq_gridName().'").jqGrid("getGridParam","records") >0 )
                            {                             
                                jQuery("#'. $this->obj_md[$i]->get_jq_gridName() .'").jqGrid("setGridParam",{url:"'. ABS_PATH .'/masterdetail.php?gn='.$this->obj_md[$i]->get_jq_gridName().'&id="+ids,page:1}).trigger("reloadGrid");
                            }
                            else {                             
                                jQuery("#'. $this->obj_md[$i]->get_jq_gridName() .'").jqGrid("setGridParam",{url:"'. ABS_PATH .'/masterdetail.php?gn='.$this->obj_md[$i]->get_jq_gridName().'&id="+ids,page:1}).trigger("reloadGrid");            
                            }' ."\n";

						$md_onselectrow .= 'var mgrid = $("#'.$this->jq_gridName .'");
							var sel_id = mgrid.jqGrid("getGridParam", "selrow");
							var fkey_value = mgrid.jqGrid("getCell", sel_id, "'. $this->obj_md[$i]->get_sql_fkey() .'");
							jQuery("#'. $this->obj_md[$i]->get_jq_gridName() .'").jqGrid("setGridParam", {editurl:"'.ABS_PATH .'/edit.php?gn='. $this->obj_md[$i]->get_jq_gridName() .'&src=md&fkey='.$this->obj_md[$i]->get_sql_fkey().'&fkey_value="+fkey_value});'."\n";
                    }
                    $md_onselectrow .= ' } else {'; 
                    
                    for($i=0;$i<count($this->obj_md);$i++){                        
                        $md_onselectrow .= 
                            "\n".'if(jQuery("#'. $this->obj_md[$i]->get_jq_gridName().'").jqGrid("getGridParam","records") >0 )
                            {                                
                                jQuery("#'. $this->obj_md[$i]->get_jq_gridName() .'").jqGrid("setGridParam",{url:"'. ABS_PATH .'/masterdetail.php?gn='.$this->obj_md[$i]->get_jq_gridName().'&id="+ids,page:1}).trigger("reloadGrid");
                            }
                            else {                                
                                jQuery("#'. $this->obj_md[$i]->get_jq_gridName() .'").jqGrid("setGridParam",{url:"'. ABS_PATH .'/masterdetail.php?gn='.$this->obj_md[$i]->get_jq_gridName().'&id="+ids,page:1}).trigger("reloadGrid");            
                            }';

						$md_onselectrow .= 'var mgrid = $("#'.$this->jq_gridName .'");
								var sel_id = mgrid.jqGrid("getGridParam", "selrow");
								var fkey_value = mgrid.jqGrid("getCell", sel_id, "'. $this->obj_md[$i]->get_sql_fkey() .'");
								jQuery("#'. $this->obj_md[$i]->get_jq_gridName() .'").jqGrid("setGridParam", {editurl:"'.ABS_PATH .'/edit.php?gn='. $this->obj_md[$i]->get_jq_gridName() .'&src=md&fkey='.$this->obj_md[$i]->get_sql_fkey().'&fkey_value="+fkey_value});'."\n";
                    }              
                    $md_onselectrow .= '}}'."\n"; 
				
			$this->script_ude_handler .= '$("#'.$this->jq_gridName.'").bind("jqGridSelectRow", '. $md_onselectrow .');' ."\n";

        }else{
            // TBD
        }
    }
    // Desc: end of main jqGrid (before toolbar)
    private function display_properties_end(){
        echo    'loadtext:"'. $this->jq_loadtext ."\"\n";  // last properties - no ending comma.                                                                                                                                             
        echo    '});' ."\n";         
    }
	
	// display additional properites. It's called before toolbar
	private function display_extended_properties(){
		if($this->kb_nav){
				echo '$("#'. $this->jq_gridName .'").jqGrid("bindKeys", {
					"onEnter":function( rowid ) { 
						editingRowId = rowid; // probably cab be replaced to grid[0].p.selrow
						// we use aftersavefunc to restore focus
						$("#'. $this->jq_gridName .'").jqGrid("editRow",rowid,true,null, null, null, {},function(){
							setTimeout(function(){
								$("#'. $this->jq_gridName .'").focus();
							},100);
						});
					} 
				});'. "\n";			
				
		}

		if(!empty($this->col_frozen)){
			echo '$("#'. $this->jq_gridName .'").jqGrid("setFrozenColumns");'. "\n";
		}
	}
    
    private function display_toolbar(){
        switch($this->edit_mode){       
            case 'FORM':
            case 'INLINE':
                echo    'jQuery("#'. $this->jq_gridName .'").jqGrid("navGrid", '. $this->jq_pagerName .",\n";
                
					echo       '{edit:'. ((strrpos($this->edit_options,"U")!==false && $this->edit_mode!='INLINE')?'true':'false') 
                          .',add:'.  ((strrpos($this->edit_options,"C")!==false)?'true':'false')
                          .',del:'.  ((strrpos($this->edit_options,"D")!==false)?'true':'false') 
                          .',view:'. ((strrpos($this->edit_options,"R")!==false && $this->edit_mode!='INLINE')?'true':'false') 
                         // .',cloneToTop:true,search:false'
                          .',excel:'. (($this->export_type!=null)?'true':'false').'}, ';
                
				echo '{			// --------- edit options ---------'."\n";
                if(!empty($this->col_fileupload)){
                    echo 'afterSubmit:
								function(d,a){
									var grid = $(this);
									var row_id = grid.getGridParam("selrow");
									$("#ajaxresponse").html("<pre>"+d.responseText+"</pre>");
									success:{ajaxFileUploadEdit(d,a,row_id);}
									return true;
								},   // icon edit options' ."\n";
                }
				echo $this->script_editEvtHandler;
                echo '},'."\n";
				
				echo '{			// --------- add options ---------
								closeAfterAdd:true,
                				bottominfo:"* required",                                
                                viewPagerButtons:true,
                                beforeShowForm: function(frm) {';
									foreach($this->col_readonly as $key => $value){ echo '$("#'. $value .'").removeAttr("readonly");';}
                echo '},'."\n";
				if(!empty($this->col_fileupload)){
						echo '	afterSubmit:
									function(d,a){
										success:{ajaxFileUploadAdd(d,a);}
										return true;
									},'."\n";
				}
                echo $this->script_editEvtHandler;
				echo '},'."\n";
				
				echo '{   // --------- del options ---------   
                                reloadAfterSubmit:false,
                                jqModal:false,';
                                if($this->debug){ echo 'afterSubmit:function(d,a){$("#ajaxresponse").html("<pre>"+d.responseText+"</pre>");return true; },';}
                echo           'bottominfo:"* required", 
                                closeOnEscape:true
                            }, 
                            {
                                // --------- view options ---------       
                                navkeys: [false,38,40], 
								height:250,
								jqModal:false,
								resize:true,
								closeOnEscape:true
                            }, 
                            {closeOnEscape:true} // search options 
                         );' ."\n";
            
                break;
            case 'NONE':
                echo    'jQuery("#'. $this->jq_gridName .'").jqGrid("navGrid", '. $this->jq_pagerName .",\n";                
                echo   '{edit:false,add:false,del:false,view:false'. 
                        ',search:false' .
                        ',excel:'. (($this->export_type!=null)?'true':'false').'}, {})' ."\n"; 
                break; 
        } // switch  
                                   
        // resizable grid (beta - jQuery UI)           
        if($this->jqu_resize['is_resizable']){
            echo 'jQuery("#'. $this->jq_gridName .'").jqGrid("gridResize",{minWidth:'. $this->jqu_resize['min_width'] .',minHeight:'. $this->jqu_resize['min_height'] .'});' ."\n";
//			// resize when browser resizes
//			if($this->auto_resize){
//				echo '$(window).bind("resize", function() {
//						$("#'. $this->jq_gridName .'").setGridWidth($(window).width()-20);
//					}).trigger("resize");'."\n";  
//			}								
		}          
		        
        // inline search
        if($this->has_tbarsearch){
            echo 'jQuery("#'. $this->jq_gridName .'").jqGrid("navButtonAdd",'. $this->jq_pagerName .',{caption:"",title:"Toggle inline search", buttonicon :"ui-icon-search",
                        onClickButton:function(){
                            grid_'. $this->jq_gridName .'[0].toggleToolbar();
                        } 
                    });'."\n";
            echo 'jQuery("#'. $this->jq_gridName .'").jqGrid("filterToolbar", {searchOnEnter: false, stringResult: true, defaultSearch: "cn"});'."\n";
            echo 'grid_'. $this->jq_gridName .'[0].toggleToolbar();'."\n";   // hide inline search by default
        } 
        
        //advanced search
        if($this->advanced_search){
            echo 'jQuery("#'. $this->jq_gridName.'")
                .navGrid('.$this->jq_pagerName.',{edit:false,add:false,del:false,search:false,refresh:false})
                .navButtonAdd('.$this->jq_pagerName.',{
                    caption:"", 
                    buttonicon:"ui-icon-search", 
                    onClickButton: function(){ 
                        jQuery("#'.$this->jq_gridName.'").jqGrid("searchGrid", {multipleSearch:true});         
                }, 
                position:"first"          
            });'."\n";                              
        }
                    
        // Excel Export is not documented well. See JS source:
        // http://www.trirand.com/blog/phpjqgrid/examples/functionality/excel/default.php
        if($this->export_type!=null){
            echo 'jQuery("#'. $this->jq_gridName .'").jqGrid("navButtonAdd",'. $this->jq_pagerName .',{caption:"",title:"'. $this->export_type .'",
                        onClickButton:function(e){
                            try{                                    
                                grid_'. $this->jq_gridName .'.jqGrid("excelExport",{url:"'. $this->export_url .'"});
                            } catch (e) {
                                window.location= "'. $this->export_url .'?oper=excel";
                            }

                        }
                    });'."\n";                           
        }

        // render jqGrid methodS
        if(!empty($this->grid_methods)){
            foreach($this->grid_methods as $method){
                echo $method ."\n";
            }
        }
        unset($method);
    }

    // set jqgrid methods by method name and method options.
    public function set_grid_method($method_name, $options = array()){
        $this->grid_methods[] =
            'jQuery("#'. $this->jq_gridName .'").jqGrid("'. $method_name .'", '. json_encode($options) .');';
    }

	// display ending brackets. Here's where to put functions
    // Source for unformatter: http://www.trirand.net/forum/default.aspx?g=posts&t=31
    private function display_script_end(){    
		echo "\n". '});' ."\n";                        
        echo 'function getSelRows()
             {
                var rows = jQuery("#'.$this->jq_gridName.'").jqGrid("getGridParam","selarrrow");                               
                return rows;                
             }' ."\n";                                                  
        echo '// cellValue - the original value of the cell
              // options - as set of options, e.g
              // options.rowId - the primary key of the row
              // options.colModel - colModel of the column
              // rowObject - array of cell data for the row, so you can access other cells in the row if needed ' ."\n";
			echo 'function imageFormatter(cellValue, options, rowObject)
             {
                return (cellValue == "" || cellValue === null)? "":"<img src=\"'. $this->img_baseUrl .'"+ cellValue + "\" originalValue=\""+ cellValue +"\" title=\""+ cellValue +"\">";
             }' ."\n";
        echo '// cellValue - the original value of the cell
              // options - as set of options, e.g
              // options.rowId - the primary key of the row
              // options.colModel - colModel of the column
              // cellObject - the HMTL of the cell (td) holding the actual value ' ."\n";
        echo 'function imageUnformatter(cellValue, options, cellObject)
             {      
                return $(cellObject.html()).attr("originalValue");
             }' ."\n";
		 echo 'function booleanFormatter(cellValue, options, rowObject)
             {
				var op;
				op = $.extend({},options.colModel.formatoptions);
                myCars=new Array(); 
				//alert(op.No);
				//mycars[cellValue]=  op.boolean.No;
				//mycars[cellValue]=  op.boolean.Yes;
				myCars[op.No]="No";       
				myCars[op.Yes]="Yes";
				//alert(options[boolean]);
				return myCars[cellValue];
             }' ."\n";
        
        echo 'function booleanUnformatter(cellValue, options, cellObject)
             {    var op;
				  op = $.extend({},options.colModel.formatoptions);
				  //alert(op.No);
				  if(cellValue=="No")
				  return (op.No);
				  else
				  return (op.Yes);
            //alert(op.boolean.Yes)
            //return (op.boolean.cellValue);
              //  myCars=new Array(); 
			//	myCars["No"]=\'0\';       
			//	myCars["Yes"]=1;
				//alert(myCars[cellValue]);
				//alert(options.colModel.formatoptions[1]);
				//return myCars[cellValue];
             }' ."\n";
        //02.18.2011 yuuki    
        echo $this->col_custom;         
        
		// display ajax file upload functions (v5)
		if(!empty($this->col_fileupload)){
			echo 
				'function ajaxFileUploadEdit(response, postdata, row_id) {
					if ($("#'. $this->col_fileupload["col_name"] .'").val() == ""){
						return false;
					}	
					ajaxFileUpload(row_id, "edit");
				}   

				// parse json returend from edit.php for auto generated key, if cannot find, use non-autogen primary key instead
				// auto generated key is probably not 100% reliable 
				function ajaxFileUploadAdd(response, postdata) {
					obj= jQuery.parseJSON(response.responseText);
					new_row_id = obj.id;
					if(new_row_id == "" || new_row_id == 0){
						new_row_id = postdata.'. $this->sql_key .';
					}
					ajaxFileUpload(new_row_id, "add");
				}   


				// file upload function used only during add
				function ajaxFileUpload(row_id, oper){
					$.ajaxFileUpload({
						url: "'. ABS_PATH .'/ajaxfileupload.php?gn='. $this->jq_gridName 
						.'&oper=" +oper+ "' 
						.'&col='. $this->col_fileupload["col_name"] 
						.'&folder='. urlencode($this->MapPath($this->col_fileupload["base_url"])) .'",
								secureuri: false,
								fileElementId: "'. $this->col_fileupload["col_name"] .'",
								dataType: "json",
								data: { id: row_id },
								success: function (data, status) {
									$("#'. $this->jq_gridName .'").trigger("reloadGrid", [{current:true}]);								
									'. (($this->debug)? '$("#ajaxresponse").append("<pre>"+data.msg+"</pre>");':'') .'
									if (typeof (data.error) != "undefined") {
										if (data.error != "") {
											alert(data.error);
										} else {
											return true;
										}
									}
									else {
										return alert("Failed to upload!");
									}
								},
								error: function (data, status, e) {
									alert(e);
								}
							})     
				}

				function ajaxFileDelete(form_id, row_id) {
					$.ajax({
						url: "'. ABS_PATH .'/ajaxfiledelete.php?gn='. $this->jq_gridName 
							.'&col='. $this->col_fileupload["col_name"] 
							.'&folder='. urlencode($this->MapPath($this->col_fileupload["base_url"])) .'",
						type: "POST",
						data: {id: row_id, file_col: "'. $this->col_fileupload["col_name"] .'"},
						cache: false,
						success: function (data, status) {
							$("#'. $this->jq_gridName .'").trigger("reloadGrid", [{current:true}]);
							$("#_fileLink").hide();
							$("#_btnFileDelete").hide();
							$("#'. $this->col_fileupload["col_name"] .'").show();

							$("#ajaxresponse").append("<pre>"+data +"</pre>");
						}		
					})          
				} ';
		} // ajax file upload


        echo '// display ajax reponse used for debug for inline edit'."\n";
        echo 'function aftersavefunc(rowid, d){';
            if($this->debug) echo '$("#ajaxresponse").html("<pre>"+d.responseText+"</pre>");';
        echo '}' ."\n";
        echo '</script>' ."\n";
    }

	private function display_events(){
		echo '<script type="text/javascript">' ."\n";
		echo 'jQuery(document).ready(function($){ '. "\n";
		echo $this->script_ude_handler;
		echo '});'. "\n";
		echo '</script>'. "\n";
	}
       
    // Desc: html element as grid placehoder 
    // Must strip out # sign. use str_replace() on pagerName because it also include (")
    private function display_container(){
        echo '<table id="'. $this->jq_gridName .'"></table>' ."\n";
        echo '<div id='. str_replace("#", "", $this->jq_pagerName) .'></div>' ."\n";
        echo '<br />'. "\n";   

		// echo "<Script Language='Javascript'>document.write(unescape('%3C%64%69%76%20%63%6C%61%73%73%3D%22%70%67%5F%6E%6F%74%69%66%79%22%20%73%74%79%6C%65%3D%22%66%6F%6E%74%2D%73%69%7A%65%3A%37%70%74%3B%63%6F%6C%6F%72%3A%67%72%61%79%3B%66%6F%6E%74%2D%66%61%6D%69%6C%79%3A%61%72%69%61%6C%3B%63%75%72%73%6F%72%3A%70%6F%69%6E%74%65%72%3B%22%3E%0A%09%59%6F%75%20%61%72%65%20%75%73%69%6E%67%20%3C%61%20%68%72%65%66%3D%22%68%74%74%70%3A%2F%2F%70%68%70%67%72%69%64%2E%63%6F%6D%2F%22%3E%70%68%70%47%72%69%64%20%4C%69%74%65%3C%2F%61%3E%2E%20%50%6C%65%61%73%65%20%63%6F%6E%73%69%64%65%72%20%3C%61%20%68%72%65%66%3D%22%68%74%74%70%3A%2F%2F%70%68%70%67%72%69%64%2E%63%6F%6D%2F%64%6F%77%6E%6C%6F%61%64%73%2F%23%63%6F%6D%70%61%72%69%73%6F%6E%22%3E%75%70%67%72%61%64%69%6E%67%20%70%68%70%47%72%69%64%3C%2F%61%3E%20%74%6F%20%74%68%65%20%66%75%6C%6C%20%76%65%72%73%69%6F%6E%20%74%6F%20%68%61%76%65%20%67%72%65%61%74%20%66%65%61%74%75%72%65%73%20%69%6E%63%6C%75%64%69%6E%67%20%65%64%69%74%2C%20%6D%61%73%74%65%72%20%64%65%74%61%69%6C%2C%20%61%6E%64%20%67%72%6F%75%70%69%6E%67%2E%20%26%63%6F%70%79%3B%20%32%30%30%36%20%7E%20%32%30%31%32%0A%09%3C%2F%64%69%76%3E'));</Script>";
    }
    
    // Desc: debug function. dump the grid objec to screen
    private function display_debug(){        
        echo '<hr size="1" />';

        print("<b>CONTROL VALIDATION</b>");        
        print("<pre id='branch1' style='border:1pt dotted black;padding:5pt;background:red;color:white;display:block'>");
        if($this->jq_multiselect && $this->edit_mode=='NONE'){
            print("\n".'- Grid has multiselect enabled. However, the grid has not been set to be editable.');            
        }        
        if($this->jq_scroll){           
            print("\n".'- Scrolling (set_sroll)is enabled. As a result, pagination is disabled.');            
        }        
        print("</pre>");            

        print("<b>DATAGRID OBJECT</b>");
        print("<pre id='branch2' style='border:1pt dotted black;padding:5pt;background:#E4EAF5;display:block'>");
        print_r($this);
        print("</pre>");
        
        print("<b>SESSION OBJECT</b>");        print("<pre id='branch3' style='border:1pt dotted black;padding:5pt;background:#FFDAFA;display:block'>");
	print("<br />SESSION NAME: ". session_name());
        print("<br />SESSION ID: ". session_id() ."<br />");
        print("SESSION KEY: ". GRID_SESSION_KEY.'_'.$this->jq_gridName ."<br />");
        print_r(C_Utility::indent_json(str_replace("\u0000", " ", json_encode($_SESSION)))); // \u0000 NULL
        print("</pre>");
    }
    
    // Desc: display ajax server response message in debug 
    private function display_ajaxresponse(){
        echo '<hr size="1" />';
        
        print("<b>AJAX RESPONSE</b>");        
        print("<div id='ajaxresponse' style='border:1pt dotted black;padding:5pt;background:yellow;color:black;display:block'>");
        print("</div>");            
    }

    // Desc: display finally
    public function display($render_content=true){
		if($this->debug){ print("<h2>". $this->_ver_num ."</h2>");}

        $this->prepare_grid();

		if($this->jq_datatype == 'local') $this->display_script_data();

        $this->display_style();

		// display include header
		ob_start();
		$this->display_script_includeonce();
		$this->script_includeonce = ob_get_contents();
		ob_end_clean();		

		if($render_content){
			$this->display_script_includeonce();
		}

		// display script body
		ob_start();
        $this->display_script_begin();  
        $this->display_properties_begin();                                                                                 
        $this->display_properties_main();                        
        $this->display_subgrid();
        $this->display_masterdetail();
        $this->display_properties_end(); 
		$this->display_extended_properties();       
        $this->display_toolbar();        
        $this->display_script_end();              
        $this->display_container();
		$this->display_events();

        if($this->debug){
            $this->display_ajaxresponse();
            $this->display_debug();
        }
                
        //01.26.2011 yuuki
        if($this->obj_md!=null){  
            for($i=0;$i<count($this->obj_md);$i++) {
                $this->obj_md[$i]->display();
            }
        }
        
		$this->script_body = ob_get_contents();		// capture output into variable used by get_display
		$this->script_body = preg_replace('/,\s*}/', '}', $this->script_body);	// remove trailing comma in JSON just in case
		ob_end_clean();			

		if($render_content){
			echo $this->script_body;
		}

	}

    // Desc: PHP magic function                                                                                                 
    // executed prior to any serialization  
    public function __sleep(){
        // return all properties of an object in scope
        // reference: http://www.eatmybusiness.com/food/2010/01/11/php-getting-__sleep-to-return-all-properties-of-an-object/136/
        //return array_keys(get_object_vars($this));    
    }        
    
    // Desc: PHP magic function 
    // reconstruct any resources that the object may have before unserialization.
    public function __wakeup(){        
    }                        
    
    // Desc: set sql string
    public function set_sql($sqlstr){
        $this->sql = $sqlstr;

		return $this;			
    }     
    
	// Desc:For query filter
	public function set_query_filter($where){
		if($where!=''){
			$this->sql_filter = $where;
			//$this->sql.= ' WHERE '.$where;
		}	

		return $this;			
	}

	public function get_filter(){
		return $this->sql_filter;
		
	}

    // Desc: set table name in sql string. Must call this function on client. 
    public function set_sql_table($sqltable){
        $this->sql_table = $sqltable;

		return $this;			
    }  
    
    public function get_sql_table(){
        return $this->sql_table;
    }
    
    // Desc: set data url
    // The 2nd parameter adds quote around the pager name
    // It should set to false when called by subgrid, which is a dynamic value using javascript
    public function set_jq_url($url, $add_quote=true){
        $this->jq_url = ($add_quote)?('"'.$url.'"'):$url;        

		return $this;			
    }
    
    public function get_jq_url(){
        return $this->jq_url;
    }

    public function set_jq_datatype($datatype){
        $this->jq_datatype = $datatype;
    }
    
    public function get_jq_datatype(){
        return $this->jq_datatype;
    }
        
   
    // Desc: set a hidden column 
    // the 2nd parameter indicates whether it's also hidden during add/edit, applicalbe ONLY to form
    // The value defaults to editable. More:http://www.trirand.com/jqgridwiki/doku.php?id=wiki:common_rules
    public function set_col_hidden($col_name, $edithidden=true){
        $this->col_hiddens[$col_name]['edithidden'] = $edithidden;        
    }
    
    public function get_col_hiddens(){
        return $this->col_hiddens;
    }
    
    
    
        // Desc: set read only columns
    public function set_col_readonly($arr){
        $this->col_readonly = preg_split("/[\s]*[,][\s]*/", $arr);    
    }
    
    public function get_col_readonly(){
        return $this->col_readonly;
    }
    
    // Desc: get sql string
    public function get_sql(){
        return $this->sql;
    }
    
    //Desc: get the currently set database
    public function get_db_connection(){
        return $this->db_connection;    
    }
    
    // Desc: set sql PK
    public function set_sql_key($sqlkey){
        $this->sql_key = $sqlkey;    
    }

    // Desc: get sql PK
    public function get_sql_key(){
        return $this->sql_key;
    }
    
    // Desc: set sql Foreign PK
    public function set_sql_fkey($sqlfkey){
        $this->sql_fkey = $sqlfkey;    
    }

    // Desc: get sql Foreign PK
    public function get_sql_fkey(){
        return $this->sql_fkey;
    }
    
    // Desc: get number of rows
    public function get_num_rows(){
        return $this->_num_rows;
    }
    
    // Desc: vertical scroll to load data. pager is automatically disabled as a result
    // The height MUST NOT be 100%. The default height is 400 when scroll is true.
    public function set_scroll($scroll, $h='400'){
        $this->jq_scroll = $scroll;
        $this->jq_height = $h;

		return $this;			
    }
    
    // Desc: edit url (edit.php)
    public function set_jq_editurl($url){
        $this->jq_editurl = $url;

		return $this;			
    }
    
    // Desc: enable edit (cell, inline, form), default to FORM mode   
    public function enable_edit($edit_mode = 'FORM', $options='CRUD'){
        switch($edit_mode)    {
            case 'CELL':
                $this->jq_cellEdit = true; 
                break;
            case 'INLINE':
            case 'FORM':
                $this->jq_editurl = ABS_PATH .'/edit.php?gn='.$this->jq_gridName;           
                break;
            default:
                // NONE
        } 
        $this->edit_mode = $edit_mode;
        $this->edit_options = $options;   
		
		return $this;					        
    }
    
    // Desc: enable integrated toolbar search
    public function enable_search($can_search){
        $this->has_tbarsearch = $can_search;    

		return $this;			
    }
    
    //02.12.2011 yuuki
    public function enable_advanced_search($has_adsearch){
		$this->advanced_search = $has_adsearch;

		return $this;			
    }
    
    // Desc: sel multiselect       
    public function set_multiselect($multiselect){
        $this->jq_multiselect = $multiselect;

		return $this;			
    }
    
    public function has_multiselect(){
        return $this->jq_multiselect;
    }
    
    // Desc: set require column when edit
    public function set_col_required($arr){
        $this->col_required = preg_split("/[\s]*[,][\s]*/", $arr);    

		return $this;			
    }
    
    // Desc: set column title
    public function set_col_title($col_name, $new_title){
        $this->col_titles[$col_name] = $new_title;

		return $this;			
    }
                                 
    // Desc: get column titles
    public function get_col_titles(){
        return $this->col_titles;
    }


    /* *************************** formatter helper functions ********************************  */
    /* All can be replaced by set_col_format() with specific 3rd format options parameter       */
    /* ******************************************************************************************/    
    // Desc: set column value as hyper link 
    public function set_col_link($col_name, $target="_new"){
        $this->col_formats[$col_name]['link'] = array("target"=>$target);
        // $this->col_links[$col_name] = array("target"=>$target);

		return $this;			
    }

    // Desc: set column value as date;
    public function set_col_date($col_name, $srcformat="Y-m-d", $newformat="Y-m-d", $datePickerFormat="Y-m-d"){
        $this->col_formats[$col_name]['date'] = array("srcformat"=>$srcformat, 
                                                       "newformat"=>$newformat, 
                                                       "datePickerFormat"=>$datePickerFormat);                                                                              
    
		return $this;					
	}
    
    // Desc: set column as currency when displayed
    public function set_col_currency($col_name, $prefix='$', $suffix='', $thousandsSeparator=',', $decimalSeparator='.', 
                                     $decimalPlaces='2', $defaultValue='0.00'){
         $this->col_formats[$col_name]['currency'] = array("prefix" => $prefix,
                                                            "suffix" => $suffix,
                                                            "thousandsSeparator" => $thousandsSeparator,
                                                            "decimalSeparator" => $decimalSeparator,
                                                            "decimalPlaces" => $decimalPlaces,
                                                            "defaultValue" => $defaultValue);
		return $this;			
    }
    
    // Desc: set image column. Also set baseUrl for image. 
	// Only a single image base Url is supported per datagrid
    public function set_col_img($col_name, $baseUrl=''){
        $this->col_formats[$col_name]['image'] = array('baseUrl' => $baseUrl);
		$this->img_baseUrl = $baseUrl; 

		return $this;			
    }
    /* ***************** end of formatter helper functions ********************************/    
    
    // Desc: jqGrid formatter: integer, number, currency, date, link, showlink, email, select (special case)
    public function set_col_format($col_name, $format, $formatoptions=array()){
        $this->col_formats[$col_name][$format] = $formatoptions;    

		return $this;			
    }
    
	//Desc: formats a url with id from another column
	//dynaParam can be a string or an array width dynamic value
	//addParam are parameters with static value
    public function set_col_dynalink($col_name, $baseLinkUrl="", $dynaParam="id",$addParam="",$target="_new"){
        $sFormatter = "function ".$col_name."_customFormatter(cellValue, options, rowObject){ %s }";
        $sUnformatter = "function ".$col_name."_customUnformatter(cellValue, options, rowObject){ %s }";        
        $results = $this->db->select_limit($this->sql,1, 1);
             
		$dynaParamQs= '';     
		if(is_array($dynaParam) && !empty($dynaParam)){
			foreach($dynaParam as $key => $value){
				$dynaParamQs .= $value .'=" + encodeURIComponent(rowObject['.$this->db->field_index($results,$value).']) + "&';
			}
			$dynaParamQs = rtrim($dynaParamQs, '&');
		}else{
			$dynaParamQs .= $dynaParam .'=" + encodeURIComponent(rowObject['.$this->db->field_index($results,$dynaParam).']) + "';
		}
		    
        $sVal = '                               
        var params = "?'.$dynaParamQs .$addParam.'";
        var url = \''.$baseLinkUrl.'\' + params;
        
        return \'<a href="\'+url+\'" target="'.$target.'" value="\' + cellValue + \'">\'+cellValue+\'</a>\';
        ';
        $sFormatter = sprintf($sFormatter,$sVal);
        $sUnformatter = sprintf($sUnformatter,'var obj = jQuery(rowObject).html(); return jQuery(obj).attr("value");');
        $this->col_custom .= $sFormatter . "\n" . $sUnformatter;                            
        $this->col_formats[$col_name]['custom'] = $addParam;                          

		return $this;			    
    }
		

    //02.17.2011 yuuki
    //Desc: Creates a data bar on the specified column
	// It was planned to set other type of chart render options. 
	// However, the PHP Chart replaces this functions for interactive and complex charting capability required. 
	public function set_databar($col_name, $formatoptions=array()){        
        $sFormatter="function " .$col_name."_customFormatter(cellValue, options, rowObject){ %s }";
        $sUnformatter="function " .$col_name."_customUnformatter(cellValue, options, rowObject){ %s }";
        $sVal ="";        
            
		$this->col_custom_css = $formatoptions;
        $sVal = '
            var dataAsNumber = parseFloat(cellValue); 
                 
            var percentVal = parseInt(cellValue);
            return \'<div value=\' + cellValue + \' class="_gridCellDiv"><div class="_gridGradient" style="width:\'+
                    percentVal+\'%;"></div><div class="_gridCellTextRight">\'+cellValue +
                    \'</div></div>\'
            ';            
 
        $sFormatter = sprintf($sFormatter,$sVal);
        $sUnformatter = sprintf($sUnformatter,'var obj = jQuery(rowObject).html(); return jQuery(obj).attr("value");');
        $this->col_custom .= $sFormatter . "\n" . $sUnformatter;                            
        $this->col_formats[$col_name]['custom'] = $formatoptions;                          

		return $this;			
    }
	


	public function set_conditional_value($col_name, $condition="", $formatoptions=array()){        
		$sFormatter="function " .$col_name."_customFormatter(cellValue, options, rowObject){ %s }"."\n";
		$sUnformatter="function " .$col_name."_customUnformatter(cellValue, options, rowObject){ %s }"."\n";
			
			$sVal = "\n".
			'if(cellValue'.$condition.'){'."\n".
			'	return "<span value=\'"+cellValue+"\''. (isset($formatoptions["TCellStyle"])?' class=\''.$formatoptions["TCellStyle"].'\'':'') .'>'. (isset($formatoptions["TCellValue"])?$formatoptions["TCellValue"]:'"+cellValue+"').'</span>";'."\n".                 	
			'}else{'."\n".
			'	return "<span value=\'"+cellValue+"\''. (isset($formatoptions["FCellStyle"])?' class=\''.$formatoptions["FCellStyle"].'\'':'') .'>'. (isset($formatoptions["FCellValue"])?$formatoptions["FCellValue"]:'"+cellValue+"').'</span>";'."\n".                 
			'}'."\n";                			
			
		$sFormatter = sprintf($sFormatter,$sVal);
		$sUnformatter = sprintf($sUnformatter,'var obj = jQuery(rowObject).html(); return jQuery(obj).attr("value");');
		$this->col_custom .= $sFormatter . "\n" . $sUnformatter;                            
		$this->col_formats[$col_name]['custom'] = $formatoptions;                          

		return $this;			
	}
  
    // Desc : formats a cell or row based on the specified condition  
    public function set_conditional_format($col_name, $type, $formatoptions=array()){        
        if($type =="ROW") {
            $this->jq_rowConditions[] = array("col"=>$col_name,"options"=>$formatoptions);
        }
        else if ($type == "CELL"){
            $this->jq_cellConditions[] = array("col"=>$col_name,"options"=>$formatoptions);            
        }                

		return $this;			
    }
    
    // Desc: set grid height and width, the default height is 100%
    public function set_dimension($w, $h='100%', $shrinkToFit = true){
        $this->jq_width=$w;
        $this->jq_height=$h;    
		$this->jq_shrinkToFit = $shrinkToFit;

		return $this;			
    }
    
    // Desc: enable resizable grid(through jquery UI. Experimental feature)
    public function enable_resize($is_resizable, $min_w=350, $min_h=80){
        $this->jqu_resize["is_resizable"]   = $is_resizable; 
        $this->jqu_resize["min_width"]      = $min_w; 
        $this->jqu_resize["min_height"]     = $min_h;    

		return $this;			
    }

    // Desc: master detail. This is different from subgrid 
    // Modification - 01.26.2011 yuuki
    // added parameter : $gdNo -> Grid Detail Number to have a unique identity for each detail grid 
    public function set_masterdetail($obj_grid, $fkey){
        $gdNo = count( $this->obj_md)+1;
        
        if($obj_grid instanceof C_DataGrid){                 
            $obj_grid->set_jq_gridName($this->jq_gridName .'_d'.$gdNo);
            $obj_grid->set_jq_pagerName(trim($this->jq_pagerName, '"') .'_d'.$gdNo);                          
            $obj_grid->set_jq_url(ABS_PATH .'/masterdetail.php?gn='. $obj_grid->jq_gridName .'&id=');
            $obj_grid->set_jq_editurl(ABS_PATH .'/edit.php?gn='. $obj_grid->jq_gridName .'&src=md');
            $obj_grid->set_sql_fkey($fkey);
            $obj_grid->enable_search(false);       
            $obj_grid->prepare_grid();             
            
            $this->obj_md[] = $obj_grid;                          
        }else{
            echo 'Invalid master/detail object. Error 102.';
        }        

		return $this;			
    }
     
    // Desc: use a grid as subgrid. Must pass the foreign key as second parameter
    // *** Note ***
    // It's very important to call prepara_grid() method first before make grid as a subgrid
    // Though it's 'possible, but editing is not supported in subgrid. 
    public function set_subgrid($obj_grid, $d_fkey, $m_fkey=-1){        
        if($obj_grid instanceof C_DataGrid){        
			$m_fkey = ($m_fkey==-1)?$d_fkey:$m_fkey;	// m_fkey default value is the same as d_fkey                        
            $this->jq_gridview = false;     // MUST disable load all data at once (slower)
            $obj_grid->set_jq_url(ABS_PATH .'/subgrid.php?gn='. $this->jq_gridName .'&sgn='. $obj_grid->get_jq_gridName() .'&m_fkey='. $m_fkey .'&id=');
            $obj_grid->set_sql_fkey($d_fkey);
            $obj_grid->set_caption('');  // remove caption
            $obj_grid->prepare_grid();             
            
            $this->obj_subgrid = $obj_grid;                          
        }else{
            echo 'Invalid subgrid object.';
        }

		return $this;			
    }

    // Desc: set pager name. 
    // *** Note *** 
    // The 2nd parameter adds quote around the pager name
    // It should set to false when called by subgrid, which is a dynamic value using javascript
    public function set_jq_pagerName($pagerName, $add_quote=true){
        $this->jq_pagerName = ($add_quote)?('"'.$pagerName.'"'):$pagerName;    

		return $this;			
    }
         
    // Desc: set grid name
    public function set_jq_gridName($gridName){
        $this->jq_gridName = $gridName;
        $this->jq_pagerName = '"#'. $gridName .'_pager1"';  // Notice the double quote;
        $this->jq_url = '"'. ABS_PATH .'/data.php?gn='.$gridName .'"'; 
        $this->export_url = ABS_PATH .'/export.php?gn='. $this->jq_gridName;  

		return $this;			
    }

    // Desc: get grid name
    public function get_jq_gridName(){
        return $this->jq_gridName;    
    }   
     
    // Desc: set sort name
   public function set_sortname($sortname,$sortorder = 'ASC'){
        $this->jq_sortname = $sortname;    
        $this->jq_sortorder = $sortorder;    

		return $this;			
    }  
 
    public function enable_export($type='EXCEL'){
        $this->export_type = $type;
    
		return $this;			
	}
                                                                              
    // Desc: set control used during editing
    // ### Note: The function can probably be improved using the cls_control.php later ###s
    // dataUrl is only valid when type equal to 'select'
    // multiple indicates whether it's a multi-value data
    // Modification: 
    // 02.01.2011 yuuki: Check if the key-value pair parameter is an sql statement
    public function set_col_edittype($col_name, $ctrl_type, $keyvalue_pair=null, $multiple=false, $dataUrl=null,$extra_params=null){               
        if($ctrl_type == "select") {
			$select_list = '';            
            $regex = "/SELECT (.*?) FROM /i";         
            $data ="";
			$matches = array();
            if (preg_match($regex , $keyvalue_pair, $matches))               
            {        
				$select_kv = explode(",",$matches[1]);
				$select_kv = array_map('trim', $select_kv);                        
                $result = $this->db->select_limit_array($keyvalue_pair,-1,0);            
                /*for($i=0;$i<count($result);$i++){
                    if(count($result[$i])==2) {
                        $key   = $i;
                        $value = $result[$i][0];
                    }
                    else if (count($result[$i])> 2) {
                        $key = $result[$i][$select_kv[0]];                       
                             
                        for ($j=0;$j < count($result[$i]);$j++) {
                            if( $key!= $result[$i][$j]) {
                                $value = $result[$i][$j];
                                break;
                            }
                        }
                    }
                    $data = $data.$key.":".str_replace(array('"','[',']',':'), array(' ',' ',' ',' '), $value) .";";
                }            
                $keyvalue_pair = substr($data,0,strlen($data)-1);
				*/

				//  new implementation - will work with string key type (S�bastien BARRIS <sebastien.barris@intelcia.com>)
				$col1 = '';
                $col2 = '';
                foreach($select_kv as $i=>$val)
				{ 
					if ($i==0) $col1=$val; 
					if ($i==1) $col2=$val; 
				} 
				foreach($result as $i=>$val) 
				{
					$select_list.=$result[$i][$col1].":".$result[$i][$col2].";"; 
				} 
				$select_list=rtrim($select_list,";"); 
				$keyvalue_pair = $select_list; 
            }
        }
               
        $this->col_edittypes[$col_name]['type']         = $ctrl_type;
        $this->col_edittypes[$col_name]['value']        = $keyvalue_pair;
        $this->col_edittypes[$col_name]['multiple']     = $multiple;       
        $this->col_edittypes[$col_name]['dataUrl']      = $dataUrl;
        $this->col_edittypes[$col_name]['extra_params'] = $extra_params;
    
		return $this;			
	}

    // Desc: overwrite color properties of jQuery UI: ui-state-hover, ui-state-highlight
    // Alternatively, user can directly declare style in HTML to overwrite. If done so, additional css properties other
    // than background color can be used in CSS class. For example:
    /* <style>
        #list1 .ui-state-hover{background:blue;[other properties]}
        #list1 .ui-state-highlight{background:red;[other properties]}
        #list1 .ui-priority-secondary{background:yellow;[other properties]}    
       </style>
    */ 
    public function set_row_color($hover_color, $highlight_color=null, $altrow_color=null){
        $this->alt_colors['hover'] = $hover_color;
        $this->alt_colors['highlight'] = $highlight_color;
        $this->alt_colors['altrow'] = $altrow_color;                                     

		return $this;			
    }
    public function set_conditional_row_color($colName, $condition=array(),$default=""){
        $this->jq_conditionalRows[] = array("col"=>$colName,"default"=>$default,"condition"=>$condition);

		return $this;			
    }
    
    public function set_conditional_cell_color($colName, $condition=array(),$default=""){
        $this->jq_conditionalRows[] = array("col"=>$colName,"default"=>$default,"condition"=>$condition);                                             

		return $this;			
    }
    
    
    // Desc: overwrite color properties of jQuery UI: ui-state-hover, ui-state-highlight, ui-priority-secondary 
    // Alternatively, user can directly declare style in HTML to overwrite. If done so, additional css properties other
    // than background color can be used in CSS class. For example:
    /* <style>
        #list1 .ui-state-hover{background:blue;[other properties]}
        #list1 .ui-state-highlight{background:red;[other properties]}
        #list1 .ui-priority-secondary{background:yellow;[other properties]}        
       </style>
    */ 

    // Desc: set jQuery theme
    public function set_theme($theme){
        $this->theme_name = $theme;

		return $this;			
    }

    // Desc: set locale
    public function set_locale($locale){
        $this->locale = $locale;

		return $this;			
    }
    
    // Desc: enable debug
    public function enable_debug($debug){
        $this->debug = $debug;
        $this->db->db->debug = $debug;

		return $this;			
    }
    
    // Desc: set caption text
    public function set_caption($caption){
        $this->jq_caption = $caption;

		return $this;			
    }
    
    // Desc: set page size
    // Note: pagination is disabled when set_scroll is set to true. 
    // The grid height is set in the 2nd param of set_scroll(). See method for more info
    public function set_pagesize($pagesize){
        $this->jq_rowNum = $pagesize;

		return $this;			
    }
    
    // Desc: boolean whether display sequence number to each row
    public function enable_rownumbers($has_rownumbers){
        $this->jq_rownumbers = $has_rownumbers;    

		return $this;			
    }

	// set coulmn width
	 public function set_col_width($col_name, $width){
        $this->col_widths[$col_name]['width'] = $width;        

		return $this;			
    }
    // get coulmn width
    public function get_col_width(){
        return $this->col_widths;
    }

    // set coulmn width
     public function set_col_align($col_name, $align="left"){
        $this->col_aligns[$col_name]['align'] = $align;        

		return $this;			
    }
    // get coulmn width
    public function get_col_align(){
        return $this->col_aligns;
    }
    
	public function set_group_properties($feildname, $groupCollapsed=false, $showSummaryOnHide=true){
        $this->jq_grouping=true;
        $this->jq_is_group_fild_hidden =true;    
        $this->jq_group_name=$feildname;
        $this->jq_groupcollapse=$groupCollapsed;
        $this->jq_showSummaryOnHide=$showSummaryOnHide;

		return $this;			
	}

	public function set_group_summary($col_name, $summaryType){
        $this->jq_is_group_summary=true;    
		$this->jq_summary_col_name[$col_name]['summaryType'] = $summaryType;    

		return $this;			
	}         
	
	// Beta. only work with a single datagrid in read only mode, or FORM edit mode.
	public function enable_kb_nav($is_enabled = false){
		$this->kb_nav = $is_enabled;
		
		return $this;			
	}     

	public function setCallbackString ($string) {
		$this->callbackstring = '&__cbstr='.strtr(rtrim(base64_encode($string), '='), '+/', '-_');
		$this->jq_url = substr($this->jq_url,0,-1).$this->callbackstring.'"';
		$this->export_url .= $this->callbackstring;
		
		return $this;			
	}

	// jq_autowidth is set to false by default, use this method to enable, the default width is 800
    public function enable_autowidth($autowidth=true){
		$this->jq_autowidth = $autowidth;

		return $this;
    }

	// return the grid script include and body. It can be useful for MVC framework integration such as Drupal.
	public function get_display($add_script_includeonce=true){
		if($add_script_includeonce){
			return $this->script_includeonce . $this->script_body;
		}else{
			return $this->script_body;
		}
	}

	// set form dimension
	public function set_form_dimension($f_width, $f_height = '100%'){
		$this->form_width = $f_width;
		$this->form_height = $f_height;

		return $this;
	}

	// set column wysiwyg. Must be a textarea
	public function set_col_wysiwyg($col_name, $extra_param = "xxx"){
		$this->col_wysiwyg[$col_name] = $extra_param;

		return $this;
	}

	// set default column default value. This option is valid only in adding new record in Form Editing
	public function set_col_default($col_name, $default = ""){
		$this->col_default[$col_name] = $default;

		return $this;
	}

	// set column frozen
	public function set_col_frozen($col_name, $value=true){
		$this->col_frozen[$col_name] = $value;		// doesn't really need a value

		return $this;
	}
	
	// advanced function
	// set event. new event model in jqgrid 4.3.2 will not overwrite previous handler of the same event
	public function add_event($event_name, $js_event_handler){
		$this->script_ude_handler .= '$("#'.$this->jq_gridName.'").bind("'. $event_name .'", '. $js_event_handler .');' ."\n";

		return $this;
	}

	// advanced funciton
	// set colModel property manually. Use this method when there's no exposed methods for some column properties.e.g. size
	public function set_col_property($col_name, $property = array()){
		$this->ud_col_properties[$col_name] = $property;
	
		return $this;
	}


	public function set_grid_property($property = array()){

	}
	// set column edit size, form edit only, workings with text type only
	// for column type text, the width is translated to "size", height is ignored
	// for text area, the width is translated to "cols" and height to "rows"
	public function set_col_edit_dimension($col_name, $width=30, $height=6){
		$this->col_edit_dimension[$col_name]["width"] = $width;
		$this->col_edit_dimension[$col_name]["height"] = $height;

		return $this;
	}

	// Note only a single fileupload per form is allow in v5
	// physical_path is automatically obtained if PHP running in Apache module, or MUST be provided as fallback
	public function set_col_fileupload($col_name, $base_url = "", $physical_path=""){
		$this->col_fileupload['col_name'] = $col_name;	// probably redundant
		$this->col_fileupload['base_url'] = $base_url;
		$this->col_fileupload['physical_path'] = $physical_path;
		$this->col_edittypes[$col_name]['type'] = 'file';
		$this->col_fileupload['editoptions'] = array("enctype" => "multipart/form-data");

		return $this;
	}

	// roughly equivalent of ASP Server.MapPath wtih fallback because apache_looK_url only works in Apache mode
	private function MapPath($file){
		if(function_exists('apache_lookup_uri')){
			$alu=apache_lookup_uri($file);
			return $alu->filename;
		}
		return $this->col_fileupload['physical_path'];
	} 
	
	// create virtual column
	public function add_column($col_name, $property = array(), $title='', $after = ''){
		$this->col_virtual[$col_name]['property'] = $property;
		$this->col_virtual[$col_name]['title'] = ($title == '') ? $col_name : $title;
		$this->col_virtual[$col_name]['after'] = $after;

		return $this;
	}

	// custom validation
	public function set_col_customrule($col_name, $customrule_func){
		$this->col_customrule[$col_name]['custom'] = true;
		$this->col_customrule[$col_name]['custom_func'] = $customrule_func;

		return $this;
	}

}
?>
