<?php
/**
 * Diesel Framework
 * Copyright © LQDI Technologies - 2011
 * http://www.lqdi.net
 *
 * Error management and debugging
 * Gerenciador de erros e debugging
 *
 * @author Aryel Tupinambá
 */


/**
 * Mostra uma mensagem de erro
 *
 * @param string $msg Mensagem de erro
 * @param boolean $internal [optional] É uma mensagem de erro interna do PHP?
 * @param Exception $e [optional] O objeto exceção que causou o erro
 *
 * @throws Exception
 */
function error($msg, $internal = false, Exception $e = NULL) {

    if(defined('DEBUG_MODE')) {
        throw new Exception($msg);
    }
	
	if(defined('AJAX_MODE')) {

        $err = array('message' => $msg, 'internal' => $internal, 'exception' => $e, 'stack' => $stack);

		Log::Write("AJAX-ERROR: '{$msg}'");
		$stack = debug_backtrace();
		reply("OPERATION_ERROR", $err);
		return;
	}
	
	global $CLI_MODE;

	static $blamed = array();

	if($e == NULL) {
	if (!$internal) {
		$errorMessage = Utils::HTML($msg);
	} else {
		$errorMessage = $msg;
	}
	} else {
		$errorMessage = "Exception: {$e->getMessage()}";
	}

	$errorDate = date("d/m/Y H:i:s");

	$errorScript = NULL;
	$errorLine = NULL;
	$runtimeStack = "";

	if($e == NULL) {
		$t = debug_backtrace();
		$t = array_reverse($t);
	} else {
		$t = $e->getTrace();
	}
	
	if($CLI_MODE) {
		echo "\n------------";
		echo "\n[!] DIESEL FRAMEWORK ERROR: {$msg}";
		echo "\n- Exception: ".print_r($e,true);
		echo "\n- Backtrace:";
		foreach($t as $k => $v) {
			echo "\n\t[ {$k} ] => {";
			foreach($v as $i => $j) {
				echo " '{$i}': '{$j}' ";
			}
			echo "}";
			
		}
		echo "\n------------";
		exit();
	}

	foreach ($t as $n => $i) {

		$runtimeStack .= $string = "<br />";
		if ($errorScript == NULL) {
			$errorScript = $i['file'];
			$errorLine = $i['line'];
		}

		$i['file'] = relative_path($i['file']);

		$argList = array();
		if (is_array($i['args'])) {
			foreach ($i['args'] as $argNum => $arg) {
				if (is_string($arg) && strlen($arg) > 128) {
					array_push($argList, "<span style=\"color: #DD0000\">\"{$arg}\"</span>");
				} else if (is_object($arg)) {
					array_push($argList, "<span style=\"color: #DD0000\">\"[object:" . get_class($arg) . "]\"</span>");
				} else if (is_resource($arg)) {
					array_push($argList, "<span style=\"color: #DD0000\">\"[resource:" . print_r($arg, true) . "]\"</span>");
				} else if (is_array($arg)) {
					array_push($argList, "<span style=\"color: #DD0000\">\"[array:" . sizeof($arg) . "]\"</span>");
				} else {
					array_push($argList, "<span style=\"color: #DD0000\">\"{$arg}\"</span>");
				}
			}

			$argList = join("<span style=\"color: #007700\">,</span> ", $argList);
		} else {
			$argList = "";
		}

		$runtimeStack .= "<br><b> at <span style=\"color: #007700\">[{$n}]</span> {$i['file']}<span style=\"color: #FF8000\">:{$i['line']}</span></b>";
		$runtimeStack .= "<br><b>&nbsp;&nbsp;&raquo; <span style=\"color: #0000BB\">{$i['function']}</span><span style=\"color: #007700\">(</span>{$argList}<span style=\"color: #007700;\">)</span><span style=\"color: #0000BB\">;</span></b>";
	}

	if(class_exists("Log")) {
		if (sizeof(Log::$logwrites) > 0) {
			$runtimeLog = join("<br />", Log::$logwrites);
		} else {
			$runtimeLog = "No messages were recorded in the log";
		}
		
		Log::Write("D-ERROR: '{$msg}' @ [{$errorScript}:{$errorLine}]");
		
	} else {
		$runtimeLog = "The logging service was not active";
	}

	$evodd = 0;

	$runtimeParameters = "<table width=\"500\" cellpadding=\"3\" cellspacing=\"0\">";
	foreach($_POST as $key => $value) {
		$evodd++; $evc = ($evodd%2==0) ? "#EEEEEE" : "#DDDDDD";
		$runtimeParameters .= "
		<tr bgcolor=\"{$evc}\">
			<td><span style=\"color: #007700\">POST</span></td>
			<td><span style=\"color: #0000BB\">\"{$key}\"</span></td>
			<td><span style=\"color: #DD0000\">\"".print_r($value, true)."\"</span></td>
		</tr>";
	}
	foreach($_GET as $key => $value) {
		$evodd++; $evc = ($evodd%2==0) ? "#EEEEEE" : "#DDDDDD";
		$runtimeParameters .= "
		<tr bgcolor=\"{$evc}\">
			<td><span style=\"color: #007700\">GET</span></td>
			<td><span style=\"color: #0000BB\">\"{$key}\"</span></td>
			<td><span style=\"color: #DD0000\">\"".print_r($value, true)."\"</span></td>
		</tr>";
	}
	$runtimeParameters .= "</table>";

	$sessionVars = "<table width=\"500\" cellpadding=\"3\" cellspacing=\"0\">";
	foreach((array) $_SESSION as $key => $value) {
		$evodd++; $evc = ($evodd%2==0) ? "#EEEEEE" : "#DDDDDD";
		$sessionVars .= "
		<tr bgcolor=\"{$evc}\">
			<td><span style=\"color: #0000BB\">\"{$key}\"</span></td>
			<td><span style=\"color: #DD0000\">\"".print_r($value, true)."\"</span></td>
		</tr>";
	}
	$sessionVars .= "</table>";

	$cookies = "<table width=\"500\" cellpadding=\"3\" cellspacing=\"0\">";
	foreach($_COOKIE as $key => $value) {
		$evodd++; $evc = ($evodd%2==0) ? "#EEEEEE" : "#DDDDDD";
		$cookies .= "
		<tr bgcolor=\"{$evc}\">
			<td><span style=\"color: #0000BB\">\"{$key}\"</span></td>
			<td><span style=\"color: #DD0000\">\"".print_r($value, true)."\"</span></td>
		</tr>";
	}
	$cookies .= "</table>";

	if(!class_exists("Module")) {
		$loadedModules = "Module managemenent class was not loaded";
	} else {
		$loadedModules = "<table width=\"500\" cellpadding=\"3\" cellspacing=\"0\">";
		foreach(ModuleManager::$modules as $module) {
			$evodd++; $evc = ($evodd%2==0) ? "#EEEEEE" : "#DDDDDD";
			$loadedModules .= "
			<tr bgcolor=\"{$evc}\">
			<td><span style=\"color: #0000BB\"><b>{$module->name}</b>, by {$module->author}</span></td><td><span style=\"color: #DD0000\">{$module->version}</span></td><td><span style=\"color: #007700\">{$module->folderName}</span></td>
			</tr>";
		}
		$loadedModules .= "</table>";
	}

	$outputBuffer = htmlentities(ob_get_contents());

	discard_output();

	$errorFile = "assets/diesel.error.php";
	include($errorFile);
	
	ob_end_flush();

	exit();
	
}

/**
 * ATENÇÃO: Função interna do framework, não a chame diretamente! Utilize em seu lugar a função error()
 * Recebe todos os erros não-fatais do PHP e os processa usando o handler de erros do framework
 *
 * @param $errno
 * @param $errstr
 * @param $errfile
 * @param $errline
 * @return bool
 */
function df3_error_handler($errno, $errstr, $errfile, $errline) {

	error("Erro de PHP #{$errno}: {$errstr} (em {$errfile}:{$errline})", true);

	return true;
}

/**
 * ATENÇÃO: Função interna do framework, não a chame diretamente! Utilize em seu lugar a função error()
 * Recebe todas as exceções não coletadas e as processa usando o handler de erros do framework
 *
 * @param Exception $e
 */
function df3_exception_handler(Exception $e) {
	error($e->getMessage(), false, $e);
}

set_error_handler("df3_error_handler", E_ERROR | E_WARNING | E_CORE_ERROR | E_CORE_WARNING | E_PARSE | E_COMPILE_ERROR | E_COMPILE_WARNING | E_USER_ERROR | E_USER_WARNING);
set_exception_handler("df3_exception_handler");
