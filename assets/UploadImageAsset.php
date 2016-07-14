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
		'uploadimage.js',
		'jquery.form.min.js',
	];
	public $depends = [
		'yii\web\JqueryAsset',
		'uploadimage\assets\FontAwesomeAsset'
	];
}
