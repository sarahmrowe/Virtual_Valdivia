<?php
include_once('/usr/share/php/PHPUnit/Framework.php');
include_once ('../includes/clientUtilities.php');
include_once ('../includes/conf.php');



class ClientUtilitiesTest extends PHPUnit_Framework_TestCase
{
	public $test_pid = null;
	public $test_sid = null;

	/**
	 * @dataProvider myFileNames01
	 */
	public function testGetFullURLFromFileName($test_filename)
	{
		$fileParts = explode('-', $test_filename);
		$this->test_pid = hexdec($fileParts[0]);
		$this->test_sid = hexdec($fileParts[1]);
		$this->assertEquals(baseURI.fileDir.'23/133/'.$test_filename, getFullURLFromFileName($test_filename));
	}

	/**
	 * @dataProvider myFileNames01
	 */
	public function testGetFullPathFromFileName($test_filename)
	{
		$fileParts = explode('-', $test_filename);
		$this->test_pid = hexdec($fileParts[0]);
		$this->test_sid = hexdec($fileParts[1]);
		$this->assertEquals(basePath.fileDir.'23/133/'.$test_filename, getFullPathFromFileName($test_filename));
	}

	/**
	 * @dataProvider myFileNames01
	 */
	public function testGetThumbURLFromFileName($test_filename)
	{
	    $this->assertEquals(baseURI.fileDir.'23/133/thumbs/'.$test_filename, getThumbURLFromFileName($test_filename));
	}

	public function myFileNames01()
	{
		return array(
			array('17-85-54-134-stained_glass-a0a5o0-a_11434.jpg'),
			array('17-85-3D-134-stained_glass-a0a5f9-a_11434.jpg'),
			array('17-85-26-134-stained_glass-a0a4y8-a_11434.jpg')
		);
	}

	/**
	 * @dataProvider myRecordIDs01
	 */
	public function testParseRecordID($my_recordID01)
	{
		$parseArray = Record::ParseRecordID($my_recordID01);

		$this->assertTrue(is_array($parseArray));
		$this->assertEquals(21, $parseArray['project']);
		$this->assertEquals(120, $parseArray['scheme']);
		$this->assertTrue(in_array($parseArray['record'], array(hexdec(10), hexdec(2), hexdec('F'))));
		$this->assertEquals($my_recordID01, $parseArray['rid']);
	}

	public function myRecordIDs01()
	{
		return array(
			array('15-78-10'),
			array('15-78-2'),
			array('15-78-F')
		);
	}

	/**
	 * @dataProvider myRecordIDs02
	 * assert that the RecordIDs are false
	 */
	public function testParseRecordIDReturnsFalse($my_recordID02)
	{
		$this->assertFalse(Record::ParseRecordID($my_recordID02));
	}

	public function myRecordIDs02()
	{
		return array(
			array('1Z-2-78-10'),
			array('1-7g8-2'),
			array('1Z-7D8-F')
		);
	}
	
	/**
	 * @dataProvider myRecordIDs03
	 */
//	public function testGetURLFromRecordID($rid, $cid)
//	{		
//		$this->db = PHPUnit_Util_PDO::factory(
//			'mysql://'
//		);
//		
//		echo 'this is it:'.getURLFromRecordID($rid, $cid);
//		
//		
//	}
//	
//	public function myRecordID03()
//	{
//		return array(
//			array('13-73-E2', 5),
//			array('19-43-23', 5),
//			array('23-12-12', 5)
//		);
//	}

}

?>
