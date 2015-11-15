<?php
/**
 * Diesel Framework
 * Copyright © LQDI Technologies - 2011
 * http://www.lqdi.net
 *
 * @author Aryel Tupinambá
 */

// error reporting setting
error_reporting(E_ALL ^ E_NOTICE);

if(!defined('DF3_PATH')) {
    define('DF3_PATH', './');
}

define('DF3_VERSION', '3.0a');

include(DF3_PATH . "global_functions.php");

include(DF3_PATH . "core/ErrorHandler.php");
include(DF3_PATH . "core/Utils.php");
include(DF3_PATH . "core/Log.php");
include(DF3_PATH . "core/Router.php");
include(DF3_PATH . "core/ModuleManager.php");
include(DF3_PATH . "core/Diesel.php");

$config = array();

define('APP_PATH', 'app/');
define('LIB_PATH', 'lib/');

include(APP_PATH . "app.config.php");

define("DIESEL_CONFIG_LOADED", true);

// Registers all required modules
if(is_array($config['REQUIRED_MODULES'])) {
	foreach($config['REQUIRED_MODULES'] as $requiredModule) {
		if(file_exists("modules/{$requiredModule}/mod.config.php")) {
			include("modules/{$requiredModule}/mod.config.php");
		}
	}
}

// Loads all required libraries
if(is_array($config['REQUIRED_LIBRARIES'])) {
	foreach($config['REQUIRED_LIBRARIES'] as $requiredLibrary) {
		load_library($requiredLibrary);
	}
}

Diesel::init();