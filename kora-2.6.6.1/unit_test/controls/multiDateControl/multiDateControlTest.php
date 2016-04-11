<?php

include_once('/usr/share/php/PHPUnit/Framework.php');
include_once ('../controls/multiDateControl.php');

class MultiDateControlsTest extends PHPUnit_Framework_TestCase
{
	public $my_control = null;
	public $differet_control = null;
	
	
	protected function setUp()
	{
		$this->my_control = new MultiDateControl;
//		$this->differet_control = new MultiDateControl;
//		$this->my_control->MultiDateControl(20, 3, 2, 1);
	}

	protected function tearDown()
	{
		unset($this->my_control);
		unset($this->differet_control);
	}
	
	public function testThatStaticVariablesAreStatic()
	{
		$this->assertEquals("Multi-Date Control", $this->my_control->getName());
		$this->assertEquals("Date (Multi-Input)", $this->my_control->getType());
		$this->assertEquals('<options><startYear>1970</startYear><endYear>2070</endYear><era>No</era><displayFormat>MDY</displayFormat><defaultValue><day /><month /><year /><era /></defaultValue></options>', $this->my_control->initialOptions());
	}
	
	public function testTheFunctionGetSearchString()
	{
		//$this->assertFalse($this->my_control->getSearchString("my string"));
		$this->assertTrue($this->my_control->isEmpty());
		$this->assertTrue($this->my_control->isXMLPacked());
	}	
	
	
	/**
     	* @dataProvider provider2
     	*/
	public function testTheMultiDateStoredValueToDisplay2($a, $b, $c)
	{
		$this->my_control->storedValueToDisplay($a, $b, $c);
	}
	
	public function provider2()
	{
		return array
		(
			array('<xml><kid>myxmlsring</kid></xml>', 10, 3)
		);
	}
	
/*
	public function testMultiDateControlFunctionDisplayXML2()
	{
		$this->assertEquals($this->different_control->displayXML(), '');
	}
*/	
	
	public function testMultiDateControlFunctionDisplayXML()
	{
		$this->assertEquals($this->my_control->displayXML(), '');
	}
	
}

?>
