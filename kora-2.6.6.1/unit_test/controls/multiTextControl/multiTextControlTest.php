<?php

include_once('/usr/share/php/PHPUnit/Framework.php');
include_once ('../controls/multiTextControl.php');

class MultiTextControlsTest extends PHPUnit_Framework_TestCase
{
	public $my_control = null;
	public $differet_control = null;
	
	
	protected function setUp()
	{
		$this->my_control = new MultiTextControl;
//		$this->differet_control = new MultiTextControl;
//		$this->my_control->MultiTextControl(20, 3, 2, 1);
	}

	protected function tearDown()
	{
		unset($this->my_control);
		unset($this->differet_control);
	}
	
	public function testThatStaticVariablesAreStatic()
	{
		$this->assertEquals("Multi-Text Control", $this->my_control->getName());
		$this->assertEquals("Text (Multi-Input)", $this->my_control->getType());
		$this->assertEquals('<options><regex></regex><rows>1</rows><columns>25</columns><defaultValue /></options>', $this->my_control->initialOptions());
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
	public function testTheMultiTextStoredValueToDisplay2($a, $b, $c)
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
	
	public function testMultiTextControlFunctionDisplayXML()
	{
		$this->assertEquals($this->my_control->displayXML(), '');
	}
	
}

?>
