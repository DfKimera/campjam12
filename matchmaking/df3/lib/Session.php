<?php
/**
 * Diesel Framework
 * Copyright © LQDI Technologies - 2011
 * http://www.lqdi.net
 *
 * Biblioteca de gerenciamento de sessão de usuário
 *
 * @author Aryel Tupinambá
 */

interface ISessionUser {
	
	/**
	 * Deve retornar o ID do usuário
	 */
	public function getUserID();
	
	/**
	 * Estática: deve retornar o objeto do usuário à partir de seu ID
	 *
	 * @param $id O ID do usuário
	 */
	public static function load($id);
	
}

class Session {
	
	/**
	 * @static
	 * @var type O nome da classe que representa um usuário (default: User)
	 */
	public static $userClass = "User";
	
	/**
	 * @static
	 * @var boolean Há um usuário logado na sessão?
	 */
	public static $isAuthenticated = false;
	
	/**
	 * @static
	 * @var int O ID do usuário logado nesta sessão (quando não logado, é igual a null)
	 */
	public static $userID = null;
	
	/**
	 * @static
	 * @var User O objeto que representa o usuário logado nesta sessão
	 */
	public static $user = null;
	
	/**
	 * Inicializa a sessão
	 *
	 * @static
	 * @param string $userClass A classe que representa um usuário no sistema (default: User)
	 */
	public static function init($userClass = "User") {
		self::$userClass = $userClass;
		session_start();
	}
	
	/**
	 * Recarrega uma sessão de usuário para a biblioteca, quando existir.
	 * Se há um usuário logado na sessão, automaticamente carrega seu objeto
	 *
	 * @static
	 */
	public static function reload() {
		if($_SESSION['isAuthenticated'] == true) {
			self::$isAuthenticated = true;
			self::$userID = intval($_SESSION['userID']);
			self::$user = call_user_func_array( array(self::$userClass, "load"), array(self::$userID) );
		}
	}
	
	/**
	 * Cria uma nova sessão de usuário e carrega automaticamente o seu objeto.
	 *
	 * @static
	 * @param string $userID O ID do usuário no sistema
	 */
	public static function create($userID) {
		$_SESSION['isAuthenticated'] = true;
		$_SESSION['userID'] = intval($userID);
		
		self::reload();
	}
	
	/**
	 * Faz com que o usuário saia da sessão, mas sem excluir as outras variáveis de sessão.
	 *
	 * @static
	 */
	public static function logoff() {
		$_SESSION['isAuthenticated'] = false;
		$_SESSION['userID'] = null;
		unset($_SESSION['userID']);
		
		self::reload();
	}
	
	/**
	 * Destrói a sessão, apagando todas as variáveis de sessão já salvas
	 *
	 * @static
	 */
	public static function destroy() {
		$_SESSION['isAuthenticated'] = false;
		$_SESSION['userID'] = null;
		
		session_destroy();
	}
	
	
}

?>
