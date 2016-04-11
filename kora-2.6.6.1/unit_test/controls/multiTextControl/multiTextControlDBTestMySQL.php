<?php

require_once '/usr/share/php/PHPUnit/Extensions/Database/TestCase.php';
require_once '/usr/share/php/PHPUnit/Extensions/Database/DataSet/FlatXmlDataSet.php';

require_once '../controls/multiTextControl.php';

class MultiTextControlDBTestMySQL extends PHPUnit_Extensions_Database_TestCase
{
	public $db;
	protected $my_control3 = null;
	protected $my_control4 = null;
	protected $my_control5 = null;
	
	public function __construct()
    {   	
    	$this->unitDB = PHPUnit_Util_PDO::factory('mysql://'.$dbuser.':'.$dbpass.'@'.$dbhost.'/'.$dbname);
    	
    	if (!table_exists($this->table, $this->unitDB))
    	{
			createTable($this->unitDB, $this->table);
    	}
    	else
    	{
    		truncateTable($this->unitDB, $this->table);
    	}
    	
    	$this->my_control3 = new MultiTextControl(99, 1, "11-38-3", 4);
		$this->my_control4 = new MultiTextControl(99, 1, "11-38-4", 4);
		$this->my_control5 = new MultiTextControl(99, 1, "11-38-5", 4);  
//		$this->createTable($this->db);
    }
    
    protected function getConnection()
    {
        return $this->createDefaultDBConnection($this->db, 'mysql');
    }
    
    protected function getDataSet()
    {
        return $this->createFlatXMLDataSet('p99Data.xml');
    }
    
    public function testInitializeAndDeleteSomeMultiTextControls()
    {		
    	$this->my_control3->ingest();
    	$this->my_control4->ingest();
    	$this->my_control4 = new MultiTextControl(99, 1, "11-38-4", 4);
    	$this->my_control4->ingest();
    	$this->my_control5->ingest();
    	
    	$expectedData = $this->createFlatXMLDataSet('p99Test01.xml');
    	$this->assertTablesEqual($expectedData->getTable('p99Data'), $this->getConnection()->createDataSet()->getTable('p99Data'));

    	$this->my_control3->delete();
    	$this->my_control5->delete();

    	$expectedData = $this->createFlatXMLDataSet('p99Test01_afterdelete.xml');
    	$this->assertTablesEqual($expectedData->getTable('p99Data'), $this->getConnection()->createDataSet()->getTable('p99Data'));
    }
}

?>