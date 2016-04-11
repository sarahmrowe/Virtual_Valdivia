<?php

include_once('/usr/share/php/PHPUnit/Framework.php');
include_once ('../controls/fileControl.php');

class FileControlsTest extends PHPUnit_Framework_TestCase
{
	public $my_control = null;
	public $differet_control = null;
	
	
	protected function setUp()
	{
		$this->my_control = new FileControl;
//		$this->differet_control = new FileControl;
//		$this->my_control->FileControl(20, 3, 2, 1);
	}

	protected function tearDown()
	{
		unset($this->my_control);
		unset($this->differet_control);
	}
	
	public function testThatStaticVariablesAreStatic()
	{
		$this->assertEquals("File Control", $this->my_control->getName());
		$this->assertEquals("File", $this->my_control->getType());
		$this->assertEquals('<options><maxSize>0</maxSize><restrictTypes>No</restrictTypes><allowedMIME></allowedMIME><archival>No</archival></options>', $this->my_control->initialOptions());
	}
	
	public function testTheFunctionGetSearchString()
	{
		$this->assertFalse($this->my_control->getSearchString("my string"));
		$this->assertTrue($this->my_control->isEmpty());
		$this->assertTrue($this->my_control->isXMLPacked());
	}	
	
	
	/**
     	* @dataProvider provider2
     	*/
	public function testTheFileStoredValueToDisplay2($a, $b, $c)
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
	public function testFileControlFunctionDisplayXML2()
	{
		$this->assertEquals($this->different_control->displayXML(), '');
	}
*/	
	
	public function testFileControlFunctionDisplayXML()
	{
		$this->assertEquals($this->my_control->displayXML(), '');
	}
	
}

?>
