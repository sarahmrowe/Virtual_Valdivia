<?php

/**
Copyright (2008) Matrix: Michigan State University

This file is part of KORA.

KORA is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

KORA is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>. */

// Initial Version: Joseph Deming, 2013

require_once(__DIR__.'/../includes/includes.php');

/**
 * This class encapsulates the creation, processing, and display of the
 * ingestion forms for KORA.  It pulls a list of controls and control
 * collections/pages, and displays the appropriate form.
 *
 * Constructor: ingestionForm(project id, scheme id, record id, preset record id)
 *nota
 */

class ControlCollection
{
	protected $pid = 0;
	protected $sid = 0;
	protected $collid = 0;
	protected $name = null;
	protected $desc = null;
	protected $sequence = 0;
    
	function __construct($pid_, $sid_, $collid_)
	{
		global $db;
		
		$results = $db->query("SELECT schemeid,collid,name,description,sequence FROM collection WHERE schemeid=".escape($sid_)." AND collid=".escape($collid_)." LIMIT 1");
		if ($results->num_rows == 0) { throw new Exception(gettext('Invalid collid requested, no collection found for this scheme with collid ['.escape($collid_).']')); return false; }
		$results = $results->fetch_assoc();
		// JUST SANITY CHECK HERE
		if ($results['schemeid'] != $sid_) { throw new Exception(gettext('Requested collid did not match sid found in database for collection, please inspect.')); return false; }
		
		// LOOK-UP OK, SO START SETTING VALUES
		$this->pid = $pid_;
		$this->sid = $results['schemeid'];
		$this->collid = $results['collid'];
		$this->name = $results['name'];
		$this->desc = $results['description'];
		$this->sequence = $results['sequence'];
				
	}
	
	public function GetPID() { return $this->pid; }
	public function GetSID() { return $this->sid; }
	public function GetCollID() { return $this->collid; }
	public function GetName() { return $this->name; }
	public function GetDesc() { return $this->desc; }
	public function GetSequence() { return $this->sequence; }

}

?>
