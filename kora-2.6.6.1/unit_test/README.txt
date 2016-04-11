!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
!!!!!WARNING!!!!!!!!!!!!!!!!!!!!!!!!!!!!
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
!These unit tests are out of date and  !
!should be restructured for the new 2.5! 
!code base                             !
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

KORA Unit Test by Maurice Wong

Initial setup by Maurice Wong

To run the tests:

1) Be sure to have PHPUnit installed.

2) conf.php must be configured correctly.

3) p99Data table must be created to test Controls. Inside the "sqlCommands" folder, there is a sql file named p99Data which contains a query to make the table with the necessary dummy data. Change necessary information (such as database) in .sql files.

4) The unit tests for KORA_Search function is set to search in project 18 scheme 57, there are sql files named p18Data and p18Control inside the "sqlCommands" folder, both contain their own query to create the tables and dummy data. Also note that using KORA_Search requires an authentication token, which must be changed in KoraSearchUnitTest.php in the KoraSearchTest class. Change necessary information (such as database) in .sql files.

5) Other changes may apply.


koraAllTest.php is the master suite for the unit tests. Testing this file tests all test suites.
header.php has all the necessary header files to run korAllTest.php.
functions.php has sql queries that are used when testing Controls (truncate table, etc.)
