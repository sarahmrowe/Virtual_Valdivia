<?php

include_once('/usr/share/php/PHPUnit/Framework.php');
include_once ('../controls/listControl.php');

class ListControlsTest extends PHPUnit_Framework_TestCase
{
	public $my_control = null;
	public $differet_control = null;
	
	
	protected function setUp()
	{
		$this->my_control = new ListControl;
//		$this->differet_control = new ListControl;
//		$this->my_control->ListControl(20, 3, 2, 1);
	}

	protected function tearDown()
	{
		unset($this->my_control);
		unset($this->differet_control);
	}
	
	public function testThatStaticVariablesAreStatic()
	{
		$this->assertEquals("List Control", $this->my_control->getName());
		$this->assertEquals("List", $this->my_control->getType());
		$this->assertEquals('<options><defaultValue /></options>', $this->my_control->initialOptions());
	}
	
	public function testTheFunctionGetSearchString()
	{
		//$this->assertFalse($this->my_control->getSearchString("my string"));
		$this->assertTrue($this->my_control->isEmpty());
		$this->assertFalse($this->my_control->isXMLPacked());
	}	
	
	public function testListControlFunctionDisplayXML()
	{
		$this->assertEquals($this->my_control->displayXML(), '');
	}
	
	
	/**
     	* @dataProvider provider2
     	*/
	public function testTheListStoredValueToDisplay2($a, $b, $c)
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
	public function testListControlFunctionDisplayXML2()
	{
		$this->assertEquals($this->different_control->displayXML(), '');
	}
*/	
	

	
}

?>
