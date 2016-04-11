<?php
/**
Copyright (2009) MATRIX: Michigan State University

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
along with this program.  If not, see <http://www.gnu.org/licenses/>. 
*/

/**
Initial Version Dan Shiman August 2009

I.  About the code
It's server-ready application, fully compliant with OAI-PMH's required specifications for repositories.  It supports the six OAI-PMH verbs and all of their required arguments.  The code also supports the required error handling (and error messages) for each verb.  The code itself has been tested against various KORA databases.

II.  Some decisions made
There were certain decisions made along the way, most of them related to what are some various optional specifications for OAI-PMH.  (The following will probably be more meaningful to those with some prior knowledge of OAI-PMH guidelines.)
- 	Timestamps used within the code comply with UTCdatetime specifications, as prescribed by OAI-PMH.  Timestamps operate at a days-level of granularity (e.g., YYYY-MM-DD) 
rather than seconds-level of granularity (e.g., YYYY-MM-DDThh:mm:ssZ).  This was done for easier timestamp processing against the KORA database (for instance, retrieving records by timestamp from the dublinCore table when from and until arguments are present in an HTTP request such as ListRecords), as well as for simplicity's sake. 
- 	For harvesting, each KORA record's unique identifier corresponds to URI (Uniform Resource Identifier) syntax.  The unique identifiers are based on the database's base URL (stored in the baseURL field of KORA's systemInfo table; e.g., spartanhistory.kora.matrix.msu.edu) appended with the KORA kid/identifier field (e.g., 1-4-11) for each record.  The string "oai" is used as the URI's scheme prefix, finally.  As follows:

	oai:spartanhistory.kora.matrix.msu.edu/1-4-11

- 	This OAI-PMH implementation does not support sets.  Sets are intellectual structures that OAI-PMH-compliant repositories can create to hierarchically organize and describe content in their database, and are used for selective harvesting.   Invoking the ListSets verb will presently only return an error message indicating that this repository does not support sets.
- 	This implementation does not support response compression, an OAI-PMH option.  Response compression is basically an HTML request-level method for response compression (server) and decompression (browser).
- 	This implementation does not support flow control, an optional OAI-PMH construct for breaking up long lists of returned repository records into smaller chunks.
-  	Information returned by the Identify response about the KORA repository is, at present, somewhat rudimentary.  There is no description mechanism, an optional, extensible container that would permit KORA to share more information about itself as an institution (such as other collaborators).

Note: sets, response compression, flow control, and description containers should be considered as future options for MATRIX's implementation of OAI-PMH.

III.  A caveat
The code will not work against older versions of the KORA database layout.  The handler is predicated upon a dublinCore table structure with the new timestamp column.  
*/
// Initial Version: Dan Shiman, August 2009

/* NOTE: LOCALHOST, MYSQL_USER, PASSWORD, and DATABASE will be specific to the host server */
mysql_connect("LOCALHOST","MYSQL_USER","PASSWORD");  
mysql_select_db("DATABASE");					

$baseURL = mysql_query("SELECT baseURL FROM systemInfo ORDER BY version DESC") or die (mysql_error());
$baseURL = mysql_result($baseURL,0);      
$oaiURL = "oai:".trim(substr($baseURL, strpos($baseURL, "/") + 2), "/");		// returns string for URI     
$oaiHandlerURL = $baseURL."oai-pmh.php";							// base URL concatenated with name of OAI-PMH handler

$verb = $_GET['verb'];
$identifier = $_GET['identifier'];
$resumptionToken = $_GET['resumptionToken'];
$from = $_GET['from'];
$until = $_GET['until'];
$metadataPrefix = $_GET['metadataPrefix'];
$set = $_GET['set'];

header("Content-type: text/xml");  // interpret file as XML  

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
/* echo "<?xml-stylesheet type=\"text/xsl\" href=\"oai-pmh.xsl\"?>";     	// - comment this line out for XML document tree (rather than xsl formatting)    */
echo  "<OAI-PMH xmlns=\"http://www.openarchives.org/OAI/2.0/\" 
		 xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" 
		 xsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/ 
					 http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd\">
		<responseDate>".gmdate("Y-m-d\TH:i:s")."Z</responseDate>
		<request";
		
