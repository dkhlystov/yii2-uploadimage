<?php
namespace dkhlystov\uploadimage\assets;

use yii\web\AssetBundle;

class UploadImageAsset extends AssetBundle
{

	public $css = [
		'uploadimage.css',
	];

	public $js = [
		'jquery.form.min.js',
	];

	public $depends = [
		'yii\web\JqueryAsset',
	];

	public function init()
	{
		parent::init();

		$this->sourcePath = __DIR__ . '/uploadimage';

		$this->js[] = 'uploadimage' . (YII_DEBUG ? '' : '.min') . '.js';
	}

}
