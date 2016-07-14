<?php

namespace uploadimage\widgets;

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
			throw new InvalidConfigException("'fileKey' property must be specified.");

		$this->_fileKey = $this->fileKey;

		if ($this->thumbKey !== null)
			$this->_thumbKey = $this->thumbKey;
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
			'data-msg-max-count' => 'Вы можете загрузить не более ' . $this->maxCount . ' файлов. Файл(ы) {files} не были загружен.',
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