switch($verb) {					// the six OAI-PMH verbs
	case "GetRecord":
		getRecord($identifier);
		break;
	case "ListRecords":
		listRecords();
		break;		
	case "ListIdentifiers":
		listIdentifiers();
		break;
	case "ListMetadataFormats":
		listMetadataFormats();
		break;
	case "Identify":
		identify(); 
		break;
	case "ListSets":
		listSets();
		break;
	default:     // catches the error if the verb is misspelled or missing entirely
		echo ">".$oaiHandlerURL."</request>";
		echo "<error code=\"badVerb\">The value of the verb argument is not a legal OAI-PMH verb</error>";	
		$haveError = TRUE;
}

echo "</OAI-PMH>";

////////////////////////////////// FUNCTIONS //////////////////////////////////
/* Retrieves information about the particular repository.  The description container (currently inactive/commented out) may be used for further description of the repository; e.g. collection-level metadata, collaborating institutions, etc. */

function identify() {	
	global $verb, $baseURL, $oaiHandlerURL;  
	$arrayLegalArguments = array('verb');

	$earliestDatestamp = mysql_query("SELECT * FROM dublinCore WHERE dublinCore.timestamp IS NOT NULL ORDER BY timestamp ASC LIMIT 1"); 
	$earliestDatestamp = mysql_fetch_assoc($earliestDatestamp);  	
	$earliestDatestamp = formatVariable($earliestDatestamp['timestamp']);  
	$earliestDatestamp = date("Y-m-d", strtotime($earliestDatestamp));

	$haveError = errorIllegalArguments($arrayLegalArguments, $haveError);  // check for illegal arguments

	if (!$haveError)  {
		foreach($_GET as $key => $value)	{	
			echo " ".$key."=\"".$value."\"";
		}
		echo ">".$oaiHandlerURL."</request>";
		echo "<".$verb.">";
		echo "<repositoryName>Matrix</repositoryName>";
		echo "<baseURL>".$baseURL."</baseURL>";
		echo "<protocolVersion>2.0</protocolVersion>";
		echo "<earliestDatestamp>".$earliestDatestamp."</earliestDatestamp>";
		echo "<deletedRecord>no</deletedRecord>";
		echo "<granularity>YYYY-MM-DD</granularity>";
		echo "<adminEmail>** ADMIN EMAIL HERE **</adminEmail>";

	/* optional
		<description><oai-identifier xmlns="http://www.openarchives.org/OAI/2.0/oai-identifier" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 	xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai-identifier http://www.openarchives.org/OAI/2.0/oai-identifier.xsd">
		<scheme>oai</scheme>
		<repositoryIdentifier>oaicat.oclc.org</repositoryIdentifier>
		<delimiter>:</delimiter>
		<sampleIdentifier>oai:oaicat.oclc.org:OCLCNo/ocm00000012</sampleIdentifier>
		</oai-identifier>
	</description>   */
		
		echo "</".$verb.">";
	}
}

///////////////////////////////////////////////////////////////////////////////
/* Retrieves information about metadata formats supported by KORA.  Currently, the database only maps to the Dublin Core format; this is reflected in the hard-coded fields below */

function listMetadataFormats()  {
	global $verb, $identifier, $oaiHandlerURL;
	$arrayLegalArguments = array('verb', 'identifier');

	$identifier = substr($identifier, (strrpos($identifier, ":")) + 1);		// returns the KORA identifier
	$result = mysql_query("SELECT * FROM dublinCore WHERE kid = '$identifier'");    // or die (mysql_error());
	$row = mysql_fetch_assoc($result);

	$haveError = errorIdentifier($row, $haveError);
	$haveError = errorIllegalArguments($arrayLegalArguments, $haveError);

	if(!$haveError)	{
			foreach($_GET as $key => $value)	{	
				echo " ".$key."=\"".$value."\"";
			}
			echo ">".$oaiHandlerURL."</request>";
			echo "<".$verb.">";
			echo "<metadataFormat>";
			echo "<metadataPrefix>oai_dc</metadataPrefix>";     	
			echo "<schema>http://www.openarchives.org/OAI/2.0/oai_dc.xsd</schema>";
			echo "<metadataNamespace>http://www.openarchives.org/OAI/2.0/oai_dc/</metadataNamespace>";
			echo "</metadataFormat>";
			echo "</".$verb.">";
	}
}

