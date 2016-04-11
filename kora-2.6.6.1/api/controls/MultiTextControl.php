<?php

namespace KoraORM\Controls;

require_once( __DIR__ . '/TextControl.php' );

/**
 * A default control for text (multi-input).
 * 
 * @author Zachary Pepin <zachary.pepin@matrix.msu.edu>
 */
class MultiTextControl extends \KoraORM\KoraControl implements \IteratorAggregate, \ArrayAccess, \Countable, \JsonSerializable
{
	private $texts = null;
	
	public static function loadMetadata()
	{
		return array( 'koraType' => 'MultiTextControl' );
	}
	
	public function getIterator()
	{
		return new \ArrayIterator($this->getTexts());
	}
	
	public function offsetExists($offset)
	{
		return is_int($offset) && 0 <= $offset && $offset < count($this->getTexts());
	}
	
	public function offsetGet($offset)
	{
		return $this->getTexts()[$offset];
	}
	
	public function offsetSet($offset, $value)
	{
		throw new Exception("unsupported operation");
	}
	
	public function offsetUnset($offset)
	{
		throw new Exception("unsupported operation");
	}
	
	public function count()
	{
		return count($this->getTexts());
	}
	
	public function jsonSerialize()
	{
		return $this->getTexts();
	}
	
	private function getTexts()
	{
		if (!isset($this->texts) && is_array($this->koraData))
		{
			$this->texts = array();
			foreach ($this->koraData as $koraData)
				$this->texts[] = new \KoraORM\Controls\TextControl($this->entity, $koraData);
		}
		return $this->texts;
	}
	
	public function printHtml()
	{
		$first = true;
		foreach ($this->getTexts() as $text)
		{
			if ($first)
				$first = false;
			else
				echo ', ';
			$text->printHtml();
		}
	}
}