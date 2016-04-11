<?php

/*
 * KoraSearchUnitTestSuite
 * Test suite that inculdes tests:
 * KORA_Search(),
 * KORA_Clause class,
 * joinKORAClause function(),
 * ClientUtilities functions.
 */

include_once('header.php');

class KoraSearchUnitTestSuite extends PHPUnit_Framework_TestSuite
{
	public static function suite()
	{
		$suite = new PHPUnit_Framework_TestSuite('KoraSearch');

		$suite->addTestSuite('KoraSearchTest');
		$suite->addTestSuite('KoraClauseTest');

		$suite->addTestSuite('JoinKoraClauseTest');

		$suite->addTestSuite('ClientUtilitiesTest');
//		$suite->addTestSuite('ClientUtilitiesTestDatabase');

		return $suite;
	}
}
?>
