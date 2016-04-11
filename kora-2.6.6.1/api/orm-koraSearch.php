<?php
/**
 * The Kora ORM search API is intended to provide a pluggable and extendable
 * API for providing various search functionalities for the Kora ORM interface.
 * 
 * @author Zachary Pepin <zachary.pepin@matrix.msu.edu>
 *
 */

namespace KoraORM;

/**
 * A base class for all Kora search handlers.
 * 
 * All search handlers are expected to implement this interface.
 */
abstract class SearchHandler
{
	/**
	 * The Kora manager to which the search handler is attached. All searches
	 * it performs will upon this Kora manager.
	 * 
	 * @var \KoraORM\KoraManager
	 */
	private $manager;
	
	/**
	 * The project ID of the scheme to search.
	 * 
	 * @var int
	 */
	private $projectID;
	
	/**
	 * The scheme ID of the scheme to search.
	 * 
	 * @var int
	 */
	private $schemeID;
	
	/**
	 * Create a new search handler attached to the given Kora manager,
	 * project, and scheme.
	 * 
	 * All search handlers should have a constructor which accepts a single
	 * argument, a Kora manager. This Kora manager should be provided as the
	 * argument for this constructor.
	 * 
	 * @param \KoraORM\KoraManager $manager
	 * @param int $projectID
	 * @param int $schemeID
	 */
	public function __construct(\KoraORM\KoraManager $manager, $projectID, $schemeID)
	{
		$this->manager = $manager;
		$this->projectID = intval($projectID);
		$this->schemeID = intval($schemeID);
	}
	
	public function __isset($name)
	{
		switch ($name)
		{
			case 'manager':
				return isset($this->manager);
			case 'projectID':
				return isset($this->projectID);
			case 'schemeID':
				return isset($this->schemeID);
		}
		return false;
	}
	
	public function __get($name)
	{
		switch ($name)
		{
			case 'manager':
				return $this->manager;
			case 'projectID':
				return $this->projectID;
			case 'schemeID':
				return $this->schemeID;
		}

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
	}
	
	/**
	 * Print a search form for use with this search handler.
	 * 
	 * The request parameters produced by this search form should be able to be
	 * handled by the implementation of \KoraORM\SearchHandler::handleRequest()
	 * for this handler. The arguments for this method are expected in the form
	 * of an associative array.
	 * 
	 * @param string $method which method should be used by the search form
	 * @param string $action what the value of the action part of the search
	 *   form should be
	 */
	public abstract function printForm(array $args = array());
	
	/**
	 * Parse a search request and perform the requested search.
	 * 
	 * @param array $params an associative array of search parameters as produced
	 *   by the search form produced by \KoraORM\SearchHandler::printForm()
	 * @return mixed a numeric array of search results with most relavant first
	 *   if the search request could be parsed successfully, <code>false</code>
	 *   if the search request was not found or improperly formatted
	 */
	public abstract function handleRequest(array $params = null);
}

/**
 * A base class for search handlers that deal with Kora basic search.
 */
abstract class BasicSearchHandler extends SearchHandler
{
	/**
	 * Create a new basic search handler attached to the given Kora manager.
	 * 
	 * This constructor should only be called by subclasses.
	 * 
	 * @param \KoraORM\KoraManager $manager
	 * @param int $projectID
	 * @param int $schemeID
	 */
	public function __construct(\KoraORM\KoraManager $manager, $projectID, $schemeID)
	{
		parent::__construct($manager, $projectID, $schemeID);
	}
	
	/**
	 * Perform the actual query.
	 * 
	 * All basic search handlers are expected to override this method to
	 * provide the search functionality.
	 * 
	 * @param string $query a basic search query
	 * @return array a numeric array of search results with most relavant first
	 */
	public abstract function basicSearch($query);
	
	/**
	 * Prints a basic search form with a text box and a search button.
	 * 
	 * @see \KoraORM\SearchHandler::printForm()
	 */
	public final function printForm(array $args = array())
	{
		$tagid = isset($args['tagid']) ? htmlspecialchars(strval($args['tagid'])) : null;
		$method = isset($args['method']) ? htmlspecialchars(strval($args['method'])) : 'GET';
		$action = isset($args['action']) ? htmlspecialchars(strval($args['action'])) : null;
		
		echo '<div'.($tagid === null ? '' : ' id="'.$tagid.'"').' class="kora-basic-search">';
		echo '<form method="'.$method.'"'.($action === null ? '' : ' action="'.$action.'"').'>';
		echo '<input type="text" name="kora-basic-q" />';
		echo '<input type="submit" value="Search" />';
		echo '</form>';
		echo '</div>';
	}
	
