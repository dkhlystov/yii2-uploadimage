<?php

namespace uploadimage\components;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Image item widget.
 */
class Item extends Widget
{

	/**
	 * @var string Token associated with image. If not null, widget properties will be readed from settings.
	 */
	public $token;



	/**
	 * @var string Base name for form inputs.
	 */
	public $baseName;

	/**
	 * @var string
	 */
	public $fileKey;

	/**
	 * @var string
	 */
	public $thumbKey;

	/**
	 * @var string Link to image file relative to web root. Using for lightbox image preview.
	 */
	public $file;

	/**
	 * @var string Link to thumb relative to web root. [[$file]] will be used if not set.
	 */
	public $thumb;

	/**
	 * @var array Data array which renders to form with hidden inputs using [[$baseName]].
	 */
	public $data = [];



	/**
	 * @var array Custom buttons.
	 * [[label]] string Button label, required.
	 * [[title]] string Button title (hint).
	 * [[active]] boolean If set to true, button will be rendered as active.
	 */
	public $buttons = [];



	/**
	 * @var string If item not uploaded, this is name of original file stored in system.
	 */
	public $originalFile;

	/**
	 * @var string If item not uploaded, this is name of original thumb stored in system.
	 */
	public $originalThumb;



	/**
	 * @var string
	 */
	public $dirtyFile;

	/**
	 * @var string
	 */
	public $dirtyThumb;



	/**
	 * @var integer Image item width.
	 */
	public $width;

	/**
	 * @var integer Image item height.
	 */
	public $height;



	/**
	 * @var integer Thumb width. Widget width will be used if not set.
	 */
	public $thumbWidth;

	/**
	 * @var integer Thumb height. Widget height will be used if not set.
	 */
	public $thumbHeight;



	/**
	 * @var string Path for uploading files.
	 */
	public $uploadPath = '/upload';

	/**
	 * @var integer Image quality (for jpeg only).
	 */
	public $quality = 80;



	/**
	 * @var string Base route for making links. Empty string by default. 
	 */
	public $baseRoute = '';



	/**
	 * @var array Container options.
	 */
	public $options = [];



	/**
	 * @var string Title for remove button.
	 */
	public $titleRemove;

	/**
	 * @var string Title for rotate button.
	 */
	public $titleRotate;

	/**
	 * @var string Title for crop button.
	 */
	public $titleCrop;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		if ($this->token !== null)
			$this->loadToken();

		if ($this->fileKey === null || $this->file === null || $this->baseName === null || $this->width === null || $this->height === null)
			throw new InvalidConfigException("Properties 'baseName', 'fileKey', 'file', 'width' and 'height' must be specified.");

		$this->checkThumbSize();
		$this->checkTitles();

