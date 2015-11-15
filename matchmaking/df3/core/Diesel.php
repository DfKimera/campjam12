<?php
/**
 * Diesel Framework
 * Copyright © LQDI Technologies - 2012
 * http://www.lqdi.net
 *
 * Framework master controller
 * Controlador-mestre do framework
 *
 * @author Aryel Tupinambá
 */

class Diesel {

	public static $context = null;
	public static $controllers = array();


	/**
	 * Inicializa os autoloaders do framework
	 *
	 * @static
	 * @return void
	 */
	public static function init() {

		spl_autoload_register(array('Diesel', 'loadModel'));

	}

	/**
	 * Carrega a classe de um controlador.
	 * Armazena as instâncias de forma a não duplicar.
	 *
	 * @static
	 * @param string $moduleName O nome do módulo
	 * @param string $class O nome da classe do controlador
	 *
	 * @return mixed Uma instância da classe
	 */
	public static function &loadController($moduleName, $class) {

        $controllerID = $moduleName."::".$class; // Unique module+controller identifier

        // If we already loaded this controller before, return the existing instance
		if (isset(self::$controllers[$controllerID])) {
			return self::$controllers[$controllerID];
		}

        // Load the controller according to the current context
		if($moduleName == "app") {
			$controllerPath = APP_CONTROLLERS_FOLDER . basename($class). ".php";
		} else {
			$module = ModuleManager::get($moduleName);
			$controllerPath = APP_MODULES_FOLDER . $module->folderName . "/controllers/" . basename($class) . ".php";
		}

        // Check if controller file exists
		if(!file_exists($controllerPath)) {
			error("Error 404-CF: Could not locate the controller class path in the current/specified context. Module: [{$moduleName}], Class: [{$class}]");
		}

        // Includes the file
		include($controllerPath);


        // Check if the controller class exists
		if(!class_exists(ucfirst($class))) {
			error("Error 404-CC: The specified controller class does not exist in the current/specified context. Module: [{$moduleName}], Class: [{$class}]");
		}

        // Creates controller instance and stores it in the registry
		$controllers[$controllerID] = new $class();

        // Returns the instance
		return $controllers[$controllerID];

	}

	/**
	 * Executa um método de um controlador
	 *
	 * @static
	 * @param array $path Um caminho da aplicação. Array associativa com os campos: module, controller, method e parameters
	 *
	 * @return mixed O retorno da função
	 */

	public static function execute($path) {

		$controllerPrefix = config('CONTROLLER_PREFIX');
		$controllerSuffix = config('CONTROLLER_SUFFIX');

        // Load the corresponding controller
		$instance =& Diesel::loadController($path['module'], $controllerPrefix.$path['controller'].$controllerSuffix);

        // Check if the called method actually exists
		if(!method_exists($instance, $path['method'])) {
			error("Error 101: The specified controller does not support the specified method. Module: [{$path['module']}], Controller: [{$path['controller']}], Method: [{$path['method']}]");
		}

        // Calls the instance method with the defined parameters, and returns the result
		return call_user_func_array( array($instance, $path['method']), $path['parameters']);
	}
	
	/**
	 * Carrega uma classe de model, buscando primeiro na pasta da aplicação e 
	 * depois consultando o registro de módulos pelo primeiro módulo que 
	 * contem esse model.
	 * 
	 * Essa função é chamada automaticamente quando uma classe é instanciada
	 * sem que uma definição seja encontrada (spl_register_autoload).
	 *
	 * @static
	 * @param string $modelName O nome do model
	 */
	public static function loadModel($modelName) {

		$modelContext = "app";

		if(self::$context != "app") {
			$module = ModuleManager::getCurrent();

			if($module) {
				$path = APP_MODULES_FOLDER . $module->folderName . "/models/" . basename($modelName) . ".php";
				if(file_exists($path)) {
					$modelContext = self::$context;
				} else {
					$modelContext = "app";
				}
			} else {
				$modelContext = "app";
			}

		}

        self::loadModelFromModule($modelContext, $modelName);
		
	}

