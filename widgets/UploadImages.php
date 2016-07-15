<?php

namespace uploadimage\widgets;

use Yii;
use yii\base\InvalidConfigException;

use uploadimage\components\Loader;

/**
 * Image upload widget for upload multiple images.
 */
class UploadImages extends BaseUploadImage
{

	/**
	 * @var string Key for file value of the item.
	 */
	public $fileKey;

	/**
	 * @var string Key for thumb value of the item.
	 */
	public $thumbKey;

	/**
	 * @var integer Max image count. Unlimited if set to zero.
	 */
	public $maxCount = 0;

	/**
	 * @var string Message that shows to user when count of uploaded files exceed [[$maxCount]] property.
	 * Substring {files} will be replaced with file names in which error occurs.
	 */
	public $messageMaxCount;

	/**
	 * @var array Image items returned by [[getItems()]].
	 */
	private $_items;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		if ($this->fileKey === null)
			throw new InvalidConfigException("Property 'fileKey' must be specified.");

		$this->_fileKey = $this->fileKey;

		if ($this->thumbKey !== null)
			$this->_thumbKey = $this->thumbKey;
	}

	/**
	 * @inheritdoc
	 */
	protected function prepareMessags()
	{
		parent::prepareMessags();

		if ($this->messageMaxCount === null)
			$this->messageMaxCount = Yii::t('uploadimage', 'You can upload up to {maxCount} files. The files {files} were not uploaded.', ['maxCount' => $this->maxCount]);
	}

	/**
	 * @inheritdoc
	 */
	protected function getItems()
	{
		$model = $this->model;
		$attr = $this->attribute;

		return $this->_items = is_array($model->$attr) ? $model->$attr : [];
	}

	/**
	 * @inheritdoc
	 */
	protected function renderLoader()
	{
		return Loader::widget([
			'baseName' => $this->getItemBaseName(false),
			'fileKey' => $this->attribute,
			'data' => $this->getItemData(null),

			'width' => $this->width,
			'height' => $this->height,
			'multiple' => true,
			'hidden' => $this->maxCount > 0 && sizeof($this->_items) >= $this->maxCount,
			'disabledInput' => sizeof($this->_items),

			'fileInputName' => $this->getFileInputName(),
		]);
	}

	/**
	 * @inheritdoc
	 */
	protected function getItemBaseName($item)
	{
		$name = $this->model->formName();

		//loader
		if ($item === false)
			return $name;

		$idx = '';
		if ($item !== null)
			$idx = array_search($item, $this->_items);

		return $name .= '[' . $this->attribute . '][' . $idx . ']';
	}

	/**
	 * @inheritdoc
	 */
	protected function getWidgetData()
	{
		return array_merge(parent::getWidgetData(), [
			'data-max-count' => $this->maxCount,
			'data-msg-max-count' => $this->messageMaxCount,
		]);
	}

	/**
	 * @inheritdoc
	 */
	protected function getItemFile($item)
	{
		$fileKey = $this->fileKey;

		return $item[$fileKey];
	}

	/**
	 * @inheritdoc
	 */
	protected function getItemThumb($item)
	{
		$thumbKey = $this->thumbKey;

		return $thumbKey === null ? null : $item[$thumbKey];
	}

}
