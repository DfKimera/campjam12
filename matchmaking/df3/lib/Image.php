<?php
/**
 * Diesel Framework
 * Copyright © LQDI Technologies - 2011
 * http://www.lqdi.net
 *
 * Image 1.0
 *		GD Image utility library
 *
 * @author Aryel 'DfKimera' Tupinambá
 *
 */

class Image {

	/**
	 * Aplica uma âncora ao caminho, obtendo o caminho absoluto do sistema de arquivos.
	 *
	 * @static
	 * @param string $location O caminho relativo
	 * @return string O caminho absoluto
	 */
	public static function anchor($location) {
		return ((!defined('ENV_WINDOWS'))?"/":"").Utils::getAbsolutePath(dirname(__FILE__) . "/../../{$location}");
	}

	/**
	 * Cria um objeto de imagem à partir de uma imagem existente
	 *
	 * @param string $imagePath O caminho da imagem
	 * @param boolean $anchor Aplicar âncora ao caminho?
	 *
	 * @return resource $image A imagem criada
	 */
	public static function open($imagePath, $anchor = true) {

		if($anchor) {
			$imagePath = Image::anchor($imagePath);
		}

		$extension = Utils::fileExtension($imagePath);

		switch ($extension) {
			case 'jpg':
			case 'jpeg':
			case 'pjpeg':
				return imagecreatefromjpeg($imagePath);
				break;
			case 'png':
				return imagecreatefrompng($imagePath);
				break;
			case 'gif':
				return imagecreatefromgif($imagePath);
				break;
			default:
				return false;
		}
	}

	/**
	 * Cria uma nova imagem vazia
	 *
	 * @static
	 * @param int $width A largura da imagem
	 * @param int $height A altura da imagem
	 * @return resource A imagem GD
	 */
	public static function create($width, $height) {
		return imagecreatetruecolor($width, $height);
	}

	/**
	 * Calcula as coordenadas do eixo X para posicionar uma string de texto no centro da imagem
	 *
	 * @static
	 * @param string $string A string de texto
	 * @param int $width A largura da imagem
	 * @param int $size O tamanho da fonte
	 * @param string $font O arquivo de fonte
	 * @return float A posição no eixo X
	 */
	public static function calculateTextCenter($string, $width, $size = 12, $font = "arial.ttf") {
		$bb = imagettfbbox($size, 0, $font, $string);
		$text_width = $bb[2] - $bb[0];
		return ($width/2) - ($text_width/2);
	}

	/**
	 * Calcula as coordenadas do eixo Y para posicionar uma string de texto no centro da imagem
	 *
	 * @static
	 * @param string $string A string de texto
	 * @param int $height A altura da imagem
	 * @param int $size O tamanho da fonte
	 * @param string $font O arquivo de fonte
	 * @return float A posição no eixo Y
	 */
	public static function calculateTextMiddle($string, $height, $size = 12, $font = "arial.ttf") {
		$bb = imagettfbbox($size, 0, $font, $string);
		$text_height = $bb[1] - $bb[3];
		return ($height/2) - ($text_height/2);
	}

	/**
	 * Renderiza uma linha de texto na imagem
	 *
	 * @static
	 * @param resource $image A imagem GD
	 * @param string $string A linha de texto
	 * @param int $x A posição no eixo X
	 * @param int $y A posição no eixo Y
	 * @param int $size O tamanho da fonte
	 * @param string $color A cor em hexadecimal completo (Ex.: #FFFFFF)
	 * @param string $font O arquivo de fonte
	 */
	public static function addText($image, $string, $x, $y, $size = 12, $color = "#FFFFFF", $font = "arial.ttf") {
		$color = self::hex2Color($image, $color);

		imagettftext($image, $size, 0, $x, $y, $color, $font, $string);
	}

	/**
	 * Salva a imagem no disco
	 *
	 * @static
	 * @param resource $image A imagem GD
	 * @param string $path O caminho físico no disco
	 */
	public static function save($image, $path) {
		$format = Utils::fileExtension(basename($path));
		
		switch($format) {
			case "png":
				imagepng($image, $path);
				break;
			case "jpeg":
			case "jpg":
			default:
				imagejpeg($image, $path);
				break;
		}
		
	}