///////////////////////////////////////////////////////////////////////////////
/* Lists all, or some (depending on FROM and UNTIL arguments, if present), headers from the database's dublinCore records. */

function listIdentifiers()  {
	global $verb, $oaiURL, $metadataPrefix, $oaiHandlerURL, $from, $until;
	$arrayLegalArguments = array('metadataPrefix','verb','from','until','set','resumptionToken');
	$arrayRequiredArguments = array('metadataPrefix');

	$haveError = errorDisseminateFormats($metadataPrefix, $haveError);
	$haveError = errorIllegalArguments($arrayLegalArguments, $haveError);
	$haveError = errorIllegalDateArguments($from, $until, $haveError);
	$haveError = errorNoRecordsMatch($from, $until, $haveError);   
	$haveError = errorMissingArguments($arrayRequiredArguments, $haveError);
	$haveError = errorSets($haveError);

/* MySQL processing against dublinCore table will depend on whether FROM or UNTIL, or both, arguments are present  */

	if(!$haveError)
	{
		foreach($_GET as $key => $value)
		{	
			echo " ".$key."=\"".$value."\"";
		}
		echo ">".$oaiHandlerURL."</request>";
		echo "<".$verb.">";

		$result=mysql_query("SELECT * FROM dublinCore WHERE timestamp IS NOT NULL ORDER BY dublinCore.kid") or die;  

		if ($_GET['from'] && $_GET['until'])	{		/* both FROM and UNTIL arguments present */
		    	while ($row = mysql_fetch_array($result)) 	{
				$datestamp = formatVariable($row['timestamp']);  
				$datestamp = date("Y-m-d", strtotime($datestamp));
				$row[timestamp] = $datestamp;

				if ($row[timestamp] >= $from && $row[timestamp] <= $until)	{
	      				echo "<record>";
		        		echo "<header>";
				       echo "<identifier>".$oaiURL.":".$row['kid']."</identifier>";  
		      			echo "<datestamp>".date("Y-m-d", strtotime($datestamp))."</datestamp>";  
	        			echo "</header>";
	       		 	echo "</record>";	 
				}
			}
	     	}
		else
			if ($_GET['from'] && !$_GET['until'])	{	/* FROM but not UNTIL argument present */
			    	while ($row = mysql_fetch_array($result)) 	{
					$datestamp = formatVariable($row['timestamp']);  
					$datestamp = date("Y-m-d", strtotime($datestamp));
					$row[timestamp] = $datestamp;
	
					if ($row[timestamp] >= $from)	{
	      					echo "<record>";
		      		 		echo "<header>";
					       echo "<identifier>".$oaiURL.":".$row['kid']."</identifier>";  
						echo "<datestamp>".date("Y-m-d", strtotime($datestamp))."</datestamp>";  
		      				echo "</header>";
		       		 	echo "</record>";	 
					}
				}
		     	}
			else
				if (!$_GET['from'] && $_GET['until'])	{	/* UNTIL but not FROM argument present */
				    	while ($row = mysql_fetch_array($result)) 	{
						$datestamp = formatVariable($row['timestamp']);  
						$datestamp = date("Y-m-d", strtotime($datestamp));
						$row[timestamp] = $datestamp;
	
						if ($row[timestamp] <= $until)	{
	      						echo "<record>";
		       		 		echo "<header>";
						       echo "<identifier>".$oaiURL.":".$row['kid']."</identifier>";  
		      					echo "<datestamp>".date("Y-m-d", strtotime($datestamp))."</datestamp>";  
		        				echo "</header>";
			       		 	echo "</record>";	 
						}
					}
		     		}
				else	{
				   	while ($row = mysql_fetch_array($result)) 	{	/* Neither FROM nor UNTIL arguments present; i.e., ALL records requested */
      						echo "<record>";
	        				echo "<header>";
					       echo "<identifier>".$oaiURL.":".$row['kid']."</identifier>";  

						$datestamp = formatVariable($row['timestamp']);  
				      		echo "<datestamp>".date("Y-m-d", strtotime($datestamp))."</datestamp>";  
	       	 			echo "</header>";
       			 		echo "</record>";	 
						flush();
					}
				}
		echo "</".$verb.">";
	}
}

