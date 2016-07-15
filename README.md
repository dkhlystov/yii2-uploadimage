# Installation

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


# Usage

For using widget at first you need to add `uploadimage\Module` to your application config:

```php
    'modules' => [
        //...
        'uploadimage' => 'uploadimage\Module',
    ],
```

## Single image

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

## Multiple images

Upload multiple images as array to model attribute. Property `fileKey` is required:

```php
<?= \uploadimage\widgets\UploadImages::widget([
        'model' => $model,
        'attribute' => 'images',
        'fileKey' => 'file',
]); ?>
```

If you need to create thumbnail use `thumbKey` property. To limit image count use `maxCount` property.

## Widget size

Default size of every item in widget is **112&times;84**. If you want to render widget with other size use `width` and `height` properties.

```php
<?= $form->field($model, 'image')->widget(\uploadimage\widgets\UploadImage::className(), [
    'width' => 100,
    'height' => 100,
]) ?>
```

## Maximum image size

All images will be optimized while uploading. By default maximun width of uploaded image is **1000** and heigh is **750**. To change this values use `maxWidth` and `maxHeight` properties.

```php
<?= $form->field($model, 'image')->widget(\uploadimage\widgets\UploadImage::className(), [
    'maxWidth' => 640,
    'maxHeight' => 480,
]) ?>
```

## Thumbnail size

When thumbnails uses, its size is similar to widget item size. To change it, use `thumbWidth` and `thumbHeight` properties.

```php
<?= $form->field($model, 'image')->widget(\uploadimage\widgets\UploadImage::className(), [
    'thumbAttribute' => 'thumb',
    'thumbWidth' => 200,
    'thumbHeight' => 150,
]) ?>
```

## Adding extra data

Use `data` property to add extra data to every image item in widget. You can use simple array or `Closure` for this property:

```php
<?= \uploadimage\widgets\UploadImages::widget([
    'model' => $model,
    'attribute' => 'images',
    'fileKey' => 'file',
    'data' => function($item) {
        return [
            'id' => $item['id'],
            'description' => $item['description'],
        ];
    },
]) ?>
```

## Custom buttons support

For working with buttons there are two steps: at first you should to declare buttons on server-side, and then you need to handle events from buttons on client-side.

### server-side

To add custom buttons use `buttons` property. This is array where key is button identifier and value is buttons configuration.
```php
<?= \uploadimage\widgets\UploadImages::widget([
    'model' => $model,
    'attribute' => 'images',
    'id' => 'images',
    'fileKey' => 'file',
    'data' => function($item) {
        return ['main' => $item['main']];
    },
    'buttons' => [
        'main' => [
            'label' => '<i class="fa fa-star"></i>',
            'title' => 'Main image',
            'active' => function($item) {
                return $item['main'];
            },
        ],
    ],
]) ?>
```

Button configuration:

* `label` string that will be rendered as button label, required
* `title` string that added to title attribute of button
* `active` if set to **true**, button will be rendered in active state

If property sets as `Closure`, `$item` parameter is item for which the buttons are rendered. For new uploaded images `$item` is **null**.

Note that you can use **Font Awesome** icons, because its in reqirements and will be installed automatically.

### client-side

In your javascript attach handler for `ui-btnclick` event to widget. In handler there are `id`, `item` and `other` parameters, that represents API for working with widget items.

* `id` button identifier.
* `ui.item` current item management object.
* `ui.other` data management object for all items, except current.

Data management:

* `item.button(id)` return `jQuery` object of button with specified `id`
* `item.data(name)` return value of item data with specified `name`.
* `item.data(name, value)` set item data value with specified `name`.
* `item.data({name1: value1, ...})` set multiple data values.

```js
$(document).on('ui-btnclick', '#images', imagesBtnClick(e, id, item, other) {
    if (ui.id == 'main') {
        item.button('main').addClass('active');
        item.data('main', 1);
        other.button('main').removeClass('active');
        other.data('main', 0);
    }
});
```

## Other properties

By default, all images will be uploaded to `/upload` directory in your web root. If you want to change it, use `uploadPath` property. To set this path globally use `uploadPath` property in application module.

You can change image quality (for JPEG only) by setting `quality` property. Default quality is **80**.

You can specify custom error messages with proprties:

* `messageMaxFileSize` shows when size of the uploading files exeeds `maxFileSize`.
* `messageMaxCount` shows when user try to upload more files then in `maxCount` specified.
* `messageFormat` shows when the format of uploaded files is not supported.
* `messageOther` shows when other error occured.

Every message may contain `{files}` substring, that will be replaced by actual files.