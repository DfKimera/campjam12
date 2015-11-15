<?php

/**
 * Diesel Framework
 * Copyright © LQDI Technologies - 2011
 * http://www.lqdi.net
 *
 * Utility function library
 * Biblioteca de funções utilitárias
 *
 * @author Aryel Tupinambá
 */

define('UPLOAD_FAILURE_HTTP', 1);
define('UPLOAD_FAILURE_FORMAT', 2);
define('UPLOAD_FAILURE_MOVE', 3);

class Utils {

	/**
	 * Testa se o ano é bissexto ou não
	 *
	 * @static
	 * @param int $ano O ano a ser testado
	 * @return boolean True ou false
	 */
	public static function isBissexto($ano) {
		if ($ano % 4 == 0 && ($ano % 400 == 0 || $ano % 100 != 0)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Examina um diretório e obtém a lista de arquivos e pastas
	 *
	 * @static
	 * @param string $dir O diretório à ser examinado
	 * @param boolean $show_subdirs Devo acrescentar os subdiretórios também?
	 * @param boolean $skip_dots Devo ignorar os diretórios '.' e '..'?
	 * @return array Uma array de ítens (arquivos e pastas)
	 */
	public static function scanDirectory($dir, $show_subdirs = false, $skip_dots = true) {
		$dirArray = array();
		if ($handle = opendir($dir)) {
			while (false !== ($file = readdir($handle))) {
				if (($file != "." && $file != "..") || $skip_dots == true) {
					if ($show_subdirs == false) {
						if (is_dir($file)) {
							continue;
						}
					}

					array_push($dirArray, basename($file));
				}
			}

			closedir($handle);
		}

		return $dirArray;
	}

	/**
	 * Função utilitária, retorna false até o contador interno chegar ao valor máximo
	 *
	 * @static
	 * @staticvar int $current Valor atual do contador
	 * @param int $max Valor máximo do contador
	 * @return boolean
	 */
	private static $currentCounter = 0;

	public static function countTo($max) {

		self::$currentCounter++;
		if (self::$currentCounter > $max) {
			self::$currentCounter = 0;
			$return = true;
		} else {
			$return = false;
		}
		return $return;
	}

	/**
	 * Reinicia o contador interno utilizado pela função countTo()
	 *
	 * @static
	 * @staticvar int $currentCounter Valor atual do contador
	 */
	public static function resetCount() {
		self::$currentCounter = 0;
	}

	/**
	 * Limpa uma string de todos os caracteres especiais
	 *
	 * @static
	 * @param string $string A string a ser limpa
	 * @return string A string limpa
	 */

	public static function rawString($string) {
		$pairs = array(
			'Š' => 'S', 'š' => 's', 'Ð' => 'Dj', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
			'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I',
			'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U',
			'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
			'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i',
			'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u',
			'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y', 'ƒ' => 'f'
		);

		return strtr($string, $pairs);
	}

	/**
	 * Verifica se um número de CEP é valido ou não
	 *
	 * @static
	 * @param string $cep O número de CEP a ser validado
	 * @return boolean True se válido, false se não
	 */
	public static function isValidCEP($cep) {

		$cep = trim($cep);
		$cep = Utils::stripPunctuation($cep);
		$valid = ereg("^[0-9]{8}$", $cep);

		if ($valid) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Verifica se um número de CPF é válido ou não
	 *
	 * @static
	 * @param string $cpf O número de CPF a ser validado
	 * @return boolean True se válido, false se não
	 */
	public static function isValidCPF($cpf) {
		$cpf = trim($cpf);
		$cpf = Utils::stripPunctuation($cpf);
		$cpf = str_pad(ereg_replace('[^0-9]', '', $cpf), 11, '0', STR_PAD_LEFT);

		$invalid = array("00000000000", "11111111111", "22222222222", "33333333333", "44444444444", "55555555555", "66666666666", "77777777777", "88888888888", "99999999999");

		if (strlen($cpf) != 11 || in_array($cpf, $invalid)) {
			return false;
		} else {
			for ($t = 9; $t < 11; $t++) {
				for ($d = 0, $c = 0; $c < $t; $c++) {
					$d += $cpf{$c} * (($t + 1) - $c);
				}

				$d = ((10 * $d) % 11) % 10;

				if ($cpf{$c} != $d) {
					return false;
				}
			}

			return true;
		}
	}

	/**
	 * Extrai uma array de chaves e valores dos dados enviados via POST
	 *
	 * @static
	 * @param array $fields Os campos à extrai
	 * @param boolean $filter Devo filtrar os valores contra SQL Injection?
	 */
	public static function fetchFormData($fields, $filter = true) {
		$data = array();
		foreach ($fields as $field) {
			if ($filter) {
				$data[$field] = clear($_POST[$field]);
			} else {
				$data[$field] = $_POST[$field];
			}
		}
	}

	/**
	 * Copia alguns campos de uma array para outra
	 *
	 * @static
	 * @param array $originArray A array original
	 * @param array $fields Os campos à serem copiados
	 * @return array Uma nova array
	 */
	public static function selectiveArrayExtract($originArray, $fields) {
		$data = array();
		foreach ($fields as $field) {
			$data[$field] = $originArray[$field];
		}
		return $data;
	}

	/**
	 * A cada iteração retorna um valor
	 *
	 * @static
	 * @staticvar int $evodd O status do contador
	 * @param mixed $evenValue Valor retornado se for even
	 * @param mixed $oddValue Valor retornado se for odd
	 * @return mixed O valor baseado no contador
	 *
	 * @deprecated
	 */
	public static function evenOrOdd($evenValue, $oddValue) {
		static $evodd;
		$evodd++;
		if ($evodd % 2 > 0) {
			return $oddValue;
		} else {
			return $evenValue;
		}
	}

	/**
	 * Traduz os caracteres especiais de uma string para entidades HTML
	 *
	 * @static
	 * @param string $str A string original
	 * @return string A string codificada
	 */
	public static function HTML($str) {
		$k = iconv('utf-8', 'utf-8//IGNORE', $str);
		if (strlen($k) < strlen($str)) {
			$k = iconv('utf-8', 'utf-8//IGNORE', utf8_encode($str));
		}

		return htmlentities($k, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Retorna a extensão de um arquivo
	 *
	 * @static
	 * @param string $filename O nome do arquivo
	 * @return string A sua extensão (tudo minúsculo)
	 */
	public static function fileExtension($filename) {
		$p = strrpos($filename, ".");

		if (!$p) {
			$xt = "tmp";
		} else {
			$xt = substr($filename, $p + 1, strlen($filename));
		}

		return strtolower($xt);
	}

	/**
	 * Obtém o browser do usuário
	 *
	 * @static
	 * @return string O nome do browser. Possíveis valores:
	 *
	 * "OPERA"
	 * "MSIE"
	 * "NETSCAPE"
	 * "FIREFOX"
	 * "SAFARI"
	 * "KONQUEROR"
	 * "MOZILLA"
	 * "OTHER"
	 *
	 */
	public static function getUserBrowser() {
		$browser = array(
			"OPERA",
			"MSIE",
			"NETSCAPE",
			"FIREFOX",
			"SAFARI",
			"KONQUEROR",
			"MOZILLA"
		);

		$info[browser] = "OTHER";

		foreach ($browser as $parent) {
			if (($s = strpos(strtoupper($_SERVER['HTTP_USER_AGENT']), $parent)) !== FALSE) {
				$f = $s + strlen($parent);
				$version = substr($_SERVER['HTTP_USER_AGENT'], $f, 5);
				$version = preg_replace('/[^0-9,.]/', '', $version);

				$info[browser] = $parent;
				$info[version] = $version;
				break;
			}
		}

		return $parent;
	}

	/**
	 * Executa uma URL e obtém o conteúdo
	 *
	 * @static
	 * @param string $url A URL a ser executada
	 * @return string Seu conteúdo
	 *
	 * @see php.net/curl
	 */
	public static function getURLContents($url) {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 

		$data = curl_exec($ch);
		curl_close($ch);

		return $data;
	}
	
	/**
	 * Executa uma URL e obtém o conteúdo
	 *
	 * @static
	 * @param string $url A URL a ser executada
	 * @param array $postVars Array associativa com chaves e valores para enviar via POST
	 * @return string Seu conteúdo
	 *
	 * @see php.net/curl
	 */
	public static function post($url, $postVars = array()) {
		$ch = curl_init();
		
		foreach($postVars as $key => &$value) {
			$value = ($key)."=".($value);
		}
		
		$postVars_s = join("&",$postVars);
		
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
		curl_setopt($ch, CURLOPT_URL, $url);
		
		curl_setopt($ch, CURLOPT_POST, sizeof($postVars));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postVars_s);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 

		$data = curl_exec($ch);
		
		curl_close($ch);

		return $data;
	}

	/**
	 * Formata um tamanho de arquivo de forma human-readable
	 *
	 * @static
	 * @param int $b O valor do arquivo, em bytes
	 * @return string O valor formatado
	 */
	public static function formatFileSize($b) {
		$b = intval($b);
		if ($b > 1024) {
			$kb = $b / 1024;
			if ($kb > 1024) {
				$mb = $kb / 1024;
				if ($mb > 1024) {
					$gb = $mb / 1024;
					$size = number_format($gb, 2, ",", ".") . " GB";
				} else {
					$size = number_format($mb, 2, ",", ".") . " MB";
				}
			} else {
				$size = number_format($kb, 2, ",", ".") . " KB";
			}
		} else {
			$size = $b . " bytes";
		}

		return $size;
	}

	/**
	 * Remove todos os caracteres de pontuação de uma string
	 *
	 * @static
	 * @param string $text A string original
	 * @return string A string sem pontuação
	 */
	public static function stripPunctuation($text) {
		$punctuation = array(".", ",", "*", "[", "]", "(", ")", "!", "?", "@", "#", "$", "%", "<", ">", ":", "_", "{", "}", "/", "\"", "'", "+", "=", "\\");
		return str_replace($punctuation, " ", $text);
	}

	/**
	 * Remove todos os tipos de número de uma string
	 *
	 * @static
	 * @param string $text A string original
	 * @return string A string sem numeros
	 */
	public static function stripNumbers($text) {
		$numbers = array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9");
		return str_replace($numbers, " ", $text);
	}

	/**
	 * Remove alguns tipos de caracteres especiais (newlines, tabulações, nulls)
	 * Caracteres removidos: \r, \n, \t e \0
	 *
	 * @static
	 * @param string $text A string original
	 * @return string A string sem os caracteres especiais
	 */
	public static function stripSpecial($text) {
		$special = array("\r", "\n", "\t", "\0");
		return str_replace($special, " ", $text);
	}

	/**
	 * Copia as chaves e valores de uma array para um objeto
	 *
	 * @static
	 * @param object $obj O objeto alvo
	 * @param array $data A array com as propriedades e valores
	 */
	public static function copyToObject(&$obj, $data) {
		if (is_array($data) && count($data)) {
			foreach ($data as $var => $val) {
				$obj->$var = $val;
			}
		}
	}

	/**
	 * Copia as propriedades de um objeto para um array de chaves e valores
	 *
	 * @static
	 * @param object $obj O objeto cujas propriedades serão copiadas
	 * @param array $fields Uma array com a lista de propriedades
	 * @return array A array com as propriedades e valores
	 */
	public static function copyFromObject(&$obj, $fields) {
		$data = array();
		foreach($fields as $field) {
			$data[$field] = $obj->$field;
		}
		return $data;
	}
	
	
	/**
	 * Extrai o caminho correto absoluto
	 *
	 * @static
	 * @param string $path O caminho original
	 * @return string O caminho absoluto 
	 */
	public static function getAbsolutePath($path) {
		$path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
		$parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
		$absolutes = array();
		foreach ($parts as $part) {
			if ('.' == $part)
				continue;
			if ('..' == $part) {
				array_pop($absolutes);
			} else {
				$absolutes[] = $part;
			}
		}
		return implode(DIRECTORY_SEPARATOR, $absolutes);
	}

	/**
	 * Faz uma requisição HTTP à $url e retorna seu conteúdo
	 *
	 * @static
	 * @param string $url A URL a ser baixada
	 * @return string O conteúdo da página
	 */
	public static function fetchHTTP($url) {

		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);

		$output = curl_exec($ch);

		curl_close($ch);

		return $output;
	}

	/**
	 * Processa uma série de checkboxes e computa um valor de flag (inteiro) 32 bits
	 * correspondente as opções selecionadas;
	 *
	 * @static
	 * @param array $array A array de items do formulário
	 * @param string $fieldPrefix O prefixo dos campos a serem processados
	 * @param int $begin O índice do primeiro campo no intervalo
	 * @param int $end O índice do último campo no intervalo
	 * @return int O valor da flag
	 */
	public static function checkboxesToFlags($array, $fieldPrefix, $begin, $end) {
		$flagVal = 0;

		for ($i = $begin; $i <= $end; $i++) {

			$k = $fieldPrefix . $i;

			if (!isset($array[$k])) {
				continue;
			}

			if (intval($array[$k]) === 1) {
				$bin = pow(2, ($i - 1));
				$flagVal += $bin;
			}
		}

		return $flagVal;
	}

	/**
	 * Converte uma data no formato europeu em formato americano
	 *
	 * @static
	 * @param string $date A data no formato DD/MM/YYYY (europeu/internacional)
	 * @return string A data no formato MM/DD/YYYY (americano)
	 */
	public static function fixEUDate($date) {
		$x = explode("/", $date);
		return join("/", array(str_pad(intval($x[1]), 2, "0", STR_PAD_LEFT), str_pad(intval($x[0]), 2, "0", STR_PAD_LEFT), str_pad(intval($x[2]), 2, "0", STR_PAD_LEFT)));
	}

	/**
	 * Testa o valor de uma variável e retorna o atributo correto para um ítem
	 * de formulário RADIO, CHECKBOX ou OPTION.
	 *
	 * @static
	 * @param string $variable A variável que deve ser testada
	 * @param string $desiredValue O valor desejado, correspondente ao SELECTED
	 * @param string $type O tipo de input ("radio", "checkbox" ou "option")
	 * @return string O atributo para a tag HTML
	 */
	public static function formSelection($variable, $desiredValue, $type = "radio") {
		if ($type == "radio" || $type == "checkbox") {
			$selectionValue = "checked=\"checked\"";
		} else {
			$selectionValue = "selected=\"selected\"";
		}
		return ($variable == $desiredValue) ? $selectionValue : "";
	}

	/**
	 * Retorna a posição da $nth ocorrência da string $needle dentro de $haystack, ou false se não existir
	 *
	 * @static
	 * @param  string  $haystack   a string completa
	 * @param  string  $needle     a string a buscar
	 * @param  integer $nth        o índice da ocorrência
	 * @param  integer $offset     o offset do $haystack
	 * @return MIXED   integer     a posição da ocorrência
	 *               or boolean    false se não encontrar
	 */
	public static function strnpos($haystack, $needle, $nth, $offset = 0) {
		if (1 > $nth || 0 === strlen($needle)) {
			return false;
		}
		--$offset;
		do {
			$offset = strpos($haystack, $needle, ++$offset);
		} while (--$nth && false !== $offset);

		return $offset;
	}

	/**
	 * Obtém o MIME Type de um arquivo
	 *
	 * @static
	 * @param string $filename O caminho do arquivo
	 * @return string O MIME type correspondente 
	 */
	public static function getMIMEType($filename) {

		$mime_types = array(
			'txt' => 'text/plain',
			'htm' => 'text/html',
			'html' => 'text/html',
			'php' => 'text/html',
			'css' => 'text/css',
			'js' => 'application/javascript',
			'json' => 'application/json',
			'xml' => 'application/xml',
			'swf' => 'application/x-shockwave-flash',
			'flv' => 'video/x-flv',
			// images
			'png' => 'image/png',
			'jpe' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'gif' => 'image/gif',
			'bmp' => 'image/bmp',
			'ico' => 'image/vnd.microsoft.icon',
			'tiff' => 'image/tiff',
			'tif' => 'image/tiff',
			'svg' => 'image/svg+xml',
			'svgz' => 'image/svg+xml',
			// archives
			'zip' => 'application/zip',
			'rar' => 'application/x-rar-compressed',
			'exe' => 'application/x-msdownload',
			'msi' => 'application/x-msdownload',
			'cab' => 'application/vnd.ms-cab-compressed',
			// audio/video
			'mp3' => 'audio/mpeg',
			'qt' => 'video/quicktime',
			'mov' => 'video/quicktime',
			// adobe
			'pdf' => 'application/pdf',
			'psd' => 'image/vnd.adobe.photoshop',
			'ai' => 'application/postscript',
			'eps' => 'application/postscript',
			'ps' => 'application/postscript',
			// ms office
			'doc' => 'application/msword',
			'rtf' => 'application/rtf',
			'xls' => 'application/vnd.ms-excel',
			'ppt' => 'application/vnd.ms-powerpoint',
			// open office
			'odt' => 'application/vnd.oasis.opendocument.text',
			'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		);

		$ext = strtolower(array_pop(explode('.', $filename)));
		if (array_key_exists($ext, $mime_types)) {
			return $mime_types[$ext];
		} elseif (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME);
			$mimetype = finfo_file($finfo, $filename);
			finfo_close($finfo);
			return $mimetype;
		} else {
			return 'application/octet-stream';
		}
	}

	/**
	 * Verifica se o usuário selecionou um arquivo para envio no campo $file
	 *
	 * @static
	 * @param array|string $file O item da array $_FILES correspondente ao arquivo desejado, ou o nome do campo do arquivo
	 *
	 * @return bool Verdadeiro se há arquivo, falso se não
	 */
	public static function isUploading($file) {
		if(!is_array($file)) {
			$file = $_FILES[$file];
		}

		if($file['error'] === UPLOAD_ERR_NO_FILE) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Processa o upload de um arquivo via HTTP multipart/form-data.
	 * Dispara uma exceção em caso de erro
	 *
	 * @static
	 * @param array|string $file O item da array $_FILES correspondente ao arquivo desejado, ou o nome do campo do arquivo
	 * @param string $path O destino do arquivo após o upload
	 * @param string $prefix O prefixo do nome gerado
	 * @param array $validFormats Uma array com formatos válidos para o arquivo
	 *
	 * @return mixed O caminho final do arquivo, ou false se ocorrer erro
	 *
	 * @throws Exception
	 *
	 */

	public static function upload($file, $path, $prefix = "", $validFormats = null) {
		if(!is_array($file)) {
			$file = $_FILES[$file];
		}

		if ($file['error'] !== UPLOAD_ERR_OK) {
			throw new Exception("HTTP upload failed with error code: {$file['error']}", UPLOAD_FAILURE_HTTP);
		}

		$extension = Utils::fileExtension($file['name']);

		if (is_array($validFormats) && sizeof($validFormats) > 0) {
			if (!in_array(strtolower($extension), $validFormats)) {
				throw new Exception("Invalid format: {$extension}", UPLOAD_FAILURE_FORMAT);
			}
		}

		$sourceFile = $file['tmp_name'];
		$targetFile = $path . $prefix . uniqid() . "." . time() . "." . $extension;

		if (move_uploaded_file($sourceFile, $targetFile)) {
			return $targetFile;
		} else {
			throw new Exception("Failed to move file to permanent path: [{$targetFile}]", UPLOAD_FAILURE_MOVE);
		}
	}

	/**
	 * Trata uma mensagem, substituindo as variáveis presentes nos parâmetros pelos seus devidos valores.
	 *
	 * @static
	 * @param string $message A mensagem
	 * @param array $parameters Os parametros, no formato :"param" => "value"
	 * @return string A mensagem devidamente tratada
	 */
	public static function parseMessage($message, $parameters = array()) {
		foreach ($parameters as $var => $value) {
			$message = str_replace(":{$var}", $value, $message);
		}

		return $message;
	}
	
	public static function containString($string, $maxLength, $append = "...") {
		
		if(strlen($string) <= $maxLength) {
			return $string;
		} else {
			return substr($string, 0, $maxLength).$append;
		}
		
	}

	/**
	 * Valida um endereço de e-mail
	 * @static
	 * @param string $email Endereço de email
	 * @return true | false Verdadeiro ou falso
	 */
	public static function isValidEmail($email) {
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}

}