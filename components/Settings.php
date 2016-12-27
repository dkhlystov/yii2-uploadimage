<?php

namespace dkhlystov\uploadimage\components;

use Yii;

/**
 * Helper that saves and load settings.
 */
class Settings
{

	/**
	 * Save data and return token.
	 * @param mixed $data Data need to save.
	 * @return string Token associated with data.
	 */
	public static function save($data)
	{
		self::clear();

		$contents = serialize($data);
		$token = md5($contents);

		$dir = Yii::getAlias('@runtime/uploadimage');
		if (!is_dir($dir))
			@mkdir($dir);

		if (@file_put_contents($dir . '/' . $token, $contents) === false)
			return false;

		return $token;
	}

	/**
	 * Load data by token.
	 * @param string $token Token associated with data.
	 * @return mixed|false Loaded data. False if token not found.
	 */
	public static function load($token)
	{
		$dir = Yii::getAlias('@runtime/uploadimage');

		$contents = @file_get_contents($dir . '/' . $token);
		if ($contents === false)
			return false;

		$data = @unserialize($contents);

		return $data;
	}

	/**
	 * Clear old tokens.
	 * @return void
	 */
	protected static function clear()
	{
		$dir = Yii::getAlias('@runtime/uploadimage');

		if (!is_dir($dir))
			return;

		$d = strtotime('-1 day');

		foreach (scandir($dir) as $name) {
			if ($name[0] === '.')
				continue;

			$filename = $dir . '/' . $name;

			if (filemtime($filename) < $d)
				@unlink($filename);
		}
	}

}
