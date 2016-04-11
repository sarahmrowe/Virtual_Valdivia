<?php

$dbuser = "";                // the user to connect to the database
$dbpass = "";                // the password to connect to the database
$dbname = "";                // the database to connect to
$dbhost = "";   // the server the database is on

// The Root URL for this KORA installation - trailing slash required
define('baseURI', '');
// The Root (server) Path for this KORA installation - trailing slash required
define('basePath', '');
// The E-Mail address that Activation E-Mails should be listed as being from
define('baseEmail', 'kora@');
// The subdirectory files should be stored in
define('fileDir', 'files/');
// The subdirectory extracted files are stored in temporarily
define('extractFileDir', fileDir.'extractedFiles/');
// the subdirectory that files waiting for approval by a moderator are stored in
define('awaitingApprovalFileDir', fileDir.'awaitingApproval/');
// the number of seconds before the session times out and users are forced to log in again.
define('sessionTimeout',30*60);
//public key - Recaptcha
define('PublicKey','' );
//private key - Recaptcha
define('PrivateKey','');

// the number of results in a single search results page
define('RESULTS_IN_PAGE', 10);

// the URL for Solr
define('solr_url', '');
// whether or not Solr is enabled
$solr_enabled = false;


require_once("includes.php");

?>
