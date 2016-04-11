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

require_once("apiutils.php");
///<kora_api>
///<kora_api_call title="Search Projects">
///  <description>
///    Returns a list of all active projects in the Kora database.
///  </description>
///  <arguments />
///  <result_fields>
///    <field type="integer" name="pid" description="Unique project ID" />
///    <field type="string" name="name" description="Project name" />
///    <field type="string" name="description" description="Project description" />
///  </result_fields>
///  <examples>
///    <example url_stub="[...]&target=projects">
///      <result>
///        <results>
///          <result>
///            <pid>4</pid>
///            <name>Sample Project</name>
///            <description>This is the project description</description>
///          </result>
///        </results>
///      </result>
///    </example>
///  </examples>
///</kora_api_call>
function kora_api_call_search_projects()
{
    global $db;
    $qstr = "SELECT pid, name, description FROM `project`";
    $q = $db->query($qstr) or XMLMySQLErrorAndDie($db->error);
    XMLDumpQueryResults($q);
    $q->close();
}

///<kora_api_call title="Search Schemes">
///  <description>
///    Returns a list of all schemes for a given project.
///  </description>
///  <arguments>
///    <argument name="pid" description="Project ID to search" required="true" />
///  </arguments>
///  <result_fields>
///    <field type="integer" name="sid" description="Unique scheme ID" />
///    <field type="string" name="name" description="Scheme name" />
///    <field type="string" name="description" description="Scheme description" />
///  </result_fields>
///  <examples>
///    <example url_stub="[...]&target=schemes&pid=4">
///      <result>
///        <results>
///          <result>
///            <sid>11</sid>
///            <name>Person</name>
///            <description>This scheme describes a person</description>
///          </result>
///        </results>
///      </result>
///    </example>
///  </examples>
///</kora_api_call>
function kora_api_call_search_schemes()
{
    global $db;
    $pid = mysql_escape_string($_GET['pid']);
    $qstr = "SELECT schemeid as sid, schemeName as name, description FROM `scheme` WHERE `pid`=$pid";
    $q = $db->query($qstr) or XMLMySQLErrorAndDie($db->error);
    XMLDumpQueryResults($q);
    $q->close();
}

///<kora_api_call title="Search Controls">
///  <description>
///    Returns a list of all controls for a given scheme.
///  </description>
///  <arguments>
///    <argument name="pid" description="Project ID of scheme" required="true" />
///    <argument name="sid" description="Scheme ID to search" required="true" />
///  </arguments>
///  <result_fields>
///    <field type="integer" name="cid" description="Unique control ID" />
///    <field type="string" name="type" description="The KORA Control type" />
///    <field type="string" name="name" description="Control name" />
///    <field type="string" name="description" description="Control description" />
///    <field type="boolean" name="searchable" description="Determines whether or not the control represents values that can be searched" />
///  </result_fields>
///  <examples>
///    <example url_stub="[...]&target=controls&pid=4&sid=11">
///      <result>
///        <results>
///          <result>
///            <cid>3</cid>
///            <type>TextControl</type>
///            <name>Color</name>
///            <description>This control stores a person's favorite color</description>
///            <searchable>1</searchable>
///          </result>
///        </results>
///      </result>
///    </example>
///  </examples>
///</kora_api_call>
function kora_api_call_search_controls()
{
    global $db;
    $sid = mysql_escape_string($_GET['sid']);
    $pid = mysql_escape_string($_GET['pid']);
    $qstr = "SELECT cid, type, name, description, searchable FROM `p{$pid}Control` WHERE `schemeid`=$sid AND `showInResults`=1";
    $q = $db->query($qstr) or XMLMySQLErrorAndDie($db->error);
    XMLDumpQueryResults($q);
    $q->close();
}


