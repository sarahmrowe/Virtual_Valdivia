<?php

require_once '/usr/share/php/PHPUnit/Extensions/Database/TestCase.php';
require_once '/usr/share/php/PHPUnit/Extensions/Database/DataSet/FlatXmlDataSet.php';

require_once '../controls/imageControl.php';

class ImageControlDBTestMySQL extends PHPUnit_Extensions_Database_TestCase
{
	public $db;
	protected $my_control3 = null;
	protected $my_control4 = null;
	protected $my_control5 = null;
	protected $table = 'p99Data';
	
	public function __construct()
    {   
    	$this->unitDB = PHPUnit_Util_PDO::factory('mysql://'.$dbuser.':'.$dbpass.'@'.$dbhost.'/'.$dbname);
    	        
    	if (!$this->table_exists ($this->table, $this->unitDB))
    	{
			$this->createTable($this->unitDB, $this->table);
    	}
    	else
    	{
    		$this->truncateTable($this->unitDB, $this->table);
    	}

    	$this->my_control3 = new ImageControl(99, 1, "11-38-3", 4);
		$this->my_control4 = new ImageControl(99, 1, "11-38-4", 4);
		$this->my_control5 = new ImageControl(99, 1, "11-38-5", 4);  
    }
    
    protected function getConnection()
    {
        return $this->createDefaultDBConnection($this->db, 'mysql');
    }
    
    protected function getDataSet()
    {
        return $this->createFlatXMLDataSet('p99Data.xml');
    }
    
    public function testInitializeAndDeleteSomeTextControls()
    {		
    	reconnectToDatabase();
    	
    	$this->my_control3->ingest();
    	$this->my_control4->ingest();
    	$this->my_control4 = new TextControl(99, 1, "11-38-4", 4);
    	$this->my_control4->ingest();
    	$this->my_control5->ingest();
    	
    	$expectedData = $this->createFlatXMLDataSet('p17Data.xml');
    	$this->assertTablesEqual($expectedData->getTable('p99Data'), $this->getConnection()->createDataSet()->getTable('p99Data'));

    	$this->my_control3->delete();
    	$this->my_control5->delete();

    	$expectedData = $this->createFlatXMLDataSet('p17Data_afterdelete.xml');
    	$this->assertTablesEqual($expectedData->getTable('p99Data'), $this->getConnection()->createDataSet()->getTable('p99Data'));
    }
}



?>