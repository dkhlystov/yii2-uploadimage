<?php

namespace dkhlystov\uploadimage;

use Yii;

/**
 * Upload image module
 */
class Module extends \yii\base\Module
{

	/**
	 * @var string Path to directory where images will be upload (relative to web root).
	 */
	public $uploadPath = '/upload';

	/**
	 * @var integer Max file size for upload in MB. Default is 64;
	 */
	public $maxFileSize = 64;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		static::addTranslation();
	}

	/**
	 * Adding translation to i18n
	 * @return void
	 */
	public static function addTranslation()
	{
		if (!isset(Yii::$app->i18n->translations['uploadimage'])) {
			Yii::$app->i18n->translations['uploadimage'] = [
				'class' => 'yii\i18n\PhpMessageSource',
				'sourceLanguage' => 'en-US',
				'basePath' => __DIR__ . '/messages',
			];
		}
	}

}