///<kora_api_call title="Search Data">
///  <description>
///    Search project data. When called without "where_" arguments, returns the full
///    data set.
///    Optionally, you can specify "where_" arguments (examples below) and join them togethor
///    using AND or OR by "join_" arguments. If you supply more than one "where_" argument,
///    you MUST specify "join_"s to properly link them, and you MUST give the last join
///    a label of "final."
///  </description>
///  <arguments>
///    <argument name="pid" description="Project ID of scheme" required="true" />
///    <argument name="sid" description="Scheme ID to search" required="true" />
///    <argument name="where_{control_name}_{operand}[_as_{label}]"
///              description="Variable search parameters. See below for usage examples.
///                   {operand} can be 'is', 'equals', 'gt', 'ge', 'lt', 'le' 'notequals', 'like', or 'notlike'. 'in' can also be used, but only when referring to KID (for the 'in' clause, seperate multiple KIDs in value field by an underscore)
///                   {control_name} is the name of any control from the current project and schema with 'searchable' set to true.
///                   {label} is the clause name used to assemble complex WHERE queries; see below for examples."
///                   required="false" />
///    <argument name="join_{labelA}_{labelB}_as_{newLabel}"
///              description="Joins {labelA} and {labelB} togethor using the value that you pass, and stores the result as {newLabel}.
///                    Acceptable values are 'and' and 'or'." required="false"/>
///  </arguments>
///  <result_fields>
///    <field type="string" name="KID" description="Data field ID" />
///    <field type="string" name="{control_name}" description="The value corresponding to the name of the control" />
///  </result_fields>
///  <examples>
///    <example url_stub="[...]&target=data&pid=4&sid=11">
///      <result>
///        <results>
///          <result>
///            <kid>4-B-AE</kid>
///            <Color>Blue</Color>
///          </result>
///          <result>
///            <kid>4-B-AF</kid>
///            <Color>Green</Color>
///          </result>
///          <result>
///            <kid>4-B-B0</kid>
///            <Color>Yellow</Color>
///          </result>
///        </results>
///      </result>
///    </example>
///    <example url_stub="[...]&target=data&pid=4&sid=11&where_KID_is=4-B-AE">
///      <result>
///        <results>
///          <result>
///            <kid>4-B-AE</kid>
///            <Color>Blue</Color>
///          </result>
///        </results>
///      </result>
///    </example>
///    <example url_stub="[...]&target=data&pid=4&sid=11&where_KID_is_as_labelA=4-B-AE&where_Color_like_as_labelB=%ree%&join_labelA_labelB_as_final=or">
///      <result>
///        <results>
///          <result>
///            <kid>4-B-AE</kid>
///            <Color>Blue</Color>
///          </result>
///          <result>
///            <kid>4-B-AF</kid>
///            <Color>Green</Color>
///          </result>
///        </results>
///      </result>
///    </example>
///  </examples>
///</kora_api_call>
function kora_api_call_search_data()
{
    global $db;
    global $TOKEN;
    $sid = mysql_escape_string($_GET['sid']);
    $pid = mysql_escape_string($_GET['pid']);
    
    # Boolean flags
    $searching = false; # == true if "wheres" have been found
    $joining = false; # == true if "joins" have been found
    $canjoin = true; # == false if any "where" clause is present without an _id
    
    # Arrays to parse searching strings
    $wheres = array(); # All the where clauses, indexed by labels
    $joiners = array(); # How to join the where clauses passed
    $validcompares = array("is" => "=", "not" => "!=", "notlike" => "NOT LIKE", "equals" => "=", "like" => "LIKE", "gt" => ">", "ge" => ">=", "lt" => "<", "le" => "<=", "in" => "IN"); # valid comparisons
    $validjoins = array("and" => "AND", "or" => "OR");
    
    # Build the "wheres" array
    foreach($_GET as $key=>$value)
    {
        $a = explode("_", $key);
        # if it's in the format "where_cid_operand[_as_label]=value"     
        if($a[0] == "where" && isset($a[1]) && isset($a[2])
            && isset($validcompares[$a[2]]) && $validcompares[$a[2]]) 
        {
            $searching = true;
            $compare = $validcompares[$a[2]];
            if($compare != "IN")
	    {
	      // replace - with space in variable name
              $clause = new KORA_Clause(mysql_escape_string(str_replace('-', ' ', $a[1])), $compare , mysql_escape_string($value));
            }
            else
            {
              $clause = new KORA_Clause(mysql_escape_string(str_replace('-', ' ', $a[1])), $compare , explode("_", mysql_escape_string($value)));
            }
            # if _as_$label, store it as the label
            if(sizeof($a) == 5 && strtolower($a[3]) == "as")
            {
                $label = $a[4];
            }
            else
            {
                $label = "final";
                $canjoin = false; #don't join unless all where clauses have an ID
            }
            
            $wheres[$label] = $clause;
        }
    }
    
    # Build the joins
    if($canjoin)
    {
        $tryagain = true;
        while($tryagain)
        {
          $tryagain = false;
          foreach($_GET as $key=>$value)
          {
              $undefinedterms = false;
              $a = explode("_", $key);
              if($a[0] == "join" && sizeof($a) == 5 && $a[3] == "as")
              {
                  $joining = true;
                  if(array_key_exists($a[1], $wheres) && array_key_exists($a[2], $wheres))
                    $wheres[$a[4]] = new KORA_Clause($wheres[$a[1]], $validjoins[$value], $wheres[$a[2]]);
                  else
                    $undefinedterms = true;
              }
              if($undefinedterms)
                $tryagain = true;
          }
        }
    }
    
    

    if(!$searching)
    {
        $where = new KORA_Clause("KID","LIKE","%");
    }
    else if($searching && isset($wheres['final']))
    {
        $where = $wheres['final'];
    }
    else
    {
        XMLDumpErrorAndDie('improper_where_clauses');
    }
    XMLHeader();

    echo "<results>";
    $results = KORA_Search($TOKEN, $pid, $sid, $where, array('ALL'));
    foreach ($results as $key => $record)
      echo "<result>" . ArrayToOutputXml($record) . "</result>\n";
    echo "</results>";
}
///</kora_api>
?>
