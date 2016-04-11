<?php

namespace KoraORM\Controls;

/**
 * A default control for dates.
 * 
 * @author Zachary Pepin <zachary.pepin@matrix.msu.edu>
 */
class DateControl extends \KoraORM\KoraControl implements \JsonSerializable
{
	private $month = null;
	private $day = null;
	private $year = null;
	private $era = null;
	private $prefix = null;
	private $suffix = null;
	private $timestamp = null;
	
	public static function loadMetadata()
	{
		return array( 'koraType' => 'DateControl' );
	}
	
	public function __get($name)
	{
		switch ($name)
		{
			case 'month':
				return $this->getMonth();
			case 'day':
				return $this->getDay();
			case 'year':
				return $this->getYear();
			case 'era':
				return $this->getEra();
			case 'prefix':
				return $this->getPrefix();
			case 'suffix':
				return $this->getSuffix();
			case 'timestamp':
				return $this->getTimestamp();
			default:
				return parent::__get($name);
		}
	}
	
	public function __isset($name)
	{
		switch ($name)
		{
			case 'month':
				return !is_null($this->getMonth());
			case 'day':
				return !is_null($this->getDay());
			case 'year':
				return !is_null($this->getYear());
			case 'era':
				return !is_null($this->getEra());
			case 'prefix':
				return !is_null($this->getPrefix());
			case 'suffix':
				return !is_null($this->getSuffix());
			case 'timestamp':
				return !is_null($this->getTimestamp());
			default:
				return parent::__isset($name);
		}
	}
	
	public function __toString()
	{
		return $this->format();
	}
	
	public function jsonSerialize()
	{
		return array(
				'month' => $this->getMonth(),
				'day' => $this->getDay(),
				'year' => $this->getYear(),
				'era' => $this->getEra(),
				'prefix' => $this->getPrefix(),
				'suffix' => $this->getSuffix(),
				'timestamp' => $this->getTimestamp()
				);
	}
	
	private function getMonth()
	{
		if (!isset($this->month) && isset($this->koraData['month']))
			$this->month = intval($this->koraData['month']);
		return $this->month;
	}
	
	private function getDay()
	{
		if (!isset($this->day) && isset($this->koraData['day']))
			$this->day = intval($this->koraData['day']);
		return $this->day;
	}
	
	private function getYear()
	{
		if (!isset($this->year) && isset($this->koraData['year']))
			$this->year = intval($this->koraData['year']);
		return $this->year;
	}
	 
	private function getEra() 
	{ 
		if (!isset($this->era) && isset($this->koraData['era']))
			$this->era = empty($this->koraData['era']) ? null : $this->koraData['era'];
		return $this->era;
	}
	
	private function getPrefix() 
	{
		if (!isset($this->prefix) && isset($this->koraData['prefix']))
			$this->prefix = empty($this->koraData['prefix']) ? null : $this->koraData['prefix'];
		return $this->prefix;
	}
	
	private function getSuffix() 
	{
		if (!isset($this->suffix) && isset($this->koraData['suffix']))
			$this->suffix = empty($this->koraData['suffix']) ? null : $this->koraData['suffix'];
		return $this->suffix;
	}
	
	private function getTimestamp() 
	{
		if (!isset($this->timestamp))
			$this->timestamp = mktime(0, 0, 0, $this->getMonth(), $this->getDay(), $this->getYear());
		return $this->timestamp;
	}
	
	public function format($format = KORA_ORM_DEFAULT_DATE_FORMAT)
	{
		return date($format, $this->getTimestamp());
	}
	
	public function printHtml($format = KORA_ORM_DEFAULT_DATE_FORMAT)
	{
		echo htmlspecialchars($this->format($format));
	}
}