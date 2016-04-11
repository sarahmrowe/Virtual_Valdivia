<?php

namespace KoraORM;

/**
 * A KoraEntity represents a generic object in a Kora repository which is a
 * record in the Kora repository.
 * 
 * This base class provides the common functionality of all KoraEntities.
 */
class KoraEntity implements \JsonSerializable
{
	/**
	 * The KoraManager instance which owns this object.
	 * 
	 * @var KoraManager
	 */
	private $manager = null;
	
	/**
	 * The KID of this KoraEntity.
	 * 
	 * @var mixed
	 */
	private $kid = null;
	
	/**
	 * The data on this object from Kora.
	 * 
	 * This variable may be unset, in which case it will be loaded later when
	 * needed. It will also be initially set to contain raw data retrieved from
	 * a Kora search. They will be later converted into KoraControl instances
	 * as needed.
	 * 
	 * @var mixed
	 */
	private $koraFields = null;
	
	/**
	 * Create a generic KoraEntity instance.
	 * 
	 * Subclasses should ensure this constructor gets called.
	 * 
	 * This constructor should only be used internally by this library. The
	 * recommended way to obtain a KoraEntity instance is via a KoraManager.
	 * 
	 * @param KoraManager $manager the KoraManager which created this KoraEntity
	 * @param mixed $kid the KID of this KoraEntity
	 * @param mixed $koraFields some raw Kora data (optional)
	 */
	public function __construct($manager, $kid, $koraFields = null)
	{
		$this->manager = $manager;
		$this->kid = KoraManager::parseKid($kid);
		$this->koraFields = $koraFields;
	}
	
	public function __get($name)
	{
		$value = $this->get($name);
		
		if (is_null($value))
		{
			$trace = debug_backtrace();
			trigger_error(
					'Undefined property: ' . $name .
					' in ' . $trace[0]['file'] .
					' on line ' . $trace[0]['line'],
					E_USER_NOTICE);
		}
		
		return $value;
	}
	
	public function __isset($name)
	{
		if ($name == 'manager')
			return isset($this->manager);
		else if ($name == 'kid')
			return isset($this->kid['kid']);
		else if ($name == 'projectID')
			return isset($this->kid['project']);
		else if ($name == 'schemeID')
			return isset($this->kid['scheme']);
		else if ($name == 'recordID')
			return isset($this->kid['record']);
		else
			return isset($this->getMetadata()['controls'][$name]);
	}
	
	public function jsonSerialize()
	{
		$json = array(
				'kid' => $this->kid['kid'],
				'projectID' => $this->kid['project'],
				'schemeID' => $this->kid['scheme'],
				'recordID' => $this->kid['record']);
		
		$ometa = $this->getMetadata();
		foreach ($ometa['controls'] as $name => $cmeta)
		{
			if (isset($this->koraFields[$cmeta['koraName']]))
			{
				$json[$name] = $this->get($name);
			}
		}
		
		return $json;
	}
	
	/**
	 * Get the value of a property of this KoraEntity.
	 * 
	 * This method allows for the generic retrieval of properties from an
	 * instance of this class.
	 * 
	 * @param string $name the name of the property
	 * @return mixed the value of the property on success, <code>null</code> on failure
	 */
	public function get($name)
	{
		// check for the special properties
		if ($name == 'manager')
			return $this->manager;
		else if ($name == 'kid')
			return $this->kid['kid'];
		else if ($name == 'projectID')
			return $this->kid['project'];
		else if ($name == 'schemeID')
			return $this->kid['scheme'];
		else if ($name == 'recordID')
			return $this->kid['record'];
		
		// check the metadata for this object to map the field name to the Kora name of the control
		$meta = $this->getMetadata();
		if (isset($meta['controls'][$name]))
		{
			// load the object data from Kora if it isn't already
			if (is_null($this->koraFields))
				$this->load();
			
			if (isset($this->koraFields[$meta['controls'][$name]['koraName']]))
			{
				// check if the data has been converted to a KoraControl yet
				if (!($this->koraFields[$meta['controls'][$name]['koraName']] instanceof KoraControl))
				{
					$classname = "\\KoraORM\\KoraControl";
					if (isset($meta['controls'][$name]['type']))
						$classname = $meta['controls'][$name]['type']['classname'];
				
					$this->koraFields[$meta['controls'][$name]['koraName']] = new $classname($this, $this->koraFields[$meta['controls'][$name]['koraName']]);
				}
				
				return $this->koraFields[$meta['controls'][$name]['koraName']];
			}
		}
		
		return null;
	}
	
	/**
	 * Retrieve the metadata for this KoraEntity instance.
	 * 
	 * @return the metadata for this KoraEntity instance
	 */
	public function getMetadata()
	{
		return $this->manager->getObjectMetadata($this->kid['project'], $this->kid['scheme']);
	}
	
	/**
	 * Load the data for this object from the Kora backend.
	 * 
	 * This is used for lazy-loading.
	 * 
	 * @throws Exception if the KID does not exist in Kora
	 */
	private function load()
	{
		$results = KORA_Search( $this->manager->searchToken, $this->kid['project'], $this->kid['scheme'],
			new \KORA_Clause('KID', '=', $this->kid['kid']),
			'ALL',
			array( array( 'field' => 'kid', 'direction' => SORT_ASC ) )
		);
		
		if ( empty($results) )
			throw new Exception('The KID provided does not exist.');

        $this->koraFields = $results[$this->kid['kid']];
	}
}