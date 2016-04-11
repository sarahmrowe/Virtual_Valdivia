<?php

require_once '/usr/share/php/PHPUnit/Extensions/Database/TestCase.php';
require_once '/usr/share/php/PHPUnit/Extensions/Database/DataSet/FlatXmlDataSet.php';

require_once '../controls/multiDateControl.php';
require_once '../includes/required.php';


class MultiDateControlDBTestMySQL extends PHPUnit_Extensions_Database_TestCase
{
	public $unitDB;
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
    	
    	$this->my_control3 = new MultiDateControl(99, 1, "11-38-3", 4);
		$this->my_control4 = new MultiDateControl(99, 1, "11-38-4", 4);
		$this->my_control5 = new MultiDateControl(99, 1, "11-38-5", 4);
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
    	
		$_REQUEST[$this->cName] = 'TestImporter-a0a0a0-a';
    	
    	
    	$this->my_control3->ingest();
    	$this->my_control4->ingest();
    	$this->my_control4 = new MultiDateControl(99, 1, "11-38-4", 4);
    	$this->my_control4->ingest();
    	$this->my_control5->ingest();
    	
    	$expectedData = $this->createFlatXMLDataSet('p17Data.xml');
    	$this->assertTablesEqual($expectedData->getTable($this->table), $this->getConnection()->createDataSet()->getTable($this->table));

    	$this->my_control3->delete();
    	$this->my_control5->delete();

    	$expectedData = $this->createFlatXMLDataSet('p17Data_afterdelete.xml');
    	$this->assertTablesEqual($expectedData->getTable($this->table), $this->getConnection()->createDataSet()->getTable($this->table));
    	
    }

    

    
/*
 * $query truncates the test table
 */
    protected function truncateTable(PDO $unitDB)
    {
    	$query = "TRUNCATE p99Data";
    	$this->unitDB->query($query);
    }
    
/*
 * Drop table
 */
    
    protected function dropTable(PDO $unitDB, $table)
    {
    	$query = "DROP TABLE $table";
    	$this->unitDB->query($query);
    }
    
/*
 * $query creates the table needed to unit test
 */
    protected function createTable(PDO $unitDB, $table)
    {    	
    	$query = "
            CREATE TABLE $table (
            	id VARCHAR(30) PRIMARY KEY,
                cid INT(10) UNSIGNED,
                schemeid INT(10) UNSIGNED,
                value LONGTEXT
            );
        ";

        $this->unitDB->query($query);
    }
   
    
/*
 * check to see if table exists
 */
	protected function table_exists ($table, $unitDB)
	{
		$query = "SELECT * FROM $table";
		
		$results = $unitDB->query($query);
		
		if (empty($results))
		{
			return false;
		}
		else
		{
			return true;
		}
	}
    
    
    

    
}



?>