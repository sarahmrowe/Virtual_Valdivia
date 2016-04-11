<?php

require_once '/usr/share/php/PHPUnit/Extensions/Database/TestCase.php';
require_once '/usr/share/php/PHPUnit/Extensions/Database/DataSet/FlatXmlDataSet.php';

require_once '../controls/listControl.php';
//require_once 'required_database.php';

class ListControlDBTestMySQL extends PHPUnit_Extensions_Database_TestCase
{
	public $db;
	protected $my_control = null;
	
	public function __construct()
    {
//    	global $db;
    	
        $this->db = PHPUnit_Util_PDO::factory(
		'mysql://maurice:5racHuga@rush.matrix.msu.edu/maurice_dev'
		);
		
		
		
//	    if (!$this->table_exists($this->table_name, $this->db))
//	    {
//			$this->createTable($this->db);
//		}
		
		
    }
    
//    public function tearDown()
//    {
//    	$query = "DROP TABLE post";
//    	$this->db->query($query);
//    }
    
    protected function getConnection()
    {
        return $this->createDefaultDBConnection($this->db, 'mysql');
    }

    

    
    protected function getDataSet()
    {
//    	echo "This is it: ".dirname(__FILE__);
        return $this->createFlatXMLDataSet('p17Data.xml');
    }
    
    public function testInitializeAssociatorControl()
    {
    	$this->my_control = new ListControl(99, 5, "11-38-3", 4);
    	$this->my_control->ingest();
    }
    
//    public function testDeleteTable()
//    {
//    	
//    }

    
    
    
    
    
    
    
    
    protected function createTable(PDO $db)
    {
//    	$query = "
//            CREATE TABLE p99Control (
//                cid INT(10) UNSIGNED PRIMARY KEY,
//                schemeid INT(10) UNSIGNED,
//                collid INT(10) UNSIGNED,
//                type VARCHAR(30),
//                name VARCHAR(255),
//                description VARCHAR(255),
//                required TINYINT(1),
//                searchable TINYINT(1),
//                advSearchable TINYINT(1),
//                showInResults TINYINT(1),
//                showInPublicResults TINYINT(1),
//                publicEntry TINYINT(1),
//                options LONGTEXT,
//                sequence INT(10) UNSIGNED
//            );
//        ";
    	
    	$query2 = "
            CREATE TABLE p99Data (
            	id VARCHAR(30) PRIMARY KEY,
                cid INT(10) UNSIGNED,
                schemeid INT(10) UNSIGNED,
                value LONGTEXT
            );
        ";

//        $this->db->query($query);
        $this->db->query($query2);
    }
    
    
//	protected function table_exists($table, $db)
//	{ 
//		$tables = mysql_list_tables ($db); 
//		while (list ($temp) = mysql_fetch_array ($tables))
//		{
//			if ($temp == $table)
//			{
//				return TRUE;
//			}
//		}
//		
//		return FALSE;
//	}
    
}



?>