		$this->saveToken();
	}

	/**
	 * @inheritdoc
	 */
	public function run()
	{
		$data = $this->renderData();
		$image = $this->renderImage();
		$buttons = $this->renderButtons();

		$container = Html::tag('div', $image . $buttons, ['class' => 'uploadimage-container']);

		$options = $this->options;
		Html::addCssClass($options, 'uploadimage-item');
		$options['data-token'] = $this->token;

		echo Html::tag('div', $data . $container, $options);
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
	 * Check titles and set default values if needed.
	 * @return void
	 */
	private function checkTitles()
	{
		if ($this->titleRemove === null)
			$this->titleRemove = Yii::t('uploadimage', 'Remove');

		if ($this->titleRotate === null)
			$this->titleRotate = Yii::t('uploadimage', 'Rotate');

		if ($this->titleCrop === null)
			$this->titleCrop = Yii::t('uploadimage', 'Crop');
	}

	/**
	 * Load settings from widget token.
	 * @return void
	 */
	private function loadToken()
	{
		$settings = Settings::load($this->token);

		if ($settings === false)
			return false;

		$this->fileKey = $settings['fileKey'];
		$this->thumbKey = $settings['thumbKey'];
		$this->originalFile = $settings['originalFile'];
		$this->originalThumb = $settings['originalThumb'];
		$this->dirtyFile = $settings['file'];
		$this->dirtyThumb = $settings['thumb'];
		$this->width = $settings['width'];
		$this->height = $settings['height'];
		$this->thumbWidth = $settings['thumbWidth'];
		$this->thumbHeight = $settings['thumbHeight'];
		$this->baseName = $settings['baseName'];
		$this->quality = $settings['quality'];
		$this->uploadPath = $settings['uploadPath'];
		$this->baseRoute = $settings['baseRoute'];
	}

	/**
	 * Save widget settings with token.
	 * @return void
	 */
	private function saveToken()
	{
		$this->token = Settings::save([
			'fileKey' => $this->fileKey,
			'thumbKey' => $this->thumbKey,
			'originalFile' => $this->originalFile,
			'originalThumb' => $this->originalThumb,
			'file' => $this->file,
			'thumb' => $this->thumb,
			'width' => $this->width,
			'height' => $this->height,
			'thumbWidth' => $this->thumbWidth,
			'thumbHeight' => $this->thumbHeight,
			'baseName' => $this->baseName,
			'quality' => $this->quality,
			'uploadPath' => $this->uploadPath,
			'baseRoute' => $this->baseRoute,
		]);
	}

	/**
	 * Render data block.
	 * @return string
	 */
	protected function renderData()
	{
		//file
		$data = Html::hiddenInput($this->baseName . '[' . $this->fileKey . ']', $this->file);

		//thumb
		if ($this->thumbKey !== null)
			$data .= Html::hiddenInput($this->baseName . '[' . $this->thumbKey . ']', $this->thumb);

		//extra data
		foreach ($this->data as $key => $value) {
			$data .= Html::hiddenInput($this->baseName . '[' . $key . ']', $value);
		}

		return Html::tag('div', $data, ['class' => 'uploadimage-data']);
	}

	/**
	 * Render image item.
	 * @return string
	 */
	protected function renderImage()
	{
		$thumb = $this->thumb;

		if ($thumb === null)
			$thumb = $this->file;

		list($w, $h) = getimagesize(Yii::getAlias('@webroot') . $thumb);
		list($width, $height) = ImageFile::getBounds($w, $h, $this->width, $this->height);
		$left = ($this->width - $width) / 2;
		$top = ($this->height - $height) / 2;
		$style = "width: {$width}px; height: {$height}px; left: {$left}px; top: {$top}px";

		$image = Html::img($thumb, ['style' => $style]);

		return Html::a($image, $this->file, [
			'class' => 'uploadimage-image',
			'style' => "width: {$this->width}px; height: {$this->height}px;",
		]);
	}

	/**
	 * Render item buttons.
	 * @return string
	 */
	protected function renderButtons()
	{
		//remove
		$buttons = Html::a('&times;', [
			$this->baseRoute . 'remove',
			'token' => $this->token,
		], [
			'class' => 'uploadimage-btn remove',
			'title' => $this->titleRemove,
		]);

		//actions
		$group = [];
		//rotate
		$group[] = Html::a('<i class="fa fa-repeat"></i>', [
			$this->baseRoute . 'rotate',
			'token' => $this->token,
		], [
			'class' => 'uploadimage-btn rotate',
			'title' => $this->titleRotate,
		]);
		//crop
		if ($this->thumbKey !== null) {
			$group[] = Html::a('<i class="fa fa-crop"></i>', [
				$this->baseRoute . 'crop',
				'token' => $this->token,
			], [
				'class' => 'uploadimage-btn crop',
				'title' => $this->titleCrop,
			]);
		}
		$buttons .= Html::tag('div', implode('', $group), ['class' => 'uploadimage-btngroup actions']);

		//custom
		$group = [];
		foreach ($this->buttons as $id => $button) {
			$button = array_merge([
				'title' => '',
				'active' => false,
			], $button);

			$options = ['class' => 'uploadimage-btn', 'data-id' => $id];
			if (!empty($button['title']))
				$options['title'] = $button['title'];
			if ($button['active'])
				Html::addCssClass($options, 'active');

			$group[] = Html::a($button['label'], '#', $options);
		}
		if (!empty($group))
			$buttons .= Html::tag('div', implode('', $group), ['class' => 'uploadimage-btngroup custom']);

		return $buttons;
	}

}
