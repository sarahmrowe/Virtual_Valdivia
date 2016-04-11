<?php
/*
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

// Initial Version: Nicole Lawrence, 2014

require_once('includes/includes.php');
Manager::AddJS('javascripts/plugin.js', Manager::JS_CLASS); 

Manager::Init();

Manager::PrintHeader();

echo "<h2>".gettext('Plugin Settings')."</h2>\n";
?>
	<table class="table"> 
		<tr>
			<th align="left" width="150px"><?php echo gettext('Plugin Name');?></th>
			<th align="left"><?php echo gettext('Description');?></th>
			<th align="left" width="100px"><?php echo gettext('Active');?></th>
			<th align="left" width="100px"><?php echo gettext('Edit');?></th>
		</tr>
<?php
Plugin::DetectNewPlugins();

$listOfPlugins = Plugin::SelectAllPlugins();

for($i = 0; $i < count($listOfPlugins); ++$i)
{
	echo "<tr>";
		echo "<td class='plugin_name'>";
			echo $listOfPlugins[$i]['pluginName'];
		echo "</td>";
		echo "<td style='word-wrap: break-word; overflow: auto;'>";
			echo $listOfPlugins[$i]['description'];
		echo "</td>";
		echo "<td style='text-align:center;'>";
		if($listOfPlugins[$i]['enabled'] == 0)
		{
?>
			<a href="#" id="<?php echo $i; ?>" class="plugin_submit">Activate</a>
<?php	
		}
		else if ($listOfPlugins[$i]['enabled'] == 1)
		{
?>
			<a href="#" id="<?php echo $i; ?>" class="plugin_submit">Deactivate</a>
<?php
		}
		echo "</td>";
		echo '<td><div style="text-align:center;"><a id="'. $i .'" class="edit_plugin">'.gettext('Edit');'</a></div></td>';
	echo "</tr>";
}
	echo "</table>";
	
Manager::PrintFooter();
?>