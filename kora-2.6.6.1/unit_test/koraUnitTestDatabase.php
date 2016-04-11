<?php
/*
 * DatabaseControlsTests class
 * Tests database manipulations for each controls
 * (e.g. ingesting and deleting records)
 */

include_once('header.php');


class DatabaseControlsTests extends PHPUnit_Framework_TestSuite
{
	public static function suite()
	{
		$suite = new PHPUnit_Framework_TestSuite('ControlDatabase');

//		Trouble accessing ingest()

//		$suite->addTestSuite('multiTextControlDBTestMySQL');
//		$suite->addTestSuite('ImageControlDBTestMYSQL');
//		$suite->addTestSuite('MultiDateControlDBTestMYSQL');
//		$suite->addTestSuite('MultiListControlDBTestMYSQL');
//		$suite->addTestSuite('DateControlDBTestMYSQL');

		$suite->addTestSuite('TextControlDBTestMYSQL');
		$suite->addTestSuite('ListControlDBTestMYSQL');

//		Unknown

//		$suite->addTestSuite('GeolocatorControlDBTestMYSQL');
//		$suite->addTestSuite('FileControlDBTestMYSQL');
//              $suite->addTestSuite('AssociatorControlDBTestMySQL');

		return $suite;
	}


}
?>
