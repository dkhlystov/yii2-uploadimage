<?php

namespace dkhlystov\uploadimage\widgets;

use yii\helpers\Html;

use dkhlystov\uploadimage\components\Loader;

/**
 * Image upload widget for upload single image.
 */
class UploadImage extends BaseUploadImage
{

	/**
	 * @var string Image thumb attribute name.
	 */
	public $thumbAttribute;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->_fileKey = $this->attribute;

		if ($this->thumbAttribute !== null)
			$this->_thumbKey = $this->thumbAttribute;
	}

	/**
	 * @inheritdoc
	 */
	protected function getItems()
	{
		$attr = $this->attribute;
		if (empty($this->model->$attr))
			return [];
		
		return [$this->model];
	}

	/**
	 * @inheritdoc
	 */
	protected function getItemFile($item)
	{
		$attr = $this->attribute;

		return $item->$attr;
	}

	/**
	 * @inheritdoc
	 */
	protected function getItemThumb($item)
	{
		$thumbAttr = $this->thumbAttribute;

		return $thumbAttr === null ? null : $item->$thumbAttr;
	}

	/**
	 * @inheritdoc
	 */
	protected function getItemBaseName($item)
	{
		return $this->model->formName();
	}

	/**
	 * @inheritdoc
	 */
	protected function renderLoader()
	{
		$attr = $this->attribute;
		$hidden = !empty($this->model->$attr);

		return Loader::widget([
			'baseName' => $this->getItemBaseName(false),
			'fileKey' => $this->_fileKey,
			'thumbKey' => $this->_thumbKey,
			'data' => $this->getItemData(null),

			'width' => $this->width,
			'height' => $this->height,
			'hidden' => $hidden,
			'disabledInput' => $hidden,

			'fileInputName' => $this->getFileInputName(),
		]);
	}

}