	/**
	 * Handles a basic search request produced by a basic search form.
	 * 
	 * @see \KoraORM\SearchHandler::handleRequest()
	 * @see \KoraORM\BasicSearchHandler::printForm()
	 */
	public final function handleRequest(array $params = null)
	{
		$params = $params === null ? $_REQUEST : $params;
		
		if (isset($params['kora-basic-q']))
		{
			return $this->basicSearch(strval($params['kora-basic-q']));
		}
		return false;
	}
}

/**
 * The search manager is responsible for managing all of the search handlers.
 * 
 * @author Zachary Pepin <zachary.pepin@matrix.msu.edu>
 *
 */
class SearchManager
{
	private $manager;
	private $handlerCache = array();
	
	public function __construct(\KoraORM\KoraManager $manager)
	{
		$this->manager = $manager;
		$this->registerHandlers();
	}
	
	public function getDefaultBasicHandler($projectID, $schemeID)
	{
		$slug = false;
		if (defined('KORA_ORM_DEFAULT_BASIC_SEARCH_HANDLER'))
		{
			$slug = KORA_ORM_DEFAULT_BASIC_SEARCH_HANDLER;
		}
		else
		{
			$basicHandlers = array_map(function(&$handler) {
				return $handler['basic'];
			}, $this->handlerCache);
			if (count($basicHandlers) > 0)
			{
				$slug = $basicHandlers[0]['slug'];
			}
		}
		if ($slug !== false)
		{
			return $this->getHandler($slug, $projectID, $schemeID);
		}
		return false;
	}
	
	public function getDefaultAdvancedHandler($projectID, $schemeID)
	{
		$slug = false;
		if (defined('KORA_ORM_DEFAULT_ADVANCED_SEARCH_HANDLER'))
		{
			$slug = KORA_ORM_DEFAULT_ADVANCED_SEARCH_HANDLER;
		}
		else
		{
			$advancedHandlers = array_map(function(&$handler) {
				return !$handler['basic'];
			}, $this->handlerCache);
			if (count($advancedHandlers) > 0)
			{
				$slug = $advancedHandlers[0]['slug'];
			}
		}
		if ($slug !== false)
		{
			return $this->getHandler($slug, $projectID, $schemeID);
		}
		return false;
	}
	
	public function getHandler($slug, $projectID, $schemeID)
	{
		if (isset($this->handlerCache[$slug]))
		{
			return new $this->handlerCache[$slug]['classname']($this->manager, $projectID, $schemeID);
		}
	}
	
	public function getHandlers()
	{
		return unserialize(serialize($this->handlerCache));
	}
	
	private function registerHandlers()
	{
		// open the search handlers directory
		foreach (scandir(KORA_ORM_SEARCH_HANDLERS_DIR) as $file)
		{
			// We are going to assume that the name of the custom
			// SearchHandler class is going to match the name of the PHP
			// file. As such, we are going to check if the filename is
			// a file, and whether it follows PHP naming conventions.
			$fullname = KORA_ORM_SEARCH_HANDLERS_DIR . DIRECTORY_SEPARATOR . $file;
			if (is_file($fullname) && preg_match("/^[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*\\.php$/", $file))
			{
				// extracting the name of the custom search handler class
				$classname = "\\KoraORM\\Search\\" . substr($file, 0, strlen($file) - 4);
				// including the custom search handler
				require_once( $fullname );
				// loading the custom search handler's metadata
				$metadata = $classname::loadMetadata();
				
				// generate the full metadata based off the partial
				// metadata found in the search handler class.
				$cachedmeta = array(
						'classname' => $classname,
						'name' => strval($metadata['name']),
						'slug' => strval($metadata['slug']));
				
				// add the metadata to the cache
				$this->handlerCache[$cachedmeta['slug']] = $cachedmeta;
			}
		}
	}
}