	/**
	 * Abre e renderiza uma imagem
	 *
	 * @static
	 * @param string $imagePath O caminho relativo da imagem
	 */
	public static function show($imagePath) {

		$imagePath = Image::anchor($imagePath);
		
		self::render(self::open($imagePath, false));

	}

	/**
	 * Renderiza uma imagem, enviando seu conteúdo ao buffer de saída
	 *
	 * @static
	 * @param resource $image A imagem GD
	 * @param string $format O formato da imagel (opções: jpeg ou png)
	 */
	public static function render($image, $format = 'jpeg') {
		discard_output();
		switch($format) {
			case "png":
				header('Content-type: image/png');
				imagepng($image);
				break;
			case "jpeg":
			default:
				header('Content-type: image/jpeg');
				imagejpeg($image);
				break;
		}

		exit();
	}

	const RESIZE_MODE_SCALE = 0;
	const RESIZE_MODE_PROPORTIONAL = 1;
	const RESIZE_MODE_AR_WIDTH = 2;
	const RESIZE_MODE_AR_HEIGHT = 3;

	/**
	 * Abre e redimensiona uma imagem, retornando seu objeto GD
	 * @static
	 * @param string $imagePath O caminho da imagem original
	 * @param int $width A largura desejada
	 * @param int $height A altura desejada
	 * @param int $mode O modo desejado (ver Image::RESIZE_MODE_*)
	 * @param string $backgroundColor A cor de fundo, em hedaxecimal completo (ex.: #FFFFFF)
	 * @return resource A imagem GD
	 */
	public static function resize($imagePath, $width, $height, $mode = Image::RESIZE_MODE_SCALE, $backgroundColor = null) {
		$resized = imagecreatetruecolor($width, $height);
		imageantialias($resized, true);

		imagealphablending($resized, true);
		imagesavealpha($resized, true);

		if($backgroundColor != null) {
			imagefill($resized,0,0, Image::hex2Color($resized, $backgroundColor));
		}

		$origin = self::open($imagePath);

		imagealphablending($origin, true);
		imagesavealpha($origin, true);

		$orig_W = imagesx($origin);
		$orig_H = imagesy($origin);

		switch ($mode) {
			case Image::RESIZE_MODE_PROPORTIONAL:

				if($orig_W > $orig_H) {

					$thumb_W = $width;
					$thumb_H = ($width * $orig_H) / $orig_W;
					$thumb_X = ($width / 2) - ($thumb_W) / 2;
					$thumb_Y = ($height / 2) - ($thumb_H) / 2;

				} else if($orig_W < $orig_H) {

					$thumb_W = ($orig_W * $height) / $orig_H;
					$thumb_H = $height;
					$thumb_X = ($width / 2) - ($thumb_W) / 2;
					$thumb_Y = ($height / 2) - ($thumb_H) / 2;

				} else {

					$thumb_W = $width;
					$thumb_H = $height;
					$thumb_X = 0;
					$thumb_Y = 0;

				}

				break;

			case Image::RESIZE_MODE_AR_WIDTH:

				$thumb_W = $width;
				$thumb_H = ($width * $orig_H) / $orig_W;
				$thumb_X = ($width / 2) - ($thumb_W) / 2;
				$thumb_Y = ($height / 2) - ($thumb_H) / 2;

				break;

			case Image::RESIZE_MODE_AR_HEIGHT:

				$thumb_W = ($orig_W * $height) / $orig_H;
				$thumb_H = $height;
				$thumb_X = ($width / 2) - ($thumb_W) / 2;
				$thumb_Y = ($height / 2) - ($thumb_H) / 2;

				break;

			case Image::RESIZE_MODE_SCALE:
			default:

				$thumb_W = $width;
				$thumb_H = $height;
				$thumb_X = 0;
				$thumb_Y = 0;

				break;
		}

		imagecopyresampled($resized, $origin, $thumb_X, $thumb_Y, 0, 0, $thumb_W, $thumb_H, $orig_W, $orig_H);

		return $resized;
	}

