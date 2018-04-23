<?php

namespace dkhlystov\uploadimage\components;

use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Widget that renders loader for UploadImage widget
 */
class Loader extends Widget
{

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
     * @var array Default data array which renders to form with hidden inputs using [[$baseName]].
     */
    public $data = [];



    /**
     * @var integer Loader width.
     */
    public $width;

    /**
     * @var integer Loader height.
     */
    public $height;

    /**
     * @var array Loader container options.
     */
    public $options = [];

    /**
     * @var boolean Set to true if need to multiple files upload support.
     */
    public $multiple = false;

    /**
     * @var boolean Set to true if need to hide loader.
     */
    public $hidden = false;

    /**
     * @var boolean Set to true if need to disable hidden input.
     */
    public $disabledInput = false;

    /**
     * @var string Name of file input.
     */
    public $fileInputName;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->baseName === null || $this->fileKey === null || $this->width === null || $this->height === null || $this->fileInputName === null)
            throw new InvalidConfigException("Properties 'baseName', 'fileKey', 'width', 'height' and 'fileInputName' must be specified.");
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $data = $this->renderData();
        $loader = $this->renderLoader();

        $options = $this->options;
        Html::addCssClass($options, 'uploadimage-item');
        if ($this->hidden)
            Html::addCssClass($options, 'hidden');

        echo Html::tag('div', $data . $loader, $options);
    }

    /**
     * Render data block
     * @return string
     */
    protected function renderData()
    {
        $data = '';

        //file
        $options = [
            'accept' => 'image/jpeg,image/png,image/gif',
            'id' => 'upload-image',
        ];
        if ($this->multiple)
            $options['multiple'] = 'multiple';
        $data .= Html::fileInput($this->fileInputName, '', $options);

        //attributes
        $a = [];
        $a[$this->fileKey] = '';
        if ($this->thumbKey !== null)
            $a[$this->thumbKey] = '';
        $a = array_merge($a, $this->data);
        foreach ($a as $key => $value) {
            $data .= Html::hiddenInput($this->baseName . '[' . $key . ']', $value, ['disabled' => $this->disabledInput]);
        }

        return Html::tag('div', $data, ['class' => 'uploadimage-data']);
    }

    /**
     * Render loader block
     * @return string
     */
    protected function renderLoader()
    {
        $options = ['class' => 'glyphicon glyphicon-plus'];
        $fontSize = min($this->width, $this->height) / 2;
        Html::addCssStyle($options, "font-size: {$fontSize}px;");

        $label = Html::tag('i', '', $options);

        return Html::a($label, '#', [
            'class' => 'uploadimage-loader',
            'style' => "width: {$this->width}px; height: {$this->height}px; line-height: {$this->height}px;"
        ]);
    }

}
