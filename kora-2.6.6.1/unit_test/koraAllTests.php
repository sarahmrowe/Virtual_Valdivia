<?php

include_once('header.php');

/*
 * Include all test suites
 */
include_once('koraUnitTestDatabase.php');
include_once('koraSearchUnitTest.php');
include_once('koraControlsFunctionsTests.php');

class KoraAllTests extends PHPUnit_Framework_TestSuite
{
        public static function suite()
        {
                $suite = new PHPUnit_Framework_TestSuite('KoraTests');

                $suite->addTestSuite('DatabaseControlsTests');
                $suite->addTestSuite('KoraSearchUnitTestSuite');
		$suite->addTestSuite('KoraControlsFunctionsTests');

                return $suite;
        }
}
?>

