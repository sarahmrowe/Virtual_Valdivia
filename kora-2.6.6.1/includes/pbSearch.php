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

// Initial Version: Brian Beck, 2008

require_once('searchAPI.php');

/*
 * PBSearch
 * 
 * This function emulates the PBSearch function used in older MATRIX frontend
 * projects.  Its emulation is not perfect; it requires the addition of an authentication
 * token, which is unavoidable given that the new KORA has much stricter data-access
 * restrictions than the old KORA.  As well, some quirks of the old KORA may or may not
 * be emulated properly.
 */

function PBSearch($pid, $sid, $authToken, $origQueries=array(), $boolean='AND', $orderBy='', $desc=false, $fieldsToReturn=array(), $orderMulti=false)
{
	// Check that parameters are valid
	$pid = (int) $pid;
	$sid = (int) $sid;
	if (!is_array($origQueries))
	{
		$origQueries = array($origQueries);
	}
	if (!is_array($fieldsToReturn))
	{
		$fieldsToReturn = array($fieldsToReturn);
	}
	if ($pid < 1 || $sid < 1 || !in_array($boolean, array('AND', 'OR')))
	{
		return false;
	}

	// Parse the queries
	$newQueries = array();
	foreach($origQueries as $query)
	{
		$args = explode(' ', $query);
		
		// Get the fieldname and the operator, then put the rest back together
		// in case there were spaces in the value
		$field = $args[0];
		$operator = $args[1];
		unset($args[1]);
		unset($args[0]);
		$value = implode(' ', $args);

        // pbds are now called KIDs, so adjust any legacy queries accordingly
        if (strtoupper($field) == 'PBD') $field == 'KID';		
		
		$newQueries[] = new KORA_Clause($field, $operator, $value);
	}
	
	if (empty($newQueries))
	{
		// If there are no queries, legacy behavior is to return all results.
		$finalQuery = new KORA_Clause('KID', '!=', '');
	}
	else
	{
		foreach($newQueries as $query)
		{
			// The first time, set finalQuery = the first query
			if (!isset($finalQuery))
			{
				$finalQuery = $query; 
			} // otherwise, use the operator to join the new query into the list
			else
			{
				$finalQuery = new KORA_Clause($finalQuery, $boolean, $query);
			}
		}
	}
	
	$values = KORA_Search($authToken, $pid, $sid, $finalQuery, $fieldsToReturn, $orderBy, !$desc);
	
	// Process any field translation between old and new return formats here
	// Problem to be addressed another day: How is this function supposed to know
	// what kind of controls various things are?
	
	// Handle that absurd format where all results were of the form
	// array(schemeid => array(thedataIactuallywant)
	$finalValues = array($sid => $values);
	
	return $finalValues;
}

?>
