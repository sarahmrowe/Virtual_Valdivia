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

// Initial Version: Matt Geimer, 2008

/**
 * addFixityItem adds a file to the fixity table and performs the initial hash.  This should be called only by things that do ingestion in general.
 *
 * @param unknown_type $kid
 * @param integer $cid
 * @param string $path
 * @return integer 1 for success and 0 for failure
 */

function addFixityItem($kid, $cid, $path) {
   global $db;
   if($path && $kid && $cid) {  #make sure there is some path and KID and CID defined 
      $query = 'SELECT * FROM fixity WHERE kid="'.$kid.'" AND cid="'.$cid.'" LIMIT 1';
      $result = $db->query($query);
      if($result->num_rows != 0) { //pre-existing, means need to update initialTime and initialHash
         $query = 'UPDATE fixity SET initialHash='.escape(hash_file(HASH_METHOD,$path)).', initialTime=NOW(), path='.escape($path).' WHERE kid='.escape($kid).' AND cid='.escape($cid);
      } else {
        $query = 'INSERT INTO fixity(kid,cid,initialHash,initialTime,path) VALUES ( '.escape($kid).','.escape($cid).','.escape(hash_file(HASH_METHOD,$path)).',NOW(),'.escape($path).' )';
      }
      $db->query($query);
      return 1;
   }
   return 0;
}

/**
 * removeFixityItem deletes an item from the fixity table.  This should only be done by the code that deletes objects or if archival is set to false
 * after being previously set to true AND the user agrees that this will happen.  This will invalidate object integrity.
 *
 * @param unknown_type $kid
 * @param integer $cid
 * @return integer 1 for success and 0 for failure
 */

function removeFixityItem($kid, $cid) {
   global $db;
   if($kid && $cid ) {
      $query = "DELETE FROM fixity WHERE kid=".escape($kid)." AND cid=".escape($cid);
      $db->query($query);
      return 1;
   }
   return 0;
}

function runFixityCheck() {
   if(!Manager::IsSystemAdmin()) {
      return -1;
   }
   global $db;
   $results = $db->query("SELECT kid,cid,path,initialTime,initialHash,computedHash,computedTime FROM fixity");
   $errorCount = 0;
   $recordCount = 0;
   $message = '';
   while($record = $results->fetch_assoc()) {       //make the assumption that there are no path issues
      $recordCount++;
      $error = false;
      $currHash = hash_file(HASH_METHOD,$record['path']);
      
      if($currHash != $record['initialHash']) {
         // PROBLEM - file changed from initial hash - figure out how bad it was
         if($record['computedHash'] && $currHash != $record['computedHash'] && $record['computedHash'] == $record['initialHash']) {
            $message .= "\r\n ".gettext('File')." $record[path] ".gettext('changed from last hash check')."! \r\n ".gettext('Last Computed').": $record[computedHash] \r\n ".gettext('Current Computed').": $currHash \r\n";
            $message .= gettext('Last Computed Hash Time').": $record[computedTime]  ".gettext('Current Computed Hash Time').": ".date('r');
         }
         else if(!$record['computedHash']) {
            $message .= "\r\n ".gettext('File')." $record[path] ".gettext('changed from initial hash')."! \r\n ".gettext('Initial').": $record[initialHash] \r\n ".gettext('Computed').": $currHash \r\n";
            $message .= gettext('Original Hash Time').": $record[initialTime]  ".gettext('Current Hash Time').": ".date('r');
         }
         else {
            //THIS SHOULD NEVER EVER HAPPEN - this implies that the initial hash and the computed hash stored don't match.  Shouldn't have happpened.
            $message .= '\r\n'.gettext('Something that should never happen did.  The original hash and stored hash in the database differ. Check this file for errors').': '.$record[path];
         }
         $errorCount++;
      }
      
      if(!$error){
         #this is really crappy - but no real other way to do it at current
         $query = "UPDATE fixity SET computedHash='$currHash',computedTime=NOW() WHERE kid=".escape($record['kid'])." AND cid=".escape($record['cid']);
         $db->query($query);
      }
   }
   $headers = '';
   $headers .= "MIME-Version: 1.0\r\n";
   $headers .= "Content-type: text/html\r\n";
   $headers .= "From: \"KORA Fixity\" <".baseEmail.">\r\n";
   $headers .= "To: ".adminEmail."\r\n";
   $headers .= "Reply-To:".baseEmail."\r\n";
   $messageSubject = gettext('KORA Fixity Run Results');
   if($errorCount) {
      //send email about errors and return
      $messageSubject = "$errorCount ".gettext('fixity errors')." - " . $messageSubject;
      $mailSuccess = mail(adminEmail, $messageSubject, $message, $headers);
      return 0;
   }
   //send happy complete email and return
   $messageSubject = "$recordCount ".gettext('files passed')." - " . $messageSubject;
   $mailSuccess = mail(adminEmail, $messageSubject, $message, $headers);
   return 1; 
}


?>