///////////////////////////////////////////////////////////////////////////////
/* Lists all, or some (depending on FROM and UNTIL arguments, if present), header and record information from the database's dublinCore records. */

function listRecords()
{
	global $verb, $oaiURL, $metadataPrefix, $oaiHandlerURL, $from, $until;
	$arrayLegalArguments = array('metadataPrefix','verb','from','until','set','resumptionToken');
	$arrayRequiredArguments = array('metadataPrefix');

	$haveError = errorDisseminateFormats($metadataPrefix, $haveError);
	$haveError = errorIllegalArguments($arrayLegalArguments, $haveError);
	$haveError = errorIllegalDateArguments($from, $until, $haveError);
	$haveError = errorNoRecordsMatch($from, $until, $haveError);   
	$haveError = errorMissingArguments($arrayRequiredArguments, $haveError);
	$haveError = errorSets($haveError);  

	if(!$haveError)
	{
		foreach($_GET as $key => $value)		{	
			echo " ".$key."=\"".$value."\"";
		}
		echo ">".$oaiHandlerURL."</request>";
		echo "<".$verb.">";
		
		$result=mysql_query("SELECT * FROM dublinCore WHERE timestamp IS NOT NULL ORDER BY dublinCore.kid") or die;  

	    	while ($row = mysql_fetch_array($result)) 	{
			$datestamp = formatVariable($row['timestamp']);  
			$datestamp = date("Y-m-d", strtotime($datestamp));
			$row[timestamp] = $datestamp;

			if ($_GET['from'] && $_GET['until']) 	{	/* both FROM and UNTIL arguments present */
				if ($row[timestamp] >= $from && $row[timestamp] <= $until)	{
					constructRecord($row, $oaiURL);
				}
			}
			else
				if ($_GET['from'] && !$_GET['until']) 	{	/* FROM but not UNTIL argument present */
					if ($row[timestamp] >= $from)	{
						constructRecord($row, $oaiURL);
					}
				}
				else
					if (!$_GET['from'] && $_GET['until']) 	{	/* UNTIL but not FROM argument present */
						if ($row[timestamp] <= $until)	{
							constructRecord($row, $oaiURL);
						}
					}
				else	{	/* Neither FROM nor UNTIL arguments present; i.e., ALL records requested */
					constructRecord($row, $oaiURL);
				}
		}

		echo "</".$verb.">";
	}  
}

///////////////////////////////////////////////////////////////////////////////
/* Retrieves an individual metadata record based on the $identifier that's sent as an argument  */

function getRecord($identifier) 	{
	global $verb, $oaiURL, $metadataPrefix, $oaiHandlerURL;

	$arrayLegalArguments = array('identifier','metadataPrefix','verb');
	$arrayRequiredArguments = array('metadataPrefix', 'identifier');

	$identifier = substr($identifier, (strrpos($identifier, ":")) + 1);
	$result=mysql_query("SELECT * FROM dublinCore WHERE kid = '$identifier' ORDER BY dublinCore.kid") or die (mysql_error());  
	$row = mysql_fetch_assoc($result);

	$haveError = errorDisseminateFormats($metadataPrefix, $haveError);
	$haveError = errorIdentifier($row, $haveError);
	$haveError = errorIllegalArguments($arrayLegalArguments, $haveError);
	$haveError = errorMissingArguments($arrayRequiredArguments, $haveError);
	
	if(!$haveError)	{
		foreach($_GET as $key => $value)	{	
			echo " ".$key."=\"".$value."\"";
		}
		echo ">".$oaiHandlerURL."</request>";
		echo "<".$verb.">";
    		constructRecord($row, $oaiURL);
		echo "</".$verb.">";
	}
}

