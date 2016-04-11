<?php

class JoinKoraClauseTest extends PHPUnit_Framework_TestCase
{
	 public function testJoinKoraClause()
        {
                $queryClause = new KORA_Clause('KID', 'LIKE', '12-39-1');
                $queryClause2 = new KORA_Clause('KID', '!=', '12-39-1');
                $queryClause3 = new KORA_Clause('KID', '!=', '12-38-1');
                $clausesArray = array($queryClause,$queryClause2,$queryClause3);
                $emptyClause = array();
                $clausesArrayFalse = array($queryClause, 'stringfail');

                //testing second argument, should return true only if second argument is 'OR' or 'A$
                $this->assertFalse(joinKORAClauses($clausesArray, 'LIKE'));

                //testing first arugment, should return false since clause is empty
                $this->assertFalse(joinKORAClauses($emptyClause, 'OR'));

                //testing first argument, should return false since first argument is not an array
                $this->assertFalse(joinKORAClauses('string', 'OR'));

                //assert that after joinKORAClauses executes, it returns KORA_Clause type
                $this->assertType('KORA_Clause', joinKORAClauses($clausesArrayFalse, 'OR'));
                $this->assertType('KORA_Clause', joinKORAClauses($clausesArray, 'AND'));
                $this->assertType('KORA_Clause', joinKORAClauses($clausesArray, 'OR'));
        }
}

?>
