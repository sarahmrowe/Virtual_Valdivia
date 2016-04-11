<?php

//require_once '/usr/share/php/PHPUnit/Extensions/Database/TestCase.php';
//require_once '/usr/share/php/PHPUnit/Extensions/Database/DataSet/FlatXmlDataSet.php';
//
//require_once '../controls/multiListControl.php';
//require_once '../includes/required.php';


class MultiListControlDBTestMySQL extends PHPUnit_Extensions_Database_TestCase
{
	public $unitDB;
	protected $my_control3 = null;
	protected $my_control4 = null;
	protected $my_control5 = null;
	protected $table = 'p99Data';
	protected $cName = 'p99c1';
   
    
	public function __construct()
    {   
		global $dbuser;
		global $dbpass;
		global $dbname;
		global $dbhost;

    	$this->unitDB = PHPUnit_Util_PDO::factory('mysql://'.$dbuser.':'.$dbpass.'@'.$dbhost.'/'.$dbname);
    	
    	if (!table_exists ($this->table, $this->unitDB))
    	{
			createTable($this->unitDB, $this->table);
    	}
    	else
    	{
    		truncateTable($this->unitDB, $this->table);
    	}
    	
    	$this->my_control3 = new MultiListControl(99, 1, "11-38-3", 4);
		$this->my_control4 = new MultiListControl(99, 1, "11-38-4", 4);
		$this->my_control5 = new MultiListControl(99, 1, "11-38-5", 4);
    }
    
	protected function tearDown()
	{
		unset($_REQUEST[$this->cName]);
	}
	   
    protected function getConnection()
    {
        return $this->createDefaultDBConnection($this->unitDB, 'mysql');
    }
    
    protected function getDataSet()
    {
        return $this->createFlatXMLDataSet('p99Data.xml');
    }
    
    public function testInitializeAndDeleteSomeListControls()
    {
    	reconnectToDatabase();
    	
		$_REQUEST[$this->cName] = array('QuickTime_hi');
		
    	$this->my_control3->ingest();
    	$this->my_control4->ingest();
    	$this->my_control4 = new MultiListControl(99, 1, "11-38-4", 4);
    	$this->my_control4->ingest();
    	$this->my_control5->ingest();
    	
    	$expectedData = $this->createFlatXMLDataSet('p99Test01.xml');
    	$this->assertTablesEqual($expectedData->getTable($this->table), $this->getConnection()->createDataSet()->getTable($this->table));

    	$this->my_control3->delete();
    	$this->my_control5->delete();

    	$expectedData = $this->createFlatXMLDataSet('p99Test01_afterdelete.xml');
    	$this->assertTablesEqual($expectedData->getTable($this->table), $this->getConnection()->createDataSet()->getTable($this->table));
    	
    }

}



?>