	/**
	 * Abre e recorta uma imagem, retornando seu objeto GD
	 * O recorte é feito entre os pontos A e B, e colado em uma imagem no tamanho especificado
	 *
	 * @static
	 * @param string $imagePath O caminho da imagem original
	 * @param int $width A largura da imagem final
	 * @param int $height A altura da imagem final
	 * @param int $x1 O eixo X do ponto A
	 * @param int $y1 O eixo Y do ponto A
	 * @param int $x2 O eixo X do ponto B
	 * @param int $y2 O eixo Y do ponto B
	 * @return resource
	 */
	public static function crop($imagePath, $width, $height, $x1, $y1, $x2, $y2) {
		$cropped = imagecreatetruecolor($width, $height);
		imageantialias($cropped, true);


		imagealphablending( $cropped, true );
		imagesavealpha( $cropped, true );

		$origin = self::open($imagePath);

		imagealphablending( $origin, true );
		imagesavealpha( $origin, true );

		imagecopyresampled($cropped, $origin, 0, 0, $x1, $y1, $width, $height, ($x2-$x1), ($y2-$y1));

		return $cropped;
	}

	/**
	 * Abre, redimensiona e salva uma imagem
	 *
	 * @static
	 * @param string $imagePath O caminho da imagem original
	 * @param string $targetPath O caminho da imagem redimensionada
	 * @param int $width A largura desejada da imagem
	 * @param int $height A altura desejada da imagem
	 * @param int $mode O modo de redimensionamento (ver as constantes RESIZE_MODE_*)
	 * @param string $backgroundColor A cor de fundo, em hexadecimal (ex.: #FFFFFF)
	 *
	 * @return string O caminho da imagem redimensionada
	 */
	public static function saveResized($imagePath, $targetPath, $width, $height, $mode = Image::RESIZE_MODE_SCALE, $backgroundColor = null) {

		$resized = Image::resize($imagePath, $width, $height, $mode, $backgroundColor);

		//imagejpeg($resized, $targetPath, 100);
		self::saveImage($resized, $targetPath);

		return $targetPath;

	}

	/**
	 * Abre, recorta e salva uma imagem
	 * O recorte é feito entre os pontos A e B, e colado em uma imagem no tamanho especificado
	 *
	 * @static
	 * @param string $imagePath O caminho da imagem original
	 * @param string $targetPath O caminho da imagem recortada
	 * @param int $width A largura da imagem final
	 * @param int $height A altura da imagem final
	 * @param int $x1 O eixo X do ponto A
	 * @param int $y1 O eixo Y do ponto A
	 * @param int $x2 O eixo X do ponto B
	 * @param int $y2 O eixo Y do ponto B
	 * @return string O caminho da imagem recortada
	 */
	public static function saveCropped($imagePath, $targetPath, $width, $height, $x1, $y1, $x2, $y2) {

		$cropped = Image::crop($imagePath, $width, $height, $x1, $y1, $x2, $y2);

		//imagejpeg($cropped, $targetPath, 100);
		self::saveImage($cropped, $targetPath);

		return $targetPath;

	}

	/**
	 * Abre, redimensiona e exibe uma imagem.
	 * Caso a imagem já tenha sido redimensionada, ela será aberta do cache.
	 * Você pode opcionalmente desabilitar o caching.
	 *
	 * @static
	 * @param string $imagePath O caminho da imagem original
	 * @param string $cacheFolder O caminho da pasta de cache
	 * @param int $width A largura desejada da imagem
	 * @param int $height A altura desejada da imagem
	 * @param int $mode O modo de redimensionamento (ver as constantes RESIZE_MODE_*)
	 * @param string $backgroundColor A cor de fundo, em hexadecimal (ex.: #FFFFFF)
	 * @param bool $noCache Desabilitar o cache?
	 */
	public static function showResized($imagePath, $cacheFolder, $width, $height, $mode = Image::RESIZE_MODE_SCALE, $backgroundColor = null, $noCache = false) {

		$cachedImage = $cacheFolder . "/R{$width}x{$height}_{$mode}_" . basename($imagePath).".jpg";
		if(!$noCache && file_exists($cachedImage)) {
			Image::show($cachedImage);
			return;
		}

		$resized = Image::resize($imagePath, $width, $height, $mode, $backgroundColor);

		if(!$noCache && $cacheFolder != NULL) {
			$cachePath = "{$cacheFolder}/R{$width}x{$height}_{$mode}_".basename($imagePath).".jpg";
			$cachePath = Image::anchor($cachePath);
			imagejpeg($resized, $cachePath, 100);
		}

		discard_output();

		header("Content-type: image/jpeg");
		imagejpeg($resized, null, 100);

		exit();
		
	}