///////////////////////////////////////////////////////////////////////////////
/* Indicates (at this point) that this repository does not support sets.  */

function listSets() {
	global $oaiHandlerURL;	
	echo ">".$oaiHandlerURL."</request>";
	echo "<error code=\"noSetHierarchy\">This repository does not support sets</error>";
}

///////////////////////////////////////////////////////////////////////////////
/* Formatting and display of records.  This function is called once (from getRecord) and multiple times (from listRecord).  Arguments are a record/row from dublinCore ($rowArray) and part of the unique identifier ($oaiURL)    */
 
function constructRecord($rowArray, $oaiURL)
{
    echo "<record>";
            echo "<header>";
                echo "<identifier>".$oaiURL.":".$rowArray['kid']."</identifier>";
	         echo "<datestamp>".date("Y-m-d", strtotime($rowArray['timestamp']))."</datestamp>";	
	      	  echo "</header>";
            	  echo "<metadata>";
                echo " <oai_dc:dc 	xmlns:oai_dc='http://www.openarchives.org/OAI/2.0/oai_dc/'";	
                echo " xmlns:dc='http://purl.org/dc/elements/1.1/'";					
                echo " xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'";    				
                echo " xsi:schemaLocation='http://www.openarchives.org/OAI/2.0/oai_dc/ 	http://www.openarchives.org/OAI/2.0/oai_dc.xsd'>";    

		  // retrieve DC data by looping through each element of the array, stripping XML as we go along
		  $elements = array  (	
					"title" => "title",
					"creator" => "creator",
					"subject" => "subject",
					"description" => "description",		  
					"date" => "dateOriginal",
					"type" => "type",
					"identifier" => "kid",
					"coverage" => "coverage",
					"contributor" => "contributor",
					"format" => "format",
					"publisher" => "publisher",
					"relation" => "relation",
					"rights" => "rights",
					"language" => "language",
					"source" => "source"
		);		

		foreach($elements as $key => $val)       	{
			if ($key != "identifier") 	{	// formatting/stripping/decoding for all Dublin Core fields except kid/identifier field (which is not wrapped in XML)
				$strippedText = formatVariable($rowArray[($elements[$key])]);  
			}
			else  	{	// bypass formatting/stripping for the kid/identifier field
				$strippedText = $oaiURL.$rowArray[($elements[$key])];
			}
				
			if ($key != "date")   { 	// everything but dates 
				if (!strpos($strippedText, "</"))	{	// stripped string has no XML start/end tags: we add them (necessary for XML processing below)   
					$strippedText = "<?xml version='1.0'?><value>".$strippedText."</value>";  
				} 

				$strippedText = simplexml_load_string($strippedText);  
				$result = $strippedText->xpath('//value');
	
				foreach($result as $value)  {
					echo "<dc:".$key.">".$value."</dc:".$key.">";
				}
			}		
			else   {	// dates get special processing
				$strippedText = "<?xml version='1.0'?><value>".$strippedText."</value>";    

				$strippedText = htmlspecialchars_decode($strippedText);  
				$strippedText = simplexml_load_string($strippedText);  
				$result = $strippedText->xpath('//month|//day|//year|//era');   

				echo "<dc:".$key.">";  
				foreach($result as $value)  {
					if (!preg_match("/^0$/", $value))   // don't display zeros     
						echo $value." ";
				}  
				echo "</dc:".$key.">";      
			}    
                }  
                echo "</oai_dc:dc>";
       	echo "</metadata>";
       echo "</record>";
	flush();	
}

///////////////////////////////////////////////////////////////////////////////
/* formatting/stripping/decoding for XML display, takes a single string (from the dublinCore table) as an argument   */

