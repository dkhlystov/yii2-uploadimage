<?php

namespace uploadimage\widgets;

use yii\base\Widget;

class UploadWidget extends Widget
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
	 * @var string The input name. This must be set if [[model]] and [[attribute]] are not set.
	 */
	public $name;

	/**
	 * @var string The input value.
	 */
	public $value;

	public $fileAttribute;

	public $thumbAttribute;

	/**
	 * @var integer Max image count. Unlimited if set to zero.
	 */
	public $max = 0;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		if ($this->hasModel()) {
			$this->checkAttributes();
		} elseif ($this->name === null) {
			throw new InvalidConfigException("Either 'name', or 'model' and 'attribute' properties must be specified.");
		}

		$this->checkMaxFileSize();

		UploadImageAsset::register($this->view);
	}

	/**
	 * @inheritdoc
	 */
	public function run()
	{

	}

	/**
	 * Whether this widget is associated with a data model.
	 * @return boolean
	 */
	protected function hasModel()
	{
		return $this->model instanceof Model && $this->attribute !== null;
	}

	/**
	 * No need addition arrays in form names if there are only one image and [[fileAttribute]] is not set.
	 * @return void
	 */
	protected function checkAttributes()
	{
		if ($this->max == 1 && ($this->fileAttribute === null)) {
			$this->fileAttribute = $this->attribute;
			$this->attribute = null;
		}
	}

	/**
	 * Checking maxFileSize property in according the ini settings.
	 * @return void
	 */
	protected function checkMaxFileSize()
	{
		$upload_max_filesize = ini_get('upload_max_filesize');
		var_dump($upload_max_filesize); die();
	}

}
