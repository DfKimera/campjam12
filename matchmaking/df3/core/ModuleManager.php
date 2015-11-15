<?php
/**
 * Diesel Framework
 * Copyright © LQDI Technologies - 2011
 * http://www.lqdi.net
 *
 * Module manager
 * Gerenciador de módulos
 *
 * @author Aryel Tupinambá
 */

class Module {

	public $name;
	public $version;
	public $author;
	public $folderName;
	public $config = array();

	private $configProperties = array();

	public $runAppInit = false;
	public $runModInit = false;

	public $requiredLibraries = array();
	public $assets = array();

	public function __construct($name) {
		$this->name = $name;
		ModuleManager::register($this);
	}

	/**
	 * Define informações do módulo
	 *
	 * @param string $author O autor do módulo
	 * @param string $version A versão do módulo
	 * @return Module
	 */
	public function setInfo($author, $version) {
		$this->author = $author;
		$this->version = $version;
		return $this;
	}

	/**
	 * Define o nome da pasta que contém o módulo
	 *
	 * @param string $folderName O nome da pasta
	 * @return Module
	 */
	public function setFolderName($folderName) {
		$this->folderName = $folderName;
		return $this;
	}

	/**
	 * Define uma lista de bibliotecas necessárias para utilização do módulo
	 *
	 * @param array $libraries Lista de bibliotecas
	 * @return Module
	 */
	public function setRequiredLibraries($libraries) {
		$this->requiredLibraries = $libraries;
		return $this;
	}

	/**
	 * Adiciona uma biblioteca à lista de bibliotecas necessárias
	 *
	 * @param string $libName O nome da biblioteca
	 * @return Module
	 */
	public function requireLibrary($libName) {
		if(!is_array($this->requiredLibraries)) {
			$this->requiredLibraries = array();
		}

		array_push($this->requiredLibraries, $libName);

		return $this;
	}

	/**
	 * Adiciona um asset à lista de assets que serão carregados no bootstrap
	 *
	 * @param string $type O tipo de asset ('css', 'javascript')
	 * @param string $path O caminho do asset, relativo à pasta "/assets"
	 * @return Module
	 */
	public function addAsset($type, $path) {
		if(!is_array($this->assets)) {
			$this->assets = array();
		}

		array_push($this->assets, array('type' => $type, 'path' => $path));

		return $this;
	}

	/**
	 * Define a flag que determina se o framework deve ou não executar o mod.init.php ao inicializar o módulo
	 *
	 * @param bool $modInit
	 * @return Module
	 */
	public function setModInitFlag($modInit) {
		$this->runModInit = $modInit;
		return $this;
	}

	/**
	 * Define a flag que determina se o framework deve ou não executar o app.init.php ao inicializar o módulo
	 *
	 * @param bool $appInit
	 * @return Module
	 */
	public function setAppInitFlag($appInit) {
		$this->runAppInit = $appInit;
		return $this;
	}

	/**
	 * Retorna o caminho relativo e ancorado de um asset
	 *
	 * @param string $path O caminho do asset, relativo à pasta "/assets"
	 * @return string O caminho relativo e ancorado
	 */
	public function getFullAssetPath($path) {
		return anchor("appmod/{$this->name}/assets/{$path}");
	}

	/**
	 * Retorna o HTML do bootstrap do módulo, para ser colocado no <head> da página
	 * @return string
	 */
	public function getBootstrap() {

		$bootstrap = array();

		foreach($this->assets as $asset) {

			$path = $this->getFullAssetPath($asset['path']);

			switch($asset['type']) {
				case "css":
					$line = "<link type=\"text/css\" rel=\"stylesheet\" href='\"{$path}\" />";
					break;
				case "javascript":
					$line = "<script type=\"text/javascript\" src=\"{$path}\"></script>";
					break;
			}

			array_push($bootstrap, $line);
		}

		return join("\n", $bootstrap);

	}

	/**
	 * Registra uma variável de configuração do módulo
	 *
	 * @param string $variable O nome da variável
	 * @param mixed $default O valor padrão
	 * @return Module
	 */
	public function registerConfig($variable, $default = null) {
		$this->config[$variable] = $default;
		if(!in_array($variable, $this->configProperties)) {
			array_push($this->configProperties, $variable);
		}
		return $this;
	}

	/**
	 * Define uma variável de configuração do módulo
	 *
	 * @param string $variable O nome da variável
	 * @param mixed $value O valor
	 * @return Module
	 */
	public function setConfig($variable, $value) {
		$this->config[$variable] = $value;
		return $this;
	}

	/**
	 * Retorna o valor de uma variável de configuração do módulo
	 *
	 * @param string $variable O nome da variável
	 * @return mixed
	 */
	public function getConfig($variable) {
		return $this->config[$variable];
	}

	/**
	 * Retorna um módulo à partir de seu nome
	 * Alias p/ ModuleManager::get()
	 *
	 * @static
	 * @param string $modName O nome do módulo
	 * @return Module
	 */
	public static function get($modName) {
		return ModuleManager::get($modName);
	}

	/**
	 * Retorna o caminho relativo e ancorado à um asset do módulo atual.
	 * Não pode ser chamada fora do contexto de um módulo.
	 * Alias p/ Module::getCurrent()->getFullAssetPath
	 *
	 * @static
	 * @param string $path O caminho, relativo à pasta "assets/" do módulo atual
	 * @return string
	 */
	public static function assetPath($path) {
		if(Diesel::$context == "app") {
			error("Error 203: You cannot call this function while on an app context");
		}

		return ModuleManager::getCurrent()->getFullAssetPath($path);
	}

}

class ModuleManager {

	public static $modules = array();

	public static function create($name) {
		return new Module($name);
	}


	/**
	 * Registra um módulo no framework
	 *
	 * @static
	 * @param Module $module
	 */
	public static function register(Module $module) {

		self::$modules[$module->name] = $module;
		Router::addModule($module->name);

	}

	/**
     * Obtém uma entrada no registro de módulos à partir do seu nome base (nome da pasta)
	 *
	 * @static
     * @param string $name O nome do módulo
     * @return Module
     */
	public static function get($name) {
		$mod = self::$modules[$name];
		if(!$mod) {
			return false;
		} else {
			return $mod;
		}
	}

    /**
     * Obtém a entrada no registro de módulos correspondente ao contexto atual
	 *
	 * @static
     * @return Module
     */
    public static function getCurrent() {
        if(!defined('DIESEL_CONTEXT_SET')) {
            return false;
        }

        return self::get(Diesel::$context);

    }

}