function formatVariable($unStrippedVariable) {			
	$strippedVariable = substr($unStrippedVariable, 0, strpos($unStrippedVariable, "</"));  		// find innermost tag
	$strippedVariable = substr($strippedVariable, (strrpos($strippedVariable, ">")) + 1);		// returns string between control number tags
	$strippedVariable = str_replace("&lt;?xml version=\\\"1.0\\\"?&gt;", "", $strippedVariable);  // strips extraneous XML header
	$strippedVariable = str_replace("&amp;lt;?xml version=\"1.0\"?&amp;gt;", "", $strippedVariable);  // strips extraneous XML header     
	$strippedVariable = trim($strippedVariable, "'");
	$strippedVariable = str_replace("\\n", "", $strippedVariable);   
	$strippedVariable = stripslashes($strippedVariable);   
	$strippedVariable = htmlspecialchars_decode($strippedVariable);  
	$strippedVariable = preg_replace("/&(?!lt|gt)/", "&amp;amp;", $strippedVariable);  // deal with ampersands
	return $strippedVariable;
}

///////////////////////////////// ERROR HANDLING FUNCTIONS /////////////////////////////////
/* checks for strings that are not part of any of the OAI-PMH verbs' argument sets; takes an array of LEGAL arguments and the BOOLEAN $haveError variable as parameters. */

function errorIllegalArguments($arrayLegalArguments, $haveError) {
	global $oaiHandlerURL;
 	foreach($_GET AS $key => $value) 	{
		if (!in_array($key, $arrayLegalArguments)) 	{
			if (!$haveError)	{
				echo ">".$oaiHandlerURL."</request>";
			}
			echo "<error code=\"badArgument\">Request includes illegal arguments or is missing required arguments</error>";	
			return true;
		}	
	}
	if ($haveError)
		return true;
}

///////////////////////////////////////////////////////////////////////////////
/* checks for errors in the FROM and UNTIL date arguments; takes the FROM (begin) and UNTIL (end) dates and the BOOLEAN $haveError variable as parameters. */

function errorIllegalDateArguments($from, $until, $haveError) {
	global $oaiHandlerURL;

	if ($from)	{
		if (preg_match("/[^0-9\-]/", $from))	{   
			if (!$haveError)	{
				echo ">".$oaiHandlerURL."</request>";
			}
			echo "<error code=\"badArgument\">Request includes illegal arguments or is missing required arguments</error>";
			$haveError = true;  
		}  

		$fromDates = explode("-", $from);
		if (!checkdate($fromDates[1], $fromDates[2], $fromDates[0]))	{
			if (!$haveError)	{
				echo ">".$oaiHandlerURL."</request>";
			}
			echo "<error code=\"badArgument\">Request includes illegal arguments or is missing required arguments</error>";	
			$haveError = true;
		} 
	}
	if ($until)	{
		if (preg_match("/[^0-9\-]/", $until))	{   
			if (!$haveError)	{
				echo ">".$oaiHandlerURL."</request>";
			}
			echo "<error code=\"badArgument\">Request includes illegal arguments or is missing required arguments</error>";
			$haveError = true;  
		}  

		$untilDates = explode("-", $until);
		if (!checkdate($untilDates[1], $untilDates[2], $untilDates[0]))	{
			if (!$haveError)	{
				echo ">".$oaiHandlerURL."</request>";
			}
			echo "<error code=\"badArgument\">Request includes illegal arguments or is missing required arguments</error>";	
			$haveError = true;
		}

	}
	if ($from && $until){
		if ($from>$until){
			if (!$haveError)	{
				echo ">".$oaiHandlerURL."</request>";
			}
			echo "<error code=\"badArgument\">Request includes illegal arguments or is missing required arguments</error>";	
			$haveError = true;
		}
	}
		
	if ($haveError)
		return true;
}

///////////////////////////////////////////////////////////////////////////////
/* checks for any missing required arguments; takes an array of arguments and the BOOLEAN $haveError variable as parameters. */

function errorMissingArguments($arrayRequiredArguments, $haveError) {
	global $oaiHandlerURL;
	foreach($_GET as $key => $value)	
		$arrayArguments[] = $key;
 	
	foreach($arrayRequiredArguments AS $key => $value) 	{
		if (!in_array($value, $arrayArguments))  {
			if (!$haveError)	{
				echo ">".$oaiHandlerURL."</request>";
			}
			echo "<error code=\"badArgument\">Request includes illegal arguments or is missing required arguments".$test."</error>";	
			return true;
		}
	}
	if ($haveError)
		return true;
}

