<?php

namespace KoraORM;

/**
 * A KoraControl represents a single control in a single record in Kora.
 * 
 * The KoraControl is responsible for converting the raw data from Kora into a
 * more intelligible and useful format.
 * 
 * @author Zachary Pepin <zachary.pepin@matrix.msu.edu>
 */
class KoraControl
{
	/**
	 * The KoraEntity instance this control belongs to.
	 * 
	 * @var KoraEntity
	 */
	private $entity = null;
	
	/**
	 * The raw data from Kora.
	 * 
	 * @var mixed
	 */
	protected $koraData = null;
	
	/**
	 * Create a new KoraControl instance.
	 * 
	 * Subclasses should be sure that this contructor gets called when they are
	 * constructed.
	 * 
	 * This constructor should only be used internally in this library.
	 * KoraControls should be obtained from a KoraEntity instance.
	 * 
	 * @param KoraEntity $object the KoraEntity instance that made this control
	 * @param mixed $koraData the raw data for the control from Kora
	 */
	public function __construct($entity, $koraData)
	{
		$this->entity = $entity;
		$this->koraData = $koraData;
	}
	
	public function __get($name)
	{
		if ($name == 'entity')
			return $this->entity;
		
		$trace = debug_backtrace();
		trigger_error(
				'Undefined property: ' . $name .
				' in ' . $trace[0]['file'] .
				' on line ' . $trace[0]['line'],
				E_USER_NOTICE);
		
		return null;
	}
	
	public function __isset($name)
	{
		if ($name == 'entity')
			return isset($this->entity);
		return false;
	}
}