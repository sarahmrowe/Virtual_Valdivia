<?php

include_once('/usr/share/php/PHPUnit/Framework.php');
require_once '/usr/share/php/PHPUnit/Extensions/Database/TestCase.php';

include_once ('controls/imageControl/imageControlTest.php');
include_once ('controls/fileControl/fileControlTest.php');
//include_once ('controls/associatorControl/associatorControlTest.php');
include_once ('controls/dateControl/dateControlTest.php');
include_once ('controls/textControl/textControlTest.php');
include_once ('controls/geolocatorControl/geolocatorControlTest.php');
include_once ('controls/listControl/listControlTest.php');
include_once ('controls/multiDateControl/multiDateControlTest.php');
include_once ('controls/multiListControl/multiListControlTest.php');
include_once ('controls/multiTextControl/multiTextControlTest.php');
include_once ('includes/clientUtilities_unittest.php');
include_once ('includes/clientUtilities_unittest_database.php');
include_once ('controls/multiTextControl/multiTextControlTest.php');

//include_once ('includes/koraSearch/koraSearch_unittest.php');
include_once ('includes/koraSearch/koraClause_unittest.php');

include_once ('includes/koraSearch/KoraSearchUnitTest.php');
include_once ('includes/koraSearch/joinKoraClauseTest.php');

include_once('functions.php');

//include_once ('controls/associatorControl/associatorControlTest.php');
include_once (basePath.'includes/koraSearch.php');

include_once ('controls/listControl/ListControlDBTestMySQL.php');
include_once ('controls/dateControl/DateControlDBTestMySQL.php');
include_once ('controls/textControl/TextControlDBTestMySQL.php');
include_once ('controls/fileControl/FileControlDBTestMySQL.php');
include_once ('controls/multiTextControl/multiTextControlDBTestMySQL.php');
include_once ('controls/geolocatorControl/GeolocatorControlDBTestMySQL.php');
include_once ('controls/imageControl/ImageControlDBTestMySQL.php');
include_once ('controls/multiDateControl/MultiDateControlDBTestMySQL.php');
include_once ('controls/multiListControl/MultiListControlDBTestMySQL.php');

?>
