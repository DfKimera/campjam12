<?php
/**
 * Diesel Framework
 * Copyright © LQDI Technologies - 2011
 * http://www.lqdi.net
 *
 * Route manager
 * Gerenciador de rotas
 *
 * @author Aryel Tupinambá
 */

class Router {
	
	public static $routes = array();
	public static $prefixes = array();
    public static $modules = array();
	
	/**
	 * Registra uma rota absoluta
	 *
	 * @static
	 * @param array $route A rota à registrar (ex: array('pages','view') para 'example.com/pages/view' )
	 * @param array $path O caminho do framework à executar (ex: array('controller' => 'pages', 'method' => 'view', 'parameters' => array(1) ); )
	 */
	public static function addRoute($route, $path) {
		if(defined("DIESEL_CONFIG_LOADED")) {
			error("Cannot add route '{$route}': you must configure the routes and prefixes in app.config.php");
		}
		array_push(self::$routes, array('route' => $route, 'path' => $path));
	}

    /**
     * Registra um módulo
     *
	 * @static
     * @param array $name O nome do módulo
     */
    public static function addModule($name) {
        if(defined("DIESEL_CONFIG_LOADED")) {
            error("Cannot add module '{$name}': you must configure the routes and prefixes in app.config.php");
        }
        array_push(self::$modules, $name);
    }
	
	/**
	 * Registra um prefixo para os controladores
	 *
	 * @static
	 * @param string $pathPrefix O prefixo, como delimitado no caminho
	 * @param string $controllerPrefix (opcional) O prefixo para os controladores correspondentes. Se não preenchido, será igual ao prefixo do path.
	 */
	public static function addPrefix($pathPrefix, $controllerPrefix = null) {
		if(defined("DIESEL_CONFIG_LOADED")) {
			error("Cannot add prefix '{$pathPrefix}': you must configure the routes and prefixes in app.config.php");
		}
		if($controllerPrefix == null) {
			$controllerPrefix = $pathPrefix;
		}
		array_push(self::$prefixes, array('pathPrefix' => $pathPrefix, 'controllerPrefix' => $controllerPrefix));
	}
	
	/**
	 * Traduz uma rota em um caminho do framework, considerando as rotas absolutas registradas
	 *
	 * @static
	 * @param string $route A rota de entrada
	 * @return array O caminho do framework
	 */
	public static function parseRoute($route) {
		
		$parts = explode("/", $route);

        // Check in the registered routes for shorcuts
		foreach(self::$routes as $rid => $routePath) {
			
			$registeredRoute = $routePath['route'];

            // Try to match the input path with the registered path
			$intersect = array_intersect_assoc($parts, $registeredRoute);
			if(sizeof($intersect) == sizeof($registeredRoute)) {

                // If we get a match, it means the path is registered
                // The difference between them are the method parameters
				$diff = array_slice(array_diff_assoc($parts, $registeredRoute), 0);

				$path = array(
                    'module' =>  $routePath['path']['module'],
					'controller' => ucfirst($routePath['path']['controller']),
					'method' => $routePath['path']['method'],
					'parameters' => $diff
				);
				
				return $path;
				
			}
			
		}

        // Set default values for prefix and module
		$controllerPrefix = "";
        $controllerModule = "app"; // Default module is the application itself
		$partOffset = 0;


        // Check if we're calling a module
        foreach(self::$modules as $module) {

			$module = strtolower($module);

            if(strtolower($parts[$partOffset]) == $module) { // Found the module in the registry

                $controllerModule = $module;
                $partOffset++; // Skip this part of the path in the next stages

                break;

            }

        }

        // Check our call has a registered prefix
		foreach(self::$prefixes as $prefix) {
			
			$pathPrefix = strtolower($prefix['pathPrefix']);
			
			if(strtolower($parts[$partOffset]) == $pathPrefix) { // Found the path prefix in the registry

				$controllerPrefix = $prefix['controllerPrefix'];
				$partOffset++; // Skip this part of the path in the next stages

				break;

			}
			
		}

        // Compose the path info
        $path = array(
            'module' => $controllerModule,
            'controller' => $controllerPrefix . ucfirst($parts[0 + $partOffset]),
            'method' => $parts[1 + $partOffset],
            'parameters' => array_slice($parts, 2 + $partOffset)
        );

        // Clean up the parameters array by making sure the first element isn't empty
		if(sizeof($path['parameters']) == 1 && $path['parameters'][0] == '') {
			$path['parameters'] = array();
		}
		
		return $path;
		
	}
	
}
