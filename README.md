# yii2-uploadimage

UploadImage widget for Yii PHP Framework Version 2

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run
```
composer require dkhlystov/yii2-uploadimage
```

or add
```
"dkhlystov/yii2-uploadimage": "*"
```

to the require section of your `composer.json` file.


## Usage

For using widget at first you need to add `uploadimage\Module` to your application config:
```php
    'modules' => [
        //...
        'uploadimage' => 'uploadimage\Module',
    ],
```


### Single image

Upload single image to model attribute:
```php
<?= \uploadimage\widgets\UploadImage::widget([
		'model' => $model,
		'attribute' => 'image',
]); ?>
```

With `ActiveForm`:
```php
<?= $form->field($model, 'image')->widget(\uploadimage\widgets\UploadImage::className()) ?>
```

If thumbnail needed:
```php
<?= \uploadimage\widgets\UploadImage::widget([
		'model' => $model,
		'attribute' => 'image',
		'thumbAttribute' => 'thumb',
]); ?>
```


### Multiple images

Upload multiple images as array to model attribute. Property `fileKey` is required:
```php
<?= \uploadimage\widgets\UploadImages::widget([
		'model' => $model,
		'attribute' => 'images',
		'fileKey' => 'file',
]); ?>
```

If you need to create thumbnail use `thumbKey` property. To limit image count use `maxCount` property.


### Widget size

Default size of every item in widget is **112&times;84**. If you want to render widget with other size use `width` and `height` properties.
```php
<?= $form->field($model, 'image')->widget(\uploadimage\widgets\UploadImage::className(), [
    'width' => 100,
    'height' => 100,
]) ?>
```

### Maximum image size

All images will be optimized while uploading. By default maximun width of uploaded image is **1000** and heigh is **750**. To change this values use `maxWidth` and `maxHeight` properties.
```php
<?= $form->field($model, 'image')->widget(\uploadimage\widgets\UploadImage::className(), [
    'maxWidth' => 640,
    'maxHeight' => 480,
]) ?>
```

### Thumbnail size

When thumbnails uses, its size is similar to widget item size. To change it, use `thumbWidth` and `thumbHeight` properties.
```php
<?= $form->field($model, 'image')->widget(\uploadimage\widgets\UploadImage::className(), [
    'thumbAttribute' => 'thumb',
    'thumbWidth' => 200,
    'thumbHeight' => 150,
]) ?>
```

### Adding extra data

Use `itemData` property to add extra data to every image item in widget. You can use simple array or `Closure` for this property:
```php
<?= \uploadimage\widgets\UploadImages::widget([
    'model' => $model,
    'attribute' => 'images',
    'fileKey' => 'file',
    'itemData' => function($item) {
        return [
            'id' => $item['id'],
            'description' => $item['description'],
        ];
    },
]) ?>
```

### Other properties

By default, all images will be uploaded to `/upload` directory in your web root. If you want to change it, use `uploadPath` property.

You can change image quality (for JPEG only) by setting `quality` property. Default quality is **80**.