	/**
	 * Abre, recorta e exibe uma imagem.
	 * Caso a imagem já tenha sido recortada, ela será aberta do cache.
	 * Você pode opcionalmente desabilitar o caching.
	 *
	 * @static
	 * @param string $imagePath O caminho da imagem original
	 * @param string $cacheFolder O caminho da pasta de cache
	 * @param int $width A largura da imagem
	 * @param int $height A altura da imagem
	 * @param int $x1 O X do primeiro ponto
	 * @param int $y1 O Y do primeiro ponto
	 * @param int $x2 O X do segundo ponto
	 * @param int $y2 O Y do segundo ponto
	 * @param bool $noCache Desabilitar cache?
	 */
	public static function showCropped($imagePath, $cacheFolder, $width, $height, $x1, $y1, $x2, $y2, $noCache = false) {

		$cachedImage = $cacheFolder . "/C{$width}x{$height}_{$x1},{$y1}_{$x2},{$y2}_" . basename($imagePath).".jpg";

		if(!$noCache && file_exists($cachedImage)) {
			Image::show($cachedImage);
			return;
		}

		$cropped = self::crop($imagePath, $width, $height, $x1, $y1, $x2, $y2);

		if(!$noCache && $cacheFolder != NULL) {
			$cachePath = "{$cacheFolder}/C{$width}x{$height}_{$x1},{$y1}_{$x2},{$y2}_" . basename($imagePath).".jpg";
			$cachePath = Image::anchor($cachePath);
			imagejpeg($cropped, $cachePath, 100);
		}

		discard_output();

		header("Content-type: image/jpeg");
		imagejpeg($cropped);

		exit();
		
	}

	/**
	 * Converte uma string hexadecimal em cor, pré-alocando-a na imagem
	 *
	 * @static
	 * @param resource $image A imagem GD
	 * @param string $hexStr A string hexadecimal
	 * @return int O índice da cor na imagem
	 */
	public static function hex2Color($image, $hexStr) {

		$colorVal = hexdec($hexStr);
		$color_R = 0xFF & ($colorVal >> 0x10);
		$color_G = 0xFF & ($colorVal >> 0x8);
		$color_B = 0xFF & $colorVal;

		return imagecolorallocate($image, $color_R, $color_G, $color_B);

	}

	/**
	 * Salva a imagem cropada ou recortada levando em conta a sua extensão
	 *
	 * @static
	 * @param resource $tempImage Imagem cropada ou recortada
	 * @param resource $savePath Caminho onde deve ser salva
	 * @param int $imageQuality Qualidade da imagem padrão 100%
	 *
	 * @author Dan Jesus <daniel.jesus@lqdi.net>
	 */
	public function saveImage($tempImage, $savePath, $imageQuality=100) {

		//Captura a extensão da miagem
		$extension = strrchr($savePath, '.');
		$extension = strtolower($extension);

		switch ($extension) {
			case '.jpg':
			case '.jpeg':
			case '.pjpeg':
				if (imagetypes() & IMG_JPG) {
					imagejpeg($tempImage, $savePath, $imageQuality);
				}
				break;

			case '.gif':
				if (imagetypes() & IMG_GIF) {
					imagegif($tempImage, $savePath);
				}
				break;

			case '.png':
				// *** Scale quality from 0-100 to 0-9
				$scaleQuality = round(($imageQuality / 100) * 9);

				// *** Invert quality setting as 0 is best, not 9
				$invertScaleQuality = 9 - $scaleQuality;

				if (imagetypes() & IMG_PNG) {
					imagepng($tempImage, $savePath, $invertScaleQuality);
				}
				break;
			default:
				break;
		}
	}

}
?>
