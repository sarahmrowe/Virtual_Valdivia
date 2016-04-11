<?php

include_once('header.php');

/*
 * Class tests functions inside each control's class.
 *
 */

class KoraControlsFunctionsTests extends PHPUnit_Framework_TestSuite
{
        public static function suite()
        {
                $suite = new PHPUnit_Framework_TestSuite('Controls');

//		$suite->addTestSuite('AssociatorControlsTest');
		$suite->addTestSuite('DateControlsTest');
//		$suite->addTestSuite('FileControlsTest');
//		$suite->addTestSuite('ImageControlsTest');
		$suite->addTestSuite('TextControlsTest');
		$suite->addTestSuite('GeolocatorControlsTest');
		$suite->addTestSuite('ListControlsTest');
		$suite->addTestSuite('MultiDateControlsTest');
		$suite->addTestSuite('MultiListControlsTest');
		$suite->addTestSuite('MultiTextControlsTest');

                return $suite;
        }
}

?>
