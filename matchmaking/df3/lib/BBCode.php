<?php
/**
 * Diesel Framework
 * Copyright © LQDI Technologies - 2011
 * http://www.lqdi.net
 *
 * BBCode 1.0
 *		BBCode parsing library
 *
 * @author Aryel 'DfKimera' Tupinambá
 *
 */

class BBCode {

	public static $registeredTags = array(
		"/\n/" => "<br />",
		"/\[b\]/" => "<strong>",
		"/\[\/b\]/" => "</strong>",
		"/\[i\]/" => "<em>",
		"/\[\/i\]/" => "</em>",
		"/\[u\]/" => "<u>",
		"/\[\/u\]/" => "</u>",
		"/\[url=([^\]]+)\](.*?)\[\/url\]/" => '<a href="$1">$2</a>',
		"/\[url\](.*?)\[\/url\]/" => '<a href="$1">$1</a>',
		"/\[img\](.*?)\[\/img\]/" => '<img src="$1" />',
		"/\[color=(.*?)\](.*?)\[\/color\]/" => '<font color="$1">$2</font>',
		"/\[code\](.*?)\[\/code\]/" => '<span class="codeStyle">$1</span>&nbsp;',
		"/\[quote.*?\](.*?)\[\/quote\]/" => '<span class="quoteStyle">$1</span>&nbsp;'

	);

	/**
	 * Strips a string of all BBCode tags
	 *
	 * @static
	 * @param string $text_to_search Text with BBCode text
	 * @return string Text without BBCode tags
	 */
	public static function stripBBCode($text_to_search) {
		$pattern = '|[[\/\!]*?[^\[\]]*?]|si';
		$replace = '';
		return preg_replace($pattern, $replace, $text_to_search);
	}

	/**
	 * Registers a new BBCode tag for parsing
	 *
	 * @static
	 * @param $bbPattern The BBCode tag pattern
	 * @param $htmlReplacement The HTML replacement
	 */
	public static function registerTag($bbPattern, $htmlReplacement) {
		self::$registeredTags[$bbPattern] = $htmlReplacement;
	}

	/**
	 * Parses a BBCode string, returning it's corresponding HTML output
	 *
	 * @static
	 * @param string $body The string with BBCode
	 * @return mixed|string The parsed string
	 */
	public static function parse($body) {
		$body = trim($body);

		foreach(self::$registeredTags as $bbPattern => $htmlReplacement) {
			$body = preg_replace($bbPattern, $htmlReplacement, $body);
		}

		return $body;
	}
}

?>