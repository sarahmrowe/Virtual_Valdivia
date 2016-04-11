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
// Refactor: Joe Deming, Anthony D'Onofrio 2013

require_once('includes/includes.php');

Manager::Init();

Manager::RequireSystemAdmin();

Manager::PrintHeader();

echo '<h2>'.gettext('Manage Users').'</h2>';
?>
<div id="ajaxstatus"></div>
<div id="apmanageusers"></div>
<?php 
Manager::PrintFooter();
?>

