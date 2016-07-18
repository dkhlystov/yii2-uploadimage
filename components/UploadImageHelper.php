<?php

namespace uploadimage\components;

use Yii;
use yii\web\UploadedFile;

use helpers\ImageFile;

/**
 * Helper for work with files and thumbs.
 */
class UploadImageHelper
{

	/**
	 * @var string Path to directory where images will be upload relative to web root.
	 */
	public static $uploadPath = '/upload';

	/**
	 * Saving file to temporary directory and resize it if needed.
	 * @param UploadedFile $file File needs to save.
	 * @param integer $maxImageWidth Maximum image width.
	 * @param integer $maxImageHeight Maximum image height.
	 * @param integer $quality Image quality (for jpeg only).
	 * @return string Name of file relative to web root.
	 */
	public static function save(UploadedFile $file, $maxImageWidth, $maxImageHeight, $quality)
	{
		$name = self::generateName($file->name);
		$filename = Yii::getAlias('@webroot') . $name;

		$file->saveAs($filename);

		$image = new ImageFile($filename, ['jpegQuality' => $quality]);
		$image->bounds($maxImageWidth, $maxImageHeight);
		$image->save();

		return $name;
	}

	/**
	 * Making thumb for image.
	 * @param string $name Image file name relative to web root.
	 * @param integer $width Thumb width.
	 * @param integer $height Thumb height.
	 * @param integer $quality Thumb quality (for jpeg only).
	 * @param integer|null $x Horizontal offset in pixels. Center if null.
	 * @param integer|null $y Vertical offset in pixels. Center if null.
	 * @return string Name of thumb file relative to web root.
	 */
	public static function thumb($name, $width, $height, $quality, $x = null, $y = null)
	{
		$thumb = self::generateName($name);

		$image = new ImageFile(Yii::getAlias('@webroot') . $name, ['jpegQuality' => $quality]);
		$image->crop($width, $height, $x, $y);
		$image->save(Yii::getAlias('@webroot') . $thumb);

		return $thumb;
	}

	/**
	 * Rotate image
	 * @param string $name Name of rotating file relative to web root.
	 * @return string Name of rotated file relative to web root.
	 */
	public static function rotate($name, $quality)
	{
		$filename = Yii::getAlias('@webroot') . $name;

		$newName = self::generateName($name);
		$newFilename = Yii::getAlias('@webroot') . $newName;

		$image = new ImageFile($filename, ['jpegQuality' => $quality]);
		$image->rotate(270);
		$image->save($newFilename);

		return $newName;
	}

	/**
	 * Generate name for file in upload path.
	 * @param string $filename Original file name. Extension of this file will be used.
	 * @return string
	 */
	private static function generateName($filename)
	{
		$name = str_replace('.', '', uniqid('', true));
		$name .= '.' . pathinfo($filename, PATHINFO_EXTENSION);
		return self::$uploadPath . '/' . $name;
	}

}
