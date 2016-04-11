<?php

/*
 * KoraSearchTest class
 * Tests the function KORA_Search().
 */

class KoraSearchTest extends PHPUnit_Framework_TestCase
{
	public $authToken = '8f9e0326732d71133d7da8ff';
	public $projID = 18;
	public $schemeID = 57;

	//'Summary' field not in fields to return
	public $fieldsToReturn = array ('location', 'interviewdate', 'interviewer', 'interviewee', 'transcript', 'title', 'description', 'Summary');
	public $fieldsToReturnEmpty = array('fail control');//array('location');//array();
	public $myConn = null;

	public function testKoraSearchFunctionWorks()
	{
		reconnectToDatabase();

		$queryClause = new KORA_Clause('KID', 'LIKE', '12-39-1');
		$results = KORA_Search($this->authToken, $this->projID, $this->schemeID, $queryClause, $this->fieldsToReturn);

		$this->assertEquals('12-39-1', $results['12-39-1']['kid']);
		$this->assertEquals('Detroit, MI', $results['12-39-1']['location']);
	}

	public function testKoraSearchReturnsNull()
	{
		reconnectToDatabase();
		$queryClause = new KORA_Clause('KID', 'LIKE', '12-39-1');
		$emptyFieldsToReturn = array();
		$falseAuth = 'aaaaaaaaaaaaaa';

		// test that KORA_Search() returns NULL if a non-KORA_Clause object is passed
		$emptyQueryClause = 'string object';

		$results = KORA_Search($this->authToken, $this->projID, $this->schemeID, $emptyQueryClause, $this->fieldsToReturn);

		$this->assertEquals(NULL, $results);

		// test that KORA_Search() returns NULL if fields to return is empty
                $results = KORA_Search($this->authToken, $this->projID, $this->schemeID, $queryClause, $emptyFieldsToReturn);
		$this->assertEquals(NULL, $results);

		//test that KORA_Search() returns NULL if authentication token is invalid
                $results = KORA_Search($falseAuth, $this->projID, $this->schemeID, $queryClause, $this->fieldsToReturn);
		$this->assertEquals(NULL, $results);
	}


	public function testWhenFieldsToReturn()
	{
		reconnectToDatabase();

		//testing incorrect fieldsToReturn data
		$queryClause = new KORA_Clause('KID', '!=', '12-39-1');
		$results = KORA_Search($this->authToken, $this->projID, $this->schemeID, $queryClause, $this->fieldsToReturnEmpty);
		$this->assertEquals(NULL, $results);

		//six elements should return in $results
		$results = KORA_Search($this->authToken, $this->projID, $this->schemeID, $queryClause, $this->fieldsToReturn, array());
		$this->assertEquals(6, count($results));
	}

	public function testLimitStartLimitNo()
	{
		reconnectToDatabase();

		$queryClause = new KORA_Clause('KID', '!=', '12-39-1');

		//testing $limitStart and $limitNo. Should return 3 elements
		$results = KORA_Search($this->authToken, $this->projID, $this->schemeID, $queryClause, $this->fieldsToReturn, array(), 3, 5);
		$this->assertEquals(3, count($results));
		$this->assertEquals('12-39-6', ($results['12-39-6']['kid']));
		$this->assertEquals('18', ($results['12-39-6']['pid']));
	}

	public function testAscendingOrder()
	{
        reconnectToDatabase();

        $queryClause = new KORA_Clause('KID', '!=', '12-39-5');

		$correctOrderASC = array('12-39-1', '12-39-6', '12-39-7', '12-39-2', '12-39-3', '12-39-4');
		$correctOrderDESC = array('12-39-3', '12-39-6', '12-39-7', '12-39-5', '12-39-4', '12-39-2');
		$counter = 0;
		$orderbyArray = array(array('field' => 'title', 'direction' => SORT_ASC));
		//$this->fieldsToReturn3 = array('title');
		
		//test orderby
		$results = KORA_Search($this->authToken, $this->projID, $this->schemeID, $queryClause, $this->fieldsToReturn, $orderbyArray);

		//assert that $correctOrder and $results match. This tests the orderby part of the search
		foreach ($results as $element)
		{
			$this->assertEquals($element['kid'], $correctOrderASC[$counter]);
			$counter++;
		}
	}

	public function testDescendingOrder()
	{
        reconnectToDatabase();

        $queryClause = new KORA_Clause('KID', '!=', '12-39-5');
        $correctOrderDESC = array('12-39-4', '12-39-3', '12-39-2', '12-39-7', '12-39-6', '12-39-1');
		$orderbyArray2 = array(array('field' => 'title', 'direction' => SORT_DESC));
		$counter2 = 0;
		//$this->fieldsToReturn = array('title');
		
		//test orderby
		$results = KORA_Search($this->authToken, $this->projID, $this->schemeID, $queryClause, $this->fieldsToReturn, $orderbyArray2);

		//assert that $correctOrderDESC and $results match.
		foreach ($results as $element)
		{
			$this->assertEquals($element['kid'], $correctOrderDESC[$counter2]);
			$counter2++;
		}
	}
	
	public function testDifferentField()
	{
        reconnectToDatabase();
		
		$queryClause = new KORA_Clause('location', '=', 'Detroit, MI');
		$results = KORA_Search($this->authToken, $this->projID, $this->schemeID, $queryClause, $this->fieldsToReturn);

		foreach ($results as $element)
		{
			$this->assertEquals($element['location'], 'Detroit, MI');
		}
	}


}

?>
