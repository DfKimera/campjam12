<?php
/**
 * Diesel Framework
 * Copyright © LQDI Technologies - 2011
 * http://www.lqdi.net
 *
 * Global function library
 * Biblioteca de funções globais
 *
 * @author Aryel Tupinambá
 */

/**
 * Obtém a âncora (URL relativo) de um determinado link absoluto no sistema
 *
 * Exemplo:
 *
 * 		anchor('MyController/getRecords/22');
 *
 * 	irá retornar o link correto para essa ação, não importando
 * 	em qual URL o usuário está agora
 *
 * @param string $location O caminho desejado
 * @return string A URL relativa correta
 */
function anchor($location) {
	return APP_ANCHOR_PATH . "{$location}";
}

/**
 * Carrega uma tela de visualização, passando os parametros.
 * 
 * (Atalho para a função Diesel::loadView)
 *
 * @param string $viewName Nome da view
 * @param array $viewData Parâmetros da view
 * @param string $context Buscar a view em qual contexto? "auto" utiliza o contexto atual.
 * @param string $contentType O tipo MIME de saída
 * @param string $encoding O tipo de codificação da saída
 */

function load_view($viewName, $viewData = array(), $context = "auto", $contentType = "text/html", $encoding="utf-8") {
	Diesel::loadView($viewName, $viewData, $context, $contentType, $encoding);
}

/**
 * Obtém o caminho relativo à partir de um caminho absoluto
 * @param string $absolutePath O caminho absoluto
 * @return string O caminho relativo ao script sendo executado (geralmente o index.php)
 */
function relative_path($absolutePath) {
	$abs = str_replace("\\", "/", dirname($_SERVER['SCRIPT_FILENAME']));
	$rel = str_replace($abs, "", str_replace("\\", "/", $absolutePath));
	return $rel;
}

/**
 * Atalho para a função mysql_escape_string()
 * @param string $str A string original
 * @return string A string escapada
 */
function clear($str) {
	return mysql_escape_string($str);
}

/**
 * Obtém dados da configuração do sistema/aplicação
 * @param string $key O nome do item de configuração
 * @return object O item da configuração desejado
 */
function config($key) {
	global $config;
	return $config[$key];
}

/**
 * Obtém uma variável de configuração do módulo atual.
 * Caso o contexto seja uma aplicação, funciona igual a função config()
 * @param string $key O nome da variável
 * @return mixed|object
 */
function modconfig($key) {
	if(Diesel::$context == "app") {
		return config($key);
	}

	return ModuleManager::getCurrent()->getConfig($key);
}

/**
 * Imprime na tela uma mensagem com quebra de linha e conversão UTF-8
 * @param string $msg A mensagem a ser impressa
 */
function trace($msg) {
	echo utf8_decode("<br>{$msg}");
}

/**
 * Descarta o buffer de saída atual
 */
function discard_output() {
	while(ob_get_length () !== FALSE) {
		ob_end_clean();
	}
}

/**
 * Ativa a compressão GZ para o buffer de saída
 */
function print_gzipped_output() {
	$HTTP_ACCEPT_ENCODING = $_SERVER["HTTP_ACCEPT_ENCODING"];
	if (headers_sent ()) {
		$encoding = false;
	} else if (strpos($HTTP_ACCEPT_ENCODING, 'x-gzip') !== false) {
		$encoding = 'x-gzip';
	} else if (strpos($HTTP_ACCEPT_ENCODING, 'gzip') !== false) {
		$encoding = 'gzip';
	} else {
		$encoding = false;
	}

	if ($encoding) {
		$contents = ob_get_clean();
		$_temp1 = strlen($contents);
		if ($_temp1 < 2048) // no need to waste resources in compressing very little data
			print($contents);
		else {
			header('Content-Encoding: ' . $encoding);
			print("\x1f\x8b\x08\x00\x00\x00\x00\x00");
			$contents = gzcompress($contents, 9);
			$contents = substr($contents, 0, $_temp1);
			print($contents);
		}
	} else {
		ob_end_flush();
	}
}

/**
 * Carrega uma library para utilização
 * @param string $libraryName O nome da library
 */
function load_library($libraryName) {
	
	$path = LIB_PATH . basename($libraryName) . ".php";
	
	if(!file_exists($path)) {

		$frameworkLibPath = DF3_PATH . "lib/" . basename($libraryName) . ".php";

		if($frameworkLibPath) {
        	include($frameworkLibPath);
		} else {
			error("Error 203: Cannot load required library [{$libraryName}]; the library does not exist");
        }

	} else {
	
		include($path);

	}
	
}

/**
 * Carrega diversas libraries para utilização
 * @param array $libraries Uma array com uma lista de libraries a carregar
 */
function load_libraries($libraries = array()) {
	foreach($libraries as $libraryName) {
		load_library($libraryName);
	}
}

/**
 * Redireciona o navegador do usuário para um caminho no sistema.
 * 
 * @param string $anchor O caminho do sistema à redirecionar
 */
function redirect($anchor) {
	$url = anchor($anchor);
	
	discard_output();
	
	ob_start();
	echo "<meta http-equiv='refresh' content='0;url={$url}' />";
	ob_end_flush();

	exit();
}

/**
 * Envia uma resposta AJAX (JSON) para o navegador do usuario.
 * 
 * @param string $status O status da requisição
 * @param array $parameters Parametros ou dados a serem retornados
 */
function reply($status, $parameters = array()) {
	discard_output();
	
	$data = $parameters;
	$data['status'] = $status;
	
	ob_start();
	echo( json_encode( $data ));
	ob_end_flush();

	exit();
	
}

/**
 * Obtém uma data no formato DATETIME do MySQL
 * @param int $timestamp (opcional) A timestamp a ser convertida. Se não informada, é retornada a data atual.
 * @return string A data no formato DATETIME. Se não foi informado um timestamp, retorna a data atual.
 */
function sql_date($timestamp = null) {
	if($timestamp != null) {
		return date("Y-m-d H:i:s", $timestamp);
	} else {
		return date("Y-m-d H:i:s");
	}
}

/**
 * Obtém uma data no formato DATETIME do MySQL
 * @param int $day O dia
 * @param int $month O mês
 * @param int $year O ano
 * @param int $hour (opcional) A hora
 * @param int $minute (opcional) O minuto
 * @param int $second (opcional) O segundo
 * @return string A data no formato DATETIME.
 */
function sql_date_create($day, $month, $year, $hour = 0, $minute = 0, $second = 0) {
	return sql_date(mktime($hour, $minute, $second, $month, $day, $year));
}

/**
 * Limpa uma string oriunda do banco de dados para exibição, removendo escape slashes, consertando newlines e
 * opcionalmente convertendo-os para linebreaks HTML (<br>)
 * @param string $str A string oriunda do banco de dados
 * @param bool $nlbr [optional] Converter newlines para <br>?
 * @return string A string pronta para output
 */
function clearOutputText($str, $nlbr = false) {
	$str = str_replace("\\r", "\r", $str);
	$str = str_replace("\\n", "\n", $str);
	
	if($nlbr) {
		$str = nl2br($str);
	}
	
	$str = str_replace("\\", "", $str);
	$str = stripslashes($str);
	
	return $str;
}