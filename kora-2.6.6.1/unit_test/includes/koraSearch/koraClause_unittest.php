<?php

class KoraClauseTest extends PHPUnit_Framework_TestCase
{

	public $projID = 18;
	public $schemeID = 57;

	//'Summary' field not in fields to return
	public $fieldsToReturn = array ('location', 'interviewdate', 'interviewer', 'interviewee', 'transcript', 'title', 'description', 'Summary');
	public $fieldsToReturnEmpty = array('fail control');//array('location');//array();
	public $myConn = null;


	public function __construct()
    	{
    		/*
    		 * Declare queryClause for KORA_Search function
    		 */
    		$this->logicalQueryClause1 = new KORA_Clause('KID', '!=', '12-39-1');
    		$this->logicalQueryClause2 = new KORA_Clause('KID', '=', '12-39-1');
    		$this->booleanORqueryClause3 = new KORA_Clause($this->logicalQueryClause2, 'OR', $this->logicalQueryClause1);
    		$this->queryClause4 = new KORA_Clause(array(), 'LIKE', '12-39-1');
    		$this->queryClause5 = new KORA_Clause('KID', '=', array());
    		$this->queryClause6 = new KORA_Clause('location', '=', 'Detroit, MI');
    		$this->booleanANDqueryClause4 = new KORA_Clause($this->logicalQueryClause2, 'AND', $this->queryClause6 );
			$this->queryClause7 = new KORA_Clause('location', '!=', 'Detroit, MI');
    		$this->queryClause8 = new KORA_Clause($this->logicalQueryClause2, 'AND', $this->queryClause7 );
    		$this->queryClause9 = new KORA_Clause('KID', 'IN', array('12-39-1', '12-39-2', '12-39-3'));

	    	$this->hardCodeDictionary = array('location' => array('cid' => 2, 'xmlPacked' =>0));

    	}

	public function testQueryResultFunctionResultsForLogicalQueryClause1()
	{
		reconnectToDatabase();
		$expectedResults1 = array("'12-39-2'", "'12-39-3'", "'12-39-4'", "'12-39-5'", "'12-39-6'", "'12-39-7'");
/*
 * Test queryResults when the Clause Type is Logical
 */
		$queryResults1 = $this->logicalQueryClause1->queryResult(array(), $this->projID, $this->schemeID);
		$this->assertEquals($queryResults1, $expectedResults1);
	}

	public function testQueryResultFunctionResultsForLogicalQueryClause2()
	{
		reconnectToDatabase();
		$expectedResults2 = array("'12-39-1'");
/*
 * Test queryResults when the Clause Type is Logical
 */
		$queryResults2 = $this->logicalQueryClause2->queryResult(array(), $this->projID, $this->schemeID);
		$this->assertEquals($queryResults2, $expectedResults2);
	}

	public function testQueryResultFunctionResultsForBooleanORqueryClause3()
	{
		reconnectToDatabase();
		$expectedResults3 = array("'12-39-1'", "'12-39-2'", "'12-39-3'", "'12-39-4'", "'12-39-5'", "'12-39-6'", "'12-39-7'");
/*
 * Test queryResults when the Clause Type is Boolean OR.
 */
		$queryResults3 = $this->booleanORqueryClause3->queryResult(array(), $this->projID, $this->schemeID);
		$this->assertEquals($queryResults3, $expectedResults3);
	}

	public function testQueryResultFunctionResultsForBooleanANDqueryClause4()
	{
		reconnectToDatabase();
/*
 * Test queryResults when the Clause Type is Boolean AND.
 */
		/*
		 * Test that only records with the following elements will be selected.
		 * KID: =  12-39-1
		 * location = 'Detroit, MI'
		 */
		$expectedResults4 = array("'12-39-1'");
		$queryResults4 = $this->booleanANDqueryClause4->queryResult($this->hardCodeDictionary, $this->projID, $this->schemeID);
		$this->assertEquals($queryResults4, $expectedResults4);
	}

	public function testQueryResultFunctionResultsForINClause5()
	{
		reconnectToDatabase();
		$expectedResults5 = array(0 =>"'12-39-1'", 1=> "'12-39-2'", 2=>"'12-39-3'");

/*
 * Test queryResults when the Clause Type is LOGICAL IN.
 */
		$queryResults5 = $this->queryClause9->queryResult(array(), $this->projID, $this->schemeID);

		$this->assertEquals($queryResults5, $expectedResults5);

	}

	public function testQueryResults()
	{
		reconnectToDatabase();
		/*
		 * Test that only records with the following elements will be selected.
		 * KID: =  12-39-1
		 * location = 'Detroit, MI'
		 *
		 * Change queryClause 7 and 8 to match the logic and boolean.
		 * 
		 * Since there is no such record. Results should be empty.
		 */

		/*
		 * Test the new queries.
		 */
		$expectedResults4 = array();
		$queryResults4 = $this->queryClause8->queryResult($this->hardCodeDictionary, $this->projID, $this->schemeID);
		$this->assertEquals($queryResults4, $expectedResults4);
	}

/*	public function testXmlFormattedFunction()
	{

	}
*/

}
?>
