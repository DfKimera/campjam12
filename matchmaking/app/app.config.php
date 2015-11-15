<?php
// Application folder paths
// Caminhos de pastas da aplicação
define(		"APP_MODELS_FOLDER", 				"app/models/");
define(		"APP_VIEWS_FOLDER", 				"app/views/");
define(		"APP_CONTROLLERS_FOLDER", 			"app/controllers/");
define(		"APP_MODULES_FOLDER", 			    "app/modules/");

define(		"APP_DATA_FOLDER",					"data/");
define(		"APP_UPLOADS_FOLDER",				"data/uploaded/");

// Base anchor path (relative to URL, used for anchor resolution)
// Caminho base de âncora (relativo à URL, utilizado para resolução de âncora)
define(		"APP_ANCHOR_PATH",					"/");
define(		"APP_ABSOLUTE_PATH",				"D:/web/lqdi.net/subdomains/intra/httpdocs/");

// Nome da aplicação
$config['APP_NAME'] = "DF3 Base Application";

// Breve descrição da aplicação
$config['APP_DESCRIPTION'] = "Diesel Framework 3 Base Application";

// Nome do cliente
$config['APP_CLIENT'] = "LQDI t.image";

// Controlador default da aplicação, à ser executado quando nenhum é informado
$config['DEFAULT_CONTROLLER'] = "Home";

// Uma lista com os módulos requiridos para o funcionamento da aplicação
$config['REQUIRED_MODULES'] = array(

);

// Uma lista de bibliotecas que o framework deve carregar automaticamente
$config['REQUIRED_LIBRARIES'] = array(
	'Database',
	'Session',
	'Image',
	'ActiveModel',
	'Facebook'
);

$config['DEFAULT_DATABASE'] = array(
	'hostname' => 'localhost',
	'username' => 'df3',
	'password' => 'test',
	'database' => 'df3',
	'port' => 3306,
	'persistent' => true,
	'table_prefix' => ''
);
