<?php
/**
 * Diesel Framework
 * Copyright © LQDI Technologies - 2011
 * http://www.lqdi.net
 *
 * Event manager
 * Gerenciador de eventos
 *
 * @author Aryel Tupinambá
 */

class EventManager {
	
	public static $events = array();	
	public static $listeners = array();

	/**
	 * @static
	 * @param $eventName
	 * @param array $parameters
	 */
	public static function registerEvent($eventName, $parameters = array()) {
		self::$events[$eventName] = $parameters;
	}

	public static function addEventListener($eventName, $listener) {
		if(!is_array(self::$listeners[$eventName])) {
			self::$listeners[$eventName] = array();
		}

		array_push(self::$listeners[$eventName], $listener);
	}

	public static function triggerEvent($eventName, $eventData = array(), $caller = "EventManager") {
		if(!is_array(self::$listeners[$eventName])) {
			return;
		}

		// Check if event is registered
		$eventMetadata = self::$events[$eventName];

		foreach(self::$listeners[$eventName] as $listener) {

			if($eventMetadata !== NULL) { // If event was previously registered

				if(sizeof($eventData) != sizeof($eventMetadata)) { // Check if event signature matches given event data
					error("Cannot trigger event '{$eventName}', event requires ".sizeof($eventMetadata)." parameters, trigger was called with ".sizeof($eventData)." instead");
				}

				$result = call_user_func_array($listener, $eventData);

			} else {
				
				// Calls the event listener with the event parameters
				$result = call_user_func($listener, $eventData);	

			}

			if($result === false) { // "return false" on a listener will prevent bubbling
				break;
			}

		}

	}

}
