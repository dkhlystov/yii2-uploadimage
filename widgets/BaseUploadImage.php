<?php

namespace uploadimage\widgets;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;

use uploadimage\components\Item;
use uploadimage\components\Settings;
use uploadimage\assets\UploadImageAsset;

/**
 * Base upload image widget contains common functionality for one and multiple image upload.
 */
class BaseUploadImage extends Widget
{

	/**
	 * @var Model The data model that this widget is associated with.
	 */
	public $model;

	/**
	 * @var string The model attribute that this widget is associated with.
	 */
	public $attribute;



	/**
	 * @var integer Widget item width.
	 */
	public $width = 112;

	/**
	 * @var integer Widget item height.
	 */
	public $height = 84;

	/**
	 * @var array Widget container options.
	 */
	public $options = [];




	/**
	 * @see \uploadimage\Module::$maxFileSize
	 */
	public $maxFileSize;

	/**
	 * @var integer Maximun width of image. Image that larger that its value will be resized. Defaults to 1000.
	 */
	public $maxImageWidth = 1000;

	/**
	 * @var integer Maximun height of image. Image that larger that its value will be resized. Defaults to 750.
	 */
	public $maxImageHeight = 750;

	/**
	 * @var integer Thumb width. Widget width will be used if not set.
	 */
	public $thumbWidth;

	/**
	 * @var integer Thumb height. Widget height will be used if not set.
	 */
	public $thumbHeight;

	/**
	 * @var integer Image quality (for jpeg only). Defaults to 80.
	 */
	public $quality = 80;

	/**
	 * @see \uploadimage\Module::$uploadPath
	 */
	public $uploadPath;

	/**
	 * @var array|Closure Extra data for image item [[function($item)]]. Should return array.
	 * If Closure, [[$item]] is null for default values.
	 */
	public $data;



	/**
	 * @var array Custom buttons. Keys is button identifiers, and values is button properties.
	 * Every button may have some properties:
	 * [[label]] string|Closure Button label, required.
	 * [[title]] string|Closure Button title.
	 * [[active]] boolean|Closure If set to true, button will be rendered as active.
	 * For closure use [[function($item)]], where $item is current item (null by default).
	 */
	public $buttons = [];



	/**
	 * @var string Message that shows to user when file size exceed [[$maxFileSize]] property.
	 * Substring {files} will be replaced with file names in which error occurs.
	 */
	public $messageMaxSize;

	/**
	 * @var string Message that shows to user when file format is not supports.
	 * Substring {files} will be replaced with file names in which error occurs.
	 */
	public $messageFormat;

	/**
	 * @var string Message that will shown to user when some other error is occured.
	 * Substring {files} will be replaced with file names in which error occurs.
	 */
	public $messageOther;



	/**
	 * @var string Name of file input.
	 */
	private $_fileInputName;



	/**
	 * @var string File key.
	 */
	protected $_fileKey;

	/**
	 * @var string Thumb key.
	 */
	protected $_thumbKey;



	/**
	 * @var string Base route for working width images.
	 */
	private $_baseRoute;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		if (!$this->hasModel())
			throw new InvalidConfigException("Properties 'model' and 'attribute' must be specified.");

		$this->checkModule();

		$this->checkThumbSize();
		$this->checkMaxFileSize();
		$this->prepareMessags();

