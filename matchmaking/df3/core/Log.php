<?php
/**
 * Diesel Framework
 * Copyright © LQDI Technologies - 2011
 * http://www.lqdi.net
 *
 * Logging service
 * Serviço de logging
 *
 * @author Aryel Tupinambá
 */

class Log {

	public static $logwrites = array();
	public static $logfile = NULL;


	/**
	 * Define o caminho do arquivo de escrita do log
	 *
	 * @static
	 * @param  string $file
	 */
	public static function setFile($file) {
		self::$logfile = $file;
	}

	/**
	 * Escreve uma mensagem no log da aplicação
	 *
	 * @static
	 * @param  string $message
     * @return string A mensagem formatada com data e caller
	 */
	public static function write($message) {

		if (self::$logfile == NULL) {

			if(!is_dir('data')) {
				mkdir("data", 777);
			}
			
			if(!is_dir('data/logs')) {
				mkdir('data/logs', 777);
			}

			self::$logfile = "data/logs/" . date("Y-m-d") . ".log";
		}

		$t = debug_backtrace();
		$caller = basename($t[sizeof($t) - 1]['file']) . ":" . $t[sizeof($t) - 1]['line'];

		$string = "\r\n[L][" . date("Y-m-d @ H:i:s") . "][{$caller}] : {$message}";
		@error_log($string, 3, self::$logfile);

		array_push(self::$logwrites, $message);

		return $message;
	}

	/**
	 * Escreve no log todos os dados de um objeto
	 *
	 * @static
	 * @param  object $object
     * @return string A mensagem formatada com data e caller
	 */
	public static function object($object) {

		if (self::$logfile == NULL) {
			self::$logfile = "data/logs/" . date("Y-m-d") . ".log";
		}

		$t = debug_backtrace();
		$caller = basename($t[sizeof($t) - 1]['file']) . ":" . $t[sizeof($t) - 1]['line'];

		$message = "Dumping object: " . $object . "\r\n";
		ob_start();
		var_dump($object);
		$message .= "\r\n" . ob_get_contents();
		ob_end_clean();
		$message .= "\r\n";

		$string = "\r\n[O][" . date("Y-m-d @ H:i:s") . "][{$caller}] : {$message}";
		@error_log($string, 3, self::$logfile);


		array_push(self::$logwrites, $message);
		return $message;
	}

	/**
	 * Executa um backtrace da execução e armazena no log
	 *
	 * @static
	 * @param  bool $show_string_args Define se os parâmetros do tipo string em cada função deve ser armazenada no log
     * @return string A string com o trace
	 */
	public static function trace($show_string_args = false) {
		$buffer = "";

		if (self::$logfile == NULL) {
			self::$logfile = "data/logs/" . date("Y-m-d") . ".log";
		}

		$t = debug_backtrace();

		$buffer .= $string = "\r\n[T][" . date("Y-m-d @ H:i:s") . "] Current backtrace:";
		@error_log($string, 3, self::$logfile);

		// We do multiple error_log calls because of the max. log message size of 1024.
		// A long execution stack and a lot of arguments in a function could easily
		// pass this value. We don't need to care about performance here because
		// this is only for debugging purposes.

		foreach ($t as $n => $i) {
			$buffer .= $string = "\r\n -> [{$n}] {$i['file']}:{$i['line']}";

			$argList = array();
			foreach ($i['args'] as $argNum => $arg) {
				if (is_string($arg) && strlen($arg) > 36) {
					if ($show_string_args) {
						array_push($argList, "'{$arg}'");
					} else {
						array_push($argList, "'[string]" . strlen($arg) . "'");
					}
				} else if (is_resource($arg)) {
					array_push($argList, "'[resource] " . print_r($arg, true) . "'");
				} else if (is_array($arg)) {
					array_push($argList, "'[array] " . sizeof($arg) . "'");
				} else {
					array_push($argList, "'{$arg}'");
				}
			}

			$argList = join(", ", $argList);

			$buffer .= $string .= "\r\n \t {$i['function']}( {$argList} );";
			@error_log($string, 3, self::$logfile);
		}

		$buffer .= $string = "\r\n[T] End of backtrace ----------------";
		@error_log($string, 3, self::$logfile);

		array_push(self::$logwrites, $buffer);
		
		return $buffer;

	}

	/**
	 * Handler para compatibilidade com projetos no Diesel Framework 2
	 * Na versão 3, a nomenclatura foi padronizada para Class::methodInCamelCase()
	 *
	 * @param $function
	 * @param $arguments
	 * @return mixed
	 */
	public static function __callStatic($function, $arguments) {

		$function = ucwords($function);
		return call_user_func_array("Log::{$function}", $arguments);

	}

}
