<?php
namespace uploadimage\assets;

use yii\web\AssetBundle;

class UploadImageAsset extends AssetBundle
{

	public $sourcePath = '@uploadimage/assets/uploadimage';

	public $css = [
		'uploadimage.css',
	];

	public $js = [
		'jquery.form.min.js',
	];

	public $depends = [
		'yii\web\JqueryAsset',
		'uploadimage\assets\FontAwesomeAsset'
	];

	public function init()
	{
		parent::init();

		$this->js[] = 'uploadimage' . (YII_DEBUG ? '' : '.min') . '.js';
	}

}
