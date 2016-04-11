<?php

include_once('/usr/share/php/PHPUnit/Framework.php');
include_once ('../controls/imageControl.php');

class ImageControlsTest extends PHPUnit_Framework_TestCase
{
	public $my_control = null;
	public $differet_control = null;
	
	
	protected function setUp()
	{
		$this->my_control = new ImageControl;
//		$this->differet_control = new ImageControl;
//		$this->my_control->ImageControl(20, 3, 2, 1);
	}

	protected function tearDown()
	{
		unset($this->my_control);
		unset($this->differet_control);
	}
	
	public function testThatStaticVariablesAreStatic()
	{
		$this->assertEquals("Image Control", $this->my_control->getName());
		$this->assertEquals("Image", $this->my_control->getType());
		$this->assertEquals('<options><maxSize>0</maxSize><restrictTypes>Yes</restrictTypes><allowedMIME><mime>image/bmp</mime><mime>image/gif</mime><mime>image/jpeg</mime><mime>image/png</mime><mime>image/pjpeg</mime><mime>image/x-png</mime></allowedMIME><thumbWidth>125</thumbWidth><thumbHeight>125</thumbHeight><archival>No</archival></options>', $this->my_control->initialOptions());
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
	public function testTheImageStoredValueToDisplay2($a, $b, $c)
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
	public function testImageControlFunctionDisplayXML2()
	{
		$this->assertEquals($this->different_control->displayXML(), '');
	}
*/	
	
	public function testImageControlFunctionDisplayXML()
	{
		$this->assertEquals($this->my_control->displayXML(), '');
	}
	
}

?>
