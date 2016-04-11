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

require_once(__DIR__.'/../includes/includes.php');
require_once('../model/plugin.php');

Manager::Init();

$action = $_REQUEST['action'];
$id = $_REQUEST['id'];

// If we are activating or deactivating a plugin
if($action == 'activation'){

	//Get a list of all the plugins
	$listOfPlugins = Plugin::SelectAllPlugins();

	//Get the plugin name of the plugin we want to activate/deactivate
	$pluginName = $listOfPlugins[$id]['pluginName'];
	
	//Actually doing the activate or deactivate
	if ($listOfPlugins[$id]['enabled'] == 0){
		//See if the plugin has an install.php file, if so run that file.
		$isInstall = Plugin::IsInstall($listOfPlugins[$id]['fileName']);

		if($isInstall){
			require_once basePath.'plugins/'.$listOfPlugins[$id]['fileName'].'/install.php';
		}

		Plugin::EnablePlugin($pluginName);
		Plugin::UpdatePlugin($pluginName);
	}
	else if ($listOfPlugins[$id]['enabled'] == 1){
		Plugin::DisablePlugin($pluginName);
	}
}
else if ($action == 'Editplugin'){	
	//Get a list of all the plugins
	$listOfPlugins = Plugin::SelectAllPlugins();
	
	//Get the id and then the plugin name of the plugin we want to edit
	$pluginName = $listOfPlugins[$id]['pluginName'];
	
	Plugin::PrintEditPlugin($pluginName, $id);
}

else if ($action == 'submitDescription'){
	$description = $_REQUEST['description'];
	
	//Get a list of all the plugins
	$listOfPlugins = Plugin::SelectAllPlugins();
	
	//Get the id and then the plugin name of the plugin we want to edit
	$pluginName = $listOfPlugins[$id]['pluginName'];

	Plugin::UpdateDescription($pluginName, $description);
}
?>