<?php
// mysql example
define('DB_HOSTNAME', $dbhost); // database host name
define('DB_USERNAME', $dbuser);     // database user name
define('DB_PASSWORD', $dbpass); // database password
define('DB_NAME', $dbname); // database name     
define('DB_TYPE', 'mysql');  // database type
define('DB_CHARSET','utf8'); // ex: utf8(for mysql),AL32UTF8 (for oracle), leave blank to use the default charset

// postgres example
//define('DB_HOSTNAME','localhost'); // database host name
//define('DB_USERNAME', 'postgres');     // database user name
//define('DB_PASSWORD', '1234'); // database password
//define('DB_NAME', 'sampledb'); // database name     
//define('DB_TYPE', 'postgres');  // database type
//define('DB_CHARSET','');

// mssql server example
//define('DB_HOSTNAME','host_name.mydomain.com'); // database host name
//define('DB_USERNAME', 'root');     // database user name
//define('DB_PASSWORD', ''); // database password
//define('DB_NAME', 'sampledb'); // database name     
//define('DB_TYPE', 'odbc_mssql');  // database type
//define('DB_CHARSET','');

// oracle server example
//define('DB_HOSTNAME','oracledb.mydomain.com'); 
//define('DB_USERNAME', 'oracleuser');     // database user name
//define('DB_PASSWORD', ''); // database password
//define('DB_NAME', 'sampledb'); // database name     
//define('DB_TYPE', 'oci805');  // database type
//define('DB_CHARSET','AL32UTF8');

// sqlite server example
//define('DB_HOSTNAME','c:\path\to\sqlite.db'); // database host name
//define('DB_USERNAME', '');     // database user name
//define('DB_PASSWORD', ''); // database password
//define('DB_NAME', ''); // database name     
//define('DB_TYPE', 'sqlite');  // database type
//define('DB_CHARSET','');


define('SERVER_ROOT', 'http://dev2.matrix.msu.edu/~joseph.deming/kora/includes/grid');

/******** DO NOT MODIFY ***********/
require_once('phpGrid.php');     
/**********************************/
?>