	/**
	 * Carrega a configuração de um módulo
	 *
	 * @static
	 * @param string $moduleFolder A pasta no qual o módulo está localizado
	 * @return Module
	 */
	public static function loadModule($moduleFolder) {

		$path = APP_MODULES_FOLDER . basename($moduleFolder) . "/mod.config.php";

		if(!file_exists($path)) {
			error("Error 103-A: Could not load module in folder '{$moduleFolder}', config file was not found");
		}

		include($path);

		return Module::get($moduleFolder);

	}
	
	/**
	 * Carrega uma classe de um model dentro de um módulo especificado
	 *
	 * @static
	 * @param string $moduleName O nome do módulo
	 * @param string $modelName O nome do model
	 */
	public static function loadModelFromModule($moduleName, $modelName) {
		
		if($moduleName == "app") {
			$path = APP_MODELS_FOLDER . basename($modelName) . ".php";

			if( file_exists($path) ) {
				include($path);
			} else {
				error("Error 102-A: Cannot load model [{$modelName}] from application context; the model class does not exist");
			}

		} else {

			$module = ModuleManager::get($moduleName);
			
			if(!$module) {
				error("Error 102-B: Cannot load model [{$modelName}] from module [{$moduleName}]; the context does not exist");
			}

			$path = APP_MODULES_FOLDER . $module->folderName . "/models/" . basename($modelName) . ".php";

			if(file_exists($path)) {
				include($path);
			} else {
				error("102-C: Cannot load model [{$modelName}] from context [{$moduleName}]; the model class does not exist in this context");
			}

		}
		
	}
	
	/**
	 * Carrega múltiplos models dentro de um módulo especificado
	 *
	 * @static
	 * @param string $moduleName O nome do módulo
	 * @param array $modelList Uma array com uma lista de models a carregar
	 */
	public static function loadModelsFromModule($moduleName, $modelList = array()) {
		
		foreach($modelList as $modelName) {

			Diesel::loadModelFromModule($moduleName, $modelName);

		}

	}
	
	/**
	 * Carrega uma tela de visualização, passando os parametros.
	 *
	 * @static
	 * @param string $viewName Nome da view
	 * @param array $viewData Parâmetros da view
	 * @param string $context Buscar a view em qual contexto? "auto" utiliza o contexto atual.
	 * @param string $contentType O tipo MIME de saída
	 * @param string $encoding O tipo de codificação da saída
	 */
	public static function loadView($viewName, $viewData = array(), $context = "auto", $contentType = "text/html", $encoding="utf-8") {

		if($context == "auto") {
			$context = Diesel::$context;
		}

		if($context == "app") {
			$viewPath = APP_VIEWS_FOLDER;
		} else {
			$module = ModuleManager::get($context);
			$viewPath = APP_MODULES_FOLDER . $module->folderName . "/views";
		}

		// Define variáveis locais para cada item do data
		foreach ($viewData as $varName => $value) {
			$$varName = $value;
		}

		// Liga o buffer de saída
		ob_start();
		
		if(!headers_sent()) {
			header("Content-Type: {$contentType}; charset={$encoding}");
		}

		// Inclui a view
		include($viewPath . "/" . $viewName . ".php");

		// Joga o buffer de saída na tela
		ob_end_flush();
	}

	/**
	 * Retorna o HTML do bootstrap do módulo, para ser colocado no <head> da página
	 *
	 * @static
	 * @param string $moduleName O nome do módulo
	 * @return string O HTML do bootstrap
	 */
	public static function getModuleBootstrap($moduleName) {
		return ModuleManager::get($moduleName)->getBootstrap();
	}
	

	/**
	 * Limpa a memória ocupada pelo framework, apagando as instâncias de controladores carregados
	 *
	 * @static
	 */

	public static function cleanup() {
		
		foreach(self::$controllers as $controller) {
			unset($controller);
		}

	}

}
