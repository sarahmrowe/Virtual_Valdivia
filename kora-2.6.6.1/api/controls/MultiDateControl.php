<?php

namespace KoraORM\Controls;

require_once( __DIR__ . '/DateControl.php' );

/**
 * A default control for dates (multi-input).
 * 
 * @author Zachary Pepin <zachary.pepin@matrix.msu.edu>
 */
class MultiDateControl extends \KoraORM\KoraControl implements \IteratorAggregate, \ArrayAccess, \Countable, \JsonSerializable
{
	private $dates = null;
	
	public static function loadMetadata()
	{
		return array( 'koraType' => 'MultiDateControl' );
	}
	
	public function getIterator()
	{
		return new \ArrayIterator($this->getDates());
	}
	
	public function offsetExists($offset)
	{
		return is_int($offset) && 0 <= $offset && $offset < count($this->getDates());
	}
	
	public function offsetGet($offset)
	{
		return $this->getDates()[$offset];
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
		return count($this->getDates());
	}
	
	public function jsonSerialize()
	{
		return $this->getDates();
	}
	
	private function getDates()
	{
		if (!isset($this->dates) && is_array($this->koraData))
		{
			$this->dates = array();
			foreach ($this->koraData as $koraData)
				$this->dates[] = new \KoraORM\Controls\DateControl($this->entity, $koraData);
		}
		return $this->dates;
	}
	
	public function printHtml($format = KORA_ORM_DEFAULT_DATE_FORMAT)
	{
		$first = true;
		foreach ($this->getDates() as $date)
		{
			if ($first)
				$first = false;
			else
				echo ', ';
			$data->printHtml($format);
		}
	}
}