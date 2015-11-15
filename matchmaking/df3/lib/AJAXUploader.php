<?php
/**
 * Diesel Framework
 * Copyright © LQDI Technologies - 2011
 * http://www.lqdi.net
 *
 * AJAXUploader 1.0
 *		JavaScript IFRAME asynchronous upload utility
 *
 * @author Aryel 'DfKimera' Tupinambá
 *
 */

class AJAXUploader {


	public static $lastErrorCode = "";

	/**
	 * Processes the file upload
	 *
	 * @static
	 * @param string $fieldName The name of the file input field in POST
	 * @param string $targetPath The target path, relative to the application root
	 * @param string $filename The file name. If omitted, will be automatically generated using uniqid()
	 * @param int $maxSize The maximum size of the upload
	 * @return bool|string False on failure or the file final path relative to the application root on success
	 */
	public static function upload($fieldName, $targetPath, $filename = NULL, $maxSize = 1048576) {

		// Check if the file field actually exists
		if(!isset($_FILES[$fieldName])) {
			self::$lastErrorCode = "NOT_RECEIVED";
			return false;
		}

		$f = $_FILES[$fieldName];

		// Check if the size limit has exceeded
		if(intval($f['size']) > $maxSize) {
			self::$lastErrorCode = "FILE_SIZE_EXCEEDED";
			return false;
		}

		// Check if we had any POST upload errors
		if(intval($f['error']) > UPLOAD_ERR_OK) {
			self::$lastErrorCode = "UPLOAD_ERR_{$f['error']}";
			return false;
		}

		// Check if target directory actually exists
		if(!is_dir($targetPath)) {
			self::$lastErrorCode = "INVALID_TARGET_PATH";
			return false;
		}

		// Check if target directory is writable
		if(!is_writable($targetPath)) {
			self::$lastErrorCode = "PERMISSION_ERROR";
			return false;
		}

		$ext = Utils::fileExtension($f['name']);

		// If filename is omitted, generate one
		if($filename == NULL) {
			$filename = self::generateName($ext);
		}

		// Normalizes the target file path
		$slash = $targetPath[strlen($targetPath)-1];

		if($slash == "/" || $slash == "\\") {
			$path = "{$targetPath}{$filename}";
		} else {
			$path = "{$targetPath}/{$filename}";
		}

		// Moves the file from the upload temp dir into the permanent file path
		$status = move_uploaded_file($f['tmp_name'], $path);

		// Check if the move operation was succesfull
		if(!$status) {
			self::$lastErrorCode = "MOVE_FAILED";
			return false;
		}

		// Check if somehow the file does not exist after moving
		if(!file_exists($path)) {
			self::$lastErrorCode = "FILE_NOT_FOUND";
			return false;
		}

		// All good!
		self::$lastErrorCode = "UPLOAD_OK";

		return stripslashes($path);

	}

	/**
	 * Makes a copy of a recently uploaded file into another directory
	 *
	 * @static
	 * @param string $sourceFile The source file
	 * @param string $targetPath The target path
	 * @param string $filename The file name. If omitted, will be automatically generated using uniqid()
	 * @param bool $eraseFile Should we erase the original source file?
	 * @return bool|string False on failure or the final file copy path on success
	 */
	public static function hostFileCopy($sourceFile, $targetPath, $filename = null, $eraseFile = false) {

		// Check if the target directory exists and is writable
		if(!is_dir($targetPath) || !is_writeable($targetPath)) {
			return false;
		}

		$ext = Utils::fileExtension($sourceFile);

		// If filename is omitted, generate one
		if($filename == NULL) {
			$filename = uniqid('FL_',true).".".$ext;
		}

		// Normalizes the target file path
		$slash = $targetPath[strlen($targetPath)-1];

		if($slash == "/" || $slash == "\\") {
			$targetFile = $targetPath . $filename;
		} else {
			$targetFile = $targetPath . "/{$filename}";
		}

		// Copies the file into the new directory
		$status = copy($sourceFile, $targetFile);


		if(!$status) {
			return false;
		} else {

			if($eraseFile) {
				unlink($sourceFile);
			}

			return stripslashes($targetFile);
		}

	}

	/**
	 * Generates an unique file name
	 *
	 * @static
	 * @param string $ext The file extension
	 * @return string
	 */
	public static function generateName($ext) {
		return uniqid('upload_',true).".".$ext;
	}

	/**
	 * Check if the submitted file has a valid extension
	 *
	 * @static
	 * @param string $fieldName The name of the POST file field
	 * @param array $formatList The list of allowed formats
	 * @return boolean True if valid, false if invalid
	 */
	public static function validateFormats($fieldName, $formatList = array()) {
		$ext = Utils::fileExtension($_FILES[$fieldName]['name']);
		if(in_array($ext, $formatList)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Handles the IFRAME asynchronous request, sending back to the front-end a JS callback trigger
	 *
	 * @static
	 * @param string $status The upload status code
	 * @param string $filePath The final file path, relative to application root
	 * @param string $data Additional data to send back to the front-end
	 * @param string $callbackFunction The name of the callback function
	 */
	public static function handleRequest($status, $filePath = "", $data = "", $callbackFunction = "uploader.triggerCallback") {
		discard_output();

		$errorCode = self::$lastErrorCode;

		echo '<html>';
			echo '<head>';

				echo '<script type="text/javascript">';
					echo 'window.onload = function () {';
					echo "	if(top.{$callbackFunction}) { top.{$callbackFunction}(\"".$status.'","'.$filePath.'", "'.$errorCode.'"); }';
					echo '};';
				echo '</script>';

			echo '</head>';
			echo '<body>';

			echo '<div id="returnedData">';
				echo $data;
			echo "</div>";

			echo '&nbsp;';

			print_r($_FILES);
			print_r($_POST);
			print_r($_GET);

			echo '</body>';
		echo '</html>';

		exit();

	}


}
?>