		UploadImageAsset::register($this->getView());
	}

	/**
	 * @inheritdoc
	 */
	public function run()
	{
		if ($this->_fileKey === null)
			throw new \Exception('Property "_fileKey" is not implemented.');
			

		$items = $this->renderItems();
		$loader = $this->renderLoader();

		$options = array_merge($this->options, $this->getWidgetData());
		Html::addCssClass($options, 'uploadimage-widget');
		if (!isset($options['id']))
			$options['id'] = $this->id;

		echo Html::tag('div', $items . $loader, $options);
	}

	/**
	 * Whether this widget is associated with a data model.
	 * @return boolean
	 */
	private function hasModel()
	{
		return $this->model instanceof Model && $this->attribute !== null;
	}

	/**
	 * Check module. Determine [[_baseRoute]], [[uploadPath]] and [[maxFileSize]] if needed.
	 * @return void
	 */
	private function checkModule()
	{
		$hasModule = false;
		$uploadPath = '/upload';
		$maxFileSize = 64;

		foreach (Yii::$app->modules as $id => $module) {
			if ($module instanceof \yii\base\Module) {

				if ($hasModule = ($module instanceof \uploadimage\Module)){
					$uploadPath = $module->uploadPath;
					$maxFileSize = $module->maxFileSize;
				}

			} elseif (is_array($module)) {

				if ($hasModule = ($module['class'] === 'uploadimage\Module')) {
					if (isset($module['uploadPath']))
						$uploadPath = $module['uploadPath'];
					if (isset($module['maxFileSize']))
					$maxFileSize = $module['maxFileSize'];
				}

			} else {

				$hasModule = ($module === 'uploadimage\Module');

			}

			if ($hasModule) {
				$this->_baseRoute = '/' . $id . '/image/';
				break;
			}
		}

		if (!$hasModule)
			throw new InvalidConfigException("UploadImage module is not declared.");

		if ($this->uploadPath === null)
			$this->uploadPath = $uploadPath;

		if ($this->maxFileSize === null)
			$this->maxFileSize = $maxFileSize;

		\uploadimage\Module::addTranslation();
	}

	/**
	 * Checking thumb width and height.
	 * @return void
	 */
	private function checkThumbSize()
	{
		if ($this->thumbWidth === null)
			$this->thumbWidth = $this->width;

		if ($this->thumbHeight === null)
			$this->thumbHeight = $this->height;
	}

	/**
	 * Checking maxFileSize property in according the ini settings.
	 * @return void
	 */
	private function checkMaxFileSize()
	{
		$val = ini_get('upload_max_filesize');
		if (!$val)
			$val = '2M';

		$p = strtolower(substr($val, -1));
		$val = (integer) $val;
		switch ($p) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}

		$val /= 1024 * 1024;

		if ($this->maxFileSize > $val)
			$this->maxFileSize = $val;
	}

	/**
	 * Prepar error messages
	 * @return void
	 */
	protected function prepareMessags()
	{
		if ($this->messageMaxSize === null)
			$this->messageMaxSize = Yii::t('uploadimage', 'The size of files {files} exceeds the allowable ({maxFileSize} MB).', ['maxFileSize' => $this->maxFileSize]);

		if ($this->messageFormat === null)
			$this->messageFormat = Yii::t('uploadimage', 'The format of files {files} is not supported.');

		if ($this->messageOther === null)
			$this->messageOther = Yii::t('uploadimage', 'An error occurred while uploading files {files}.');
	}

	/**
	 * Render image items
	 * @return string
	 */
	protected function renderItems()
	{
		$items = '';

		foreach ($this->getItems() as $item) {
			$items .= Item::widget([
				'fileKey' => $this->_fileKey,
				'thumbKey' => $this->_thumbKey,
				'file' => $this->getItemFile($item),
				'thumb' => $this->getItemThumb($item),
				'width' => $this->width,
				'height' => $this->height,
				'thumbWidth' => $this->thumbWidth,
				'thumbHeight' => $this->thumbHeight,
				'maxImageWidth' => $this->maxImageWidth,
				'maxImageHeight' => $this->maxImageHeight,
				'baseName' => $this->getItemBaseName($item),
				'data' => $this->getItemData($item),
				'buttons' => $this->getItemButtons($item),
				'quality' => $this->quality,
				'uploadPath' => $this->uploadPath,
				'baseRoute' => $this->_baseRoute,
			]);
		}

		return $items;
	}

	/**
	 * Return data that will be transferred to js.
	 * @return array
	 */
	protected function getWidgetData()
	{
		return [
			'data-url' => Url::toRoute([$this->_baseRoute . 'upload', 'token' => $this->getToken()]),
			'data-max-size' => $this->maxFileSize * 1024 * 1024,
			'data-max-count' => 1,
			'data-msg-max-size' => $this->messageMaxSize,
			'data-msg-max-count' => '',
			'data-msg-format' => $this->messageFormat,
			'data-msg-other' => $this->messageOther,
		];
	}

	/**
	 * Save settings for controller.
	 * @return string|boolean Token name. False if failed.
	 */
	private function getToken()
	{
		return Settings::save($this->getSettings());
	}

	/**
	 * Settings, that will be used in controller.
	 * @return array
	 */
	private function getSettings()
	{
		return [
			'name' => $this->getFileInputName(),
			'maxImageWidth' => $this->maxImageWidth,
			'maxImageHeight' => $this->maxImageHeight,
			'thumbWidth' => $this->thumbWidth,
			'thumbHeight' => $this->thumbHeight,
			'quality' => $this->quality,
			'maxFileSize' => $this->maxFileSize * 1024 * 1024,
			'uploadPath' => $this->uploadPath,

			'fileKey' => $this->_fileKey,
			'thumbKey' => $this->_thumbKey,
			'width' => $this->width,
			'height' => $this->height,
			'baseName' => $this->getItemBaseName(null),
			'data' => $this->getItemData(null),
			'buttons' => $this->getItemButtons(null),
		];
	}

	/**
	 * Return name for file input. Generate it if needed.
	 * @return string
	 */
	protected function getFileInputName()
	{
		if ($this->_fileInputName !== null)
			return $this->_fileInputName;

		$name = Html::getInputName($this->model, $this->attribute);

		return $this->_fileInputName = strtolower(str_replace(['[', ']'], ['_', ''], $name));
	}

	/**
	 * Render loader block.
	 * @return string
	 */
	protected function renderLoader()
	{
		throw new \Exception('Function "renderLoader" is not implemented.');
	}

	/**
	 * Return image items to render.
	 * @return array
	 */
	protected function getItems()
	{
		throw new \Exception('Function "getItems" is not implemented.');
	}

	/**
	 * Return item file relative to web root.
	 * @param Model|array $item One of items getted with [[getItems]].
	 * @return string
	 */
	protected function getItemFile($item)
	{
		throw new \Exception('Function "getItemFile" is not implemented.');
	}

	/**
	 * Return item thumb relative to web root.
	 * @param Model|array $item One of items getted with [[getItems]].
	 * @return string
	 */
	protected function getItemThumb($item)
	{
		throw new \Exception('Function "getItemThumb" is not implemented.');
	}

	/**
	 * Return item base name. For new uploaded images [[$item]] is null.
	 * @param Model|array|false|null $item Item for what base name will returned. Item is false for Loader and is null for new item.
	 * @return string
	 */
	protected function getItemBaseName($item)
	{
		throw new \Exception('Function "getItemBaseName" is not implemented.');
	}

	/**
	 * Return item data. For new uploaded images [[$item]] is null.
	 * @param Model|array|null $item Item for what data will returned. Item is null for new item.
	 * @return array
	 */
	protected function getItemData($item)
	{
		$data = $this->data;

		if (is_array($data))
			return $data;

		if ($data instanceof \Closure)
			return $data($item);

		return [];
	}

	/**
	 * Return item buttons. For new uploaded images [[$item]] is null.
	 * @param Model|array|null $item Item for what buttons will returned. Item is null for new item.
	 * @return array
	 */
	private function getItemButtons($item)
	{
		return array_map(function($button) use ($item) {
			return array_map(function($v) use ($item) {
				if ($v instanceof \Closure)
					return $v($item);

				return $v;
			}, $button);
		}, $this->buttons);
	}

}
