<?php
include_once('/usr/share/php/PHPUnit/Framework.php');
include_once ('../includes/clientUtilities.php');
include_once ('../includes/conf.php');



class ClientUtilitiesTestDatabase extends PHPUnit_Extensions_Database_TestCase
{
	public $cid = null;
	public $rid = null;
	public $db;
	
	public function setUp()
    {  	
        $this->db = PHPUnit_Util_PDO::factory('mysql://maurice:5racHuga@rush.matrix.msu.edu/maurice_dev');
		
//		$this->createTable($this->db);
    }
    
    public function tearDown()
    {
    	$this->db = null;
    }
    
    protected function getConnection()
    {
        return $this->createDefaultDBConnection($this->db, 'mysql');
    }
    
    protected function getDataSet()
    {
        return $this->createFlatXMLDataSet('p35Data.xml');
    }

	/**
	 * @dataProvider myRecordID03
	 */
	public function testGetURLFromRecordID()
	{
		reconnectToDatabase();
		$this->rid = '23-73-E2';
		$this->cid = 5;
		$this->assertEquals(baseURI.'files/35/115/', getURLFromRecordID($this->rid, $this->cid));
		
		$this->rid = '23-43-23';
		$this->cid = 5;
		$this->assertEquals(baseURI.'files/35/67/', getURLFromRecordID($this->rid, $this->cid));
		
		$this->rid = '23-12-12';
		$this->cid = 5;
		$this->assertEquals(baseURI.'files/35/18/', getURLFromRecordID($this->rid, $this->cid));
		

	}

    
}


?>