///////////////////////////////////////////////////////////////////////////////
/* checks for errors in the FROM and UNTIL date arguments; takes the FROM (begin) and UNTIL (end) dates and the BOOLEAN $haveError variable as parameters. */

function errorIdentifier($row, $haveError) {
	global $oaiHandlerURL;
	if(!$row && $_GET['identifier'])		{
		if (!$haveError)	{
			echo ">".$oaiHandlerURL."</request>";
		}
		echo "<error code=\"idDoesNotExist\">No matching identifier in MATRIX:KORA</error>";
		return true;
	}
	if ($haveError)
		return true;
}

///////////////////////////////////////////////////////////////////////////////
/* checks to see if a SET is requested (and returns message if one is); takes the BOOLEAN $haveError variable as parameter. */

function errorSets($haveError) {
	if ($_GET['set']) 	{
		if (!$haveError)	{
			echo ">".$oaiHandlerURL."</request>";
		}
		echo "<error code=\"noSetHierarchy\">This repository does not support sets</error>";
		return true;
	}
	if ($haveError)
		return true;
}

///////////////////////////////////////////////////////////////////////////////
/* checks for non-supported metadata formats (currently anything but OAI_DC); takes the metadata prefix in question and the BOOLEAN $haveError variable as parameters. */

function errorDisseminateFormats($metadataPrefix, $haveError)  {
	global $oaiHandlerURL;
	if ($metadataPrefix != "oai_dc" && $metadataPrefix != "") 	{
		if (!$haveError)	{
			echo ">".$oaiHandlerURL."</request>";
		}
		echo "<error code=\"cannotDisseminateFormat\">Cannot disseminate this metadata format</error>";
		return true;
	}
	if ($haveError)
		return true;
}

///////////////////////////////////////////////////////////////////////////////
/* checks to see if there are any matching records (and errors if there aren't); takes the FROM (begin) and UNTIL (end) dates and the BOOLEAN $haveError variable as parameters. */

function errorNoRecordsMatch($from, $until, $haveError)	{
	$recordFound = FALSE;
	$result=mysql_query("SELECT * FROM dublinCore WHERE timestamp IS NOT NULL ORDER BY dublinCore.kid") or die;
	$row = mysql_fetch_array($result);

	$num_rows = mysql_num_rows($result);

	if ($_GET['from'] && $_GET['until'])	{
		while ($row = mysql_fetch_array($result)) 	{
			$datestamp = formatVariable($row['timestamp']);  
			$datestamp = date("Y-m-d", strtotime($datestamp));
			$row[timestamp] = $datestamp;

			if ($row[timestamp] >= $from && $row[timestamp] <= $until)	{
				$recordFound = TRUE;
			}
		}
     	}
	else
		if ($_GET['from'] && !$_GET['until'])	{
		    	while ($row = mysql_fetch_array($result)) 	{
				$datestamp = formatVariable($row['timestamp']);  
				$datestamp = date("Y-m-d", strtotime($datestamp));
				$row[timestamp] = $datestamp;

				if ($row[timestamp] >= $from)	{
					$recordFound = TRUE;
				}
			}
	     	}
		else
			if (!$_GET['from'] && $_GET['until'])	{
			    	while ($row = mysql_fetch_array($result)) 	{
					$datestamp = formatVariable($row['timestamp']);  
					$datestamp = date("Y-m-d", strtotime($datestamp));
					$row[timestamp] = $datestamp;

					if ($row[timestamp] <= $until)	{
						$recordFound = TRUE;
					}
				}
	     		}
			else	// from and until arguments are not present
				if ($num_rows > 0)	{															 
					$recordFound = TRUE;
				}

	if (!$recordFound) 	{
		if (!$haveError)	{
			echo ">".$oaiHandlerURL."</request>";
		}
		echo "<error code=\"noRecordsMatch\">No records match this list request</error>";
		return true;
	}
	if ($haveError)
		return true;   
}
?>
