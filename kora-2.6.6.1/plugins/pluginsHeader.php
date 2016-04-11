<?php
    include_once(basePathPlugin.'includes/utilities.php');
	
    $length = strlen(basePath);
    if( !empty($_SESSION['uid']) && (empty($_SESSION['base_Path']) || $_SESSION['base_Path']!=substr($_SERVER['SCRIPT_FILENAME'],0,$length)) ){
		$_SESSION = array();    // unset all session variables
		session_destroy();
		header('Location: accountLogin.php');
		die();
	}
	
    if ( ! headers_sent() ) { header('Content-Type: text/html; charset=utf-8'); }
    
// Initial Version: Brian Beck, 2008
// Refactor: Joe Deming, Anthony D'Onofrio 2013
    

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<!-- Copyright (2008) Matrix: Michigan State University

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
 -->
    <head>
        <title>KORA</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta http-equiv="Pragma" content="no-cache" />
	<?php //Manager::PrintCSS() ?>
		
<link rel="shortcut icon" type="image/x-icon" href="<?php echo baseURI;?>favicon.ico">
    </head>
    <body>
		<div id="container_main">
		<div id="container">
		<div id="header">
		<?php Manager::PrintLoginDiv(); ?>
		<div id="content_container">
<?php require(basePathPlugin.'plugins/pluginsMenu.php'); ?>
<div id="right_container"><div id="right">
<?php

Manager::CheckDatabaseVersion();
Manager::PrintBreadcrumbs();

print "<div id='global_error'></div>";
?>
