<?php
/*
 * The following functions are used to test classes or functions that use a database.
 */

/*
 * $query truncates the test table
 * Passes database variable
 */
    function truncateTable($unitDB, $table)
    {
    	$query = "TRUNCATE $table";
    	$unitDB->query($query);
    }

/*
 * Drop table
 */

    function dropTable(PDO $unitDB, $table)
    {
    	$query = "DROP TABLE $table";
    	$unitDB->query($query);
    }

/*
 * $query creates the table needed to unit test
 */
    function createTable(PDO $unitDB, $table)
    {
    	$query = "
            CREATE TABLE $table (
            	id VARCHAR(30) PRIMARY KEY,
                cid INT(10) UNSIGNED,
                schemeid INT(10) UNSIGNED,
                value LONGTEXT
            );
        ";

        $unitDB->query($query);
    }


/*
 * check to see if table exists
 */
	function table_exists ($table, $unitDB)
	{
		$query = "SELECT * FROM $table";

		$results = $unitDB->query($query);

		if (empty($results))
		{
			return false;
		}
		else
		{
			return true;
		}
	}


/*
 * Reconnect to the database.
 * Quick fix for disconnecting
 * issue for unit testing
 * functions with databases.
 */
	function reconnectToDatabase()
	{
		global $db, $dbhost, $dbuser, $dbpass, $dbname;
		$db = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	}
?>
