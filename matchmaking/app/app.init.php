<?php
session_start();

Database::$logQueries = false; // Disable query logging briefly so we avoid logging a lot of boilerplate

$connID = Database::quickConnect(config('DEFAULT_DATABASE'));
ActiveModelManager::setup($connID);

define('AJAX_AUTH', 'AJAX_AUTH');

if($_POST['AJAX_MODE']) {
	define('AJAX_MODE', true);
}

Session::init("User");
Session::reload();

function requireLogin($postRedirect) {
	if(!Session::$isAuthenticated) {
		if($postRedirect == AJAX_AUTH) {
			reply("AUTH_REQUIRED");
		} else {
			redirect($postRedirect);
		}
	}
}

Database::$logQueries = true; // Re-enable log querying for debugging
