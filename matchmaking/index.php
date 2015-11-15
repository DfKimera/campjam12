<?php
/**
 * Diesel Framework 3
 * Copyright © LQDI Technologies - 2012
 * http://www.lqdi.net
 *
 * Application entry-point
 * Entry-point da aplicação
 *
 * @author Aryel Tupinambá
 */

define('APP_ENVIRONMENT', 'development');

if(!defined('DF3_PATH')) {
    define('DF3_PATH', './df3/');
}

include(DF3_PATH . "init.php");

// Attempt to get the incoming application route
if(isset($_GET['DIESEL_ROUTE'])) { // Rewritten URL route (lqdi.net/controller/method/params)
	$route = $_GET['DIESEL_ROUTE'];

} else if(isset($_SERVER['PATH_INFO'])) { // Path Info route (lqdi.net/index.php/controller/method/params)
	$route = substr($_SERVER['PATH_INFO'], 1);

} else { // Query String route (lqdi.net/index.php?controller/method/params)
    $route = $_SERVER['QUERY_STRING'];

}

// Parses the route into a framework path
$path = Router::parseRoute($route);

// Ensures all required information is in the path
if(!$path['module']) {
    $path['module'] = "app";
}

if(!$path['controller']) {
    $path['controller'] = config("DEFAULT_CONTROLLER");
}

if(!$path['method']) {
    $path['method'] = "index";
}

if(!$path['parameters']) {
    $path['parameters'] = array();
}

Diesel::$context = $path['module'];
$currentModule = ModuleManager::getCurrent();

define("DIESEL_CONTEXT_SET", true);

// Runs the application/module init script (pre-execute event)
if(Diesel::$context == "app" || ($currentModule && $currentModule->runAppInit)) {
	include(APP_PATH . "app.init.php");
} else if($currentModule && $currentModule->runModInit) {
	include(APP_MODULES_FOLDER . $currentModule->folderName . "/mod.init.php");
}

// Executes the incoming app route
Diesel::execute($path);

// Unload all to free memory controllers
Diesel::cleanup();
