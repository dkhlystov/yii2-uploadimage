<?php

namespace dkhlystov\uploadimage\controllers;

use Yii;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\ServerErrorHttpException;
use yii\web\UploadedFile;

use dkhlystov\uploadimage\components\Item;
use dkhlystov\uploadimage\components\Settings;
use dkhlystov\uploadimage\components\UploadImageHelper;

/**
 * Controller for images uploading, removes, rotate and cropping.
 */
class ImageController extends Controller
{

	/**
	 * @inheritdoc
	 */
	public $enableCsrfValidation = false;

	/**
	 * Action for file uploading.
	 * @param string $token Widget settings token.
	 * @return void
	 */
	public function actionUpload($token)
	{
		$settings = $this->getSettings($token);

		$files = UploadedFile::getInstancesByName($settings['name']);

		$errorMaxSize = [];
		$errorFormat = [];
		$errorOther = [];
		$items = [];
		$names = [];

		UploadImageHelper::$uploadPath = $settings['uploadPath'];

		foreach ($files as $file) {
			if (!$this->validateFileSize($file, $settings['maxFileSize'])) {
				$errorMaxSize[] = $file->name;
				continue;
			}
			
			if (!in_array($file->type, ['image/gif', 'image/jpeg', 'image/png'])) {
				$errorFormat[] = $file->name;
				continue;
			}

			if ($file->error != UPLOAD_ERR_OK) {
				$errorOther[] = $file->name;
				continue;
			}

			$items[] = $this->upload($settings, $file);
			$names[] = $file->name;
		}
		
		$response = Yii::$app->getResponse();
		$response->format = \yii\web\Response::FORMAT_RAW;
		$response->getHeaders()->add('Content-Type', 'text/plain');
		return Json::encode([
			'items' => $items,
			'names' => $names,
			'errorMaxSize' => $errorMaxSize,
			'errorFormat' => $errorFormat,
			'errorOther' => $errorOther,
		]);
	}

	/**
	 * Remove temporary files.
	 * @param string $token Image widget token.
	 * @return void
	 */
	public function actionRemove($token)
	{
		$this->clearDirty($this->getSettings($token));

		return Json::encode(true);
	}

	/**
	 * Action for rotating image.
	 * @param string $token Image widget token.
	 * @return void
	 */
	public function actionRotate($token)
	{
		$settings = $this->getSettings($token);

		UploadImageHelper::$uploadPath = $settings['uploadPath'];

		$file = UploadImageHelper::rotate($settings['file'], $settings['maxImageWidth'], $settings['maxImageHeight'], $settings['quality']);

		$thumb = null;
		if ($settings['thumbKey'] !== null)
			$thumb = UploadImageHelper::thumb($file, $settings['thumbWidth'], $settings['thumbHeight'], $settings['quality']);

		$item = Item::widget([
			'token' => $token,
			'file' => $file,
			'thumb' => $thumb,
		]);

		$this->clearDirty($settings);

		return $item;
	}

	/**
	 * Crop image to make a thumb action.
	 * @param string $token Image widget token.
	 * @return void
	 */
	public function actionCrop($token)
	{
		$settings = $this->getSettings($token);

		$request = Yii::$app->getRequest();

		$thumb = UploadImageHelper::thumb(
			$settings['file'],
			$settings['thumbWidth'],
			$settings['thumbHeight'],
			$settings['quality'],
			$request->get('x', null),
			$request->get('y', null)
		);

		$item = Item::widget([
			'token' => $token,
			'file' => $settings['file'],
			'thumb' => $thumb,
		]);

		$settings['file'] = null;
		$this->clearDirty($settings);

		return $item;
	}

	/**
	 * Load settings by token. Exceptions throws if token is not valid.
	 * @param string $token Settings token.
	 * @return mixed Loaded settings.
	 */
	private function getSettings($token)
	{
		$settings = Settings::load($token);
		if ($settings === false)
			throw new ServerErrorHttpException("Token is not valid.");

		return $settings;
	}

	/**
	 * Maximum file size validator.
	 * @param UploadedFile $file File to check.
	 * @param integer $maxFileSize Maximum file size in bytes.
	 * @return boolean
	 */
	private function validateFileSize(UploadedFile $file, $maxFileSize)
	{
		if ($file->error == UPLOAD_ERR_INI_SIZE)
			return false;

		if ($file->error == UPLOAD_ERR_FORM_SIZE)
			return false;

		return $file->size < $maxFileSize;
	}

	/**
	 * Uploading file and render image item.
	 * @param array $settings Widget settings.
	 * @param UploadedFile $file File to upload.
	 * @return string Rendered image item.
	 */
	private function upload($settings, UploadedFile $file)
	{
		$name = UploadImageHelper::save($file, $settings['maxImageWidth'], $settings['maxImageHeight'], $settings['quality']);

		$thumb = null;
		if ($settings['thumbKey'] !== null)
			$thumb = UploadImageHelper::thumb($name, $settings['thumbWidth'], $settings['thumbHeight'], $settings['quality']);

		return Item::widget([
			'fileKey' => $settings['fileKey'],
			'thumbKey' => $settings['thumbKey'],
			'file' => $name,
			'thumb' => $thumb,
			'width' => $settings['width'],
			'height' => $settings['height'],
			'thumbWidth' => $settings['thumbWidth'],
			'thumbHeight' => $settings['thumbHeight'],
			'maxImageWidth' => $settings['maxImageWidth'],
			'maxImageHeight' => $settings['maxImageHeight'],
			'baseName' => $settings['baseName'],
			'data' => $settings['data'],
			'quality' => $settings['quality'],
			'uploadPath' => $settings['uploadPath'],
			'buttons' => $settings['buttons'],
		]);
	}

	/**
	 * Clear temporary file and thumb.
	 * @param array $settings Image widget settings.
	 * @return void
	 */
	private function clearDirty($settings)
	{
		$base = Yii::getAlias('@webroot');

		$oFile = $settings['originalFile'];
		$file = $settings['file'];
		if ($file !== null && $file !== $oFile)
			@unlink($base . $file);

		$oThumb = $settings['originalThumb'];
		$thumb = $settings['thumb'];
		if ($thumb !== null && $thumb !== $oThumb)
			@unlink($base . $thumb);
	}

}
