$(function() {

	var $drag;

	//======
	//events

	$(document).on('click', '.uploadimage-loader', loaderClick);
	$(document).on('change', '.uploadimage-widget :file', fileChange);
	$(document).on('click', '.uploadimage-image', imageClick);
	$(document).on('mousedown', '.uploadimage-item.crop .uploadimage-image', imageCropMouseDown);
	$(document).on('touchstart', '.uploadimage-item.crop .uploadimage-image', imageCropMouseDown);
	$(document).on('click', '.uploadimage-btn.remove', removeClick);
	$(document).on('click', '.uploadimage-btn.rotate', rotateClick);
	$(document).on('click', '.uploadimage-btn.crop', cropClick);
	$(document).on('click', '.uploadimage-btngroup.custom .uploadimage-btn', customBtnClick);
	$(window).resize(windowResize);

	//==============
	//event handlers

	function loaderClick(e)
	{
		e.preventDefault();

		var $this = $(this);
		if (!$this.hasClass('loading'))
			$this.closest('.uploadimage-item').find(':file').click();
	};

	function fileChange(e)
	{
		var $uploadimage = $(this).closest('.uploadimage-widget'),
			can = true;

		can &= 'files' in this;
		can &= window.FormData !== undefined;
		can &= 'onload' in new XMLHttpRequest();

		if (can) {
			uploadImageFiles($uploadimage, this.files);
		} else {
			uploadImageLegacy($uploadimage);
		};

		this.value = '';
	};

	function imageClick(e)
	{
		e.preventDefault();

		var $item = $(this).closest('.uploadimage-item');

		if (!$item.hasClass('crop') && !$item.closest('.uploadimage-widget').hasClass('nopreview'))
			itemPreview($item);
	};

	function imageCropMouseDown(e)
	{
		e.preventDefault();

		var px, py;

		//touch event
		if (e.type === 'touchstart') {
			var touches = e.originalEvent.changedTouches;
			if (touches.length) {
				px = touches[0].pageX;
				py = touches[0].pageY;
			}
		} else {
			px = e.pageX;
			py = e.pageY;
		}

		if (px === undefined)
			return;

		$(window).on('mousemove', windowCropMouseMove);
		$(window).on('mouseup', windowCropMouseUp);

		$(window).on('touchmove', windowCropMouseMove);
		$(window).on('touchend', windowCropMouseUp);
		$(window).on('touchcancel', windowCropMouseUp);

		$drag = $(this).find('.uploadimage-crop img');

		var $crop = $drag.parent(), p = $drag.position();

		$drag.data({
			'x': p.left,
			'y': p.top,
			'px': px,
			'py': py,
			'mx': $crop.width() - $drag.width(),
			'my': $crop.height() - $drag.height(),
		});
	};

	function removeClick(e)
	{
		e.preventDefault();
		itemRemove($(this).closest('.uploadimage-item'));
	};

	function rotateClick(e)
	{
		e.preventDefault();
		itemRotate($(this).closest('.uploadimage-item'));
	};

	function cropClick(e)
	{
		e.preventDefault();

		var $this = $(this),
			$item = $this.closest('.uploadimage-item');

		if ($this.hasClass('active')) {
			itemCropDisable($item);
		} else {
			itemCropEnable($item);
		}
	};

	function windowResize(e)
	{
		previewResize();
	};

	function windowPreviewKeyDown(e)
	{
		if (e.which == 27)
			itemPreviewClose();
	};

	function windowCropMouseMove(e)
	{
		var px, py;

		//touch event
		if (e.type === 'touchmove') {
			var touches = e.originalEvent.changedTouches;
			if (touches.length) {
				px = touches[0].pageX;
				py = touches[0].pageY;
			}
		} else {
			px = e.pageX;
			py = e.pageY;
		}

		if (px === undefined)
			return;

		var x = $drag.data('x') + px - $drag.data('px'),
			y = $drag.data('y') + py - $drag.data('py'),
			mx = $drag.data('mx'),
			my = $drag.data('my');

		if (x < mx) x = mx;
		if (x > 0) x = 0;
		if (y < my) y = my;
		if (y > 0) y = 0;

		$drag.css({
			'left': x,
			'top': y
		});
	};

	function windowCropMouseUp(e)
	{
		$(window).off('mousemove', windowCropMouseMove);
		$(window).off('mouseup', windowCropMouseUp);

		$(window).off('touchmove', windowCropMouseMove);
		$(window).off('touchend', windowCropMouseUp);
		$(window).off('touchcancel', windowCropMouseUp);

		itemCrop($drag.closest('.uploadimage-item'));
	};

	function customBtnClick(e)
	{
		e.preventDefault();

		var $this = $(this),
			$item = $this.closest('.uploadimage-item');

		$this.closest('.uploadimage-widget').trigger('ui-btnclick', [
			$this.data('id'),
			customItem($item),
			customOther($item)
		]);
	};

	//=============
	//functionality

	function checkLoaderData($loaderItem)
	{
		var disabled = $loaderItem.closest('.uploadimage-widget').find('.uploadimage-item').length > 1;
		$loaderItem.find('input').not(':file').prop('disabled', disabled);
	};

	function uploadImageFiles($uploadimage, files)
	{
		var $loaderItem = $uploadimage.find('.uploadimage-loader').closest('.uploadimage-item'),
			w = $loaderItem.outerWidth(),
			h = $loaderItem.outerHeight(),
			name = $loaderItem.find(':file')[0].name,
			url = $uploadimage.data('url'),
			maxSize = $uploadimage.data('maxSize'),
			maxCount = $uploadimage.data('maxCount'),
			errorMaxSize = [],
			errorMaxCount = [];


		//check max count
		if (maxCount === undefined) {
			maxCount = 1;
		} else if (maxCount == 0) {
			maxCount = null;
		} else {
			maxCount -= $uploadimage.find('.uploadimage-item').length - 1;
		};

		for (var i = 0, count = 0; i < files.length; i++) {
			file = files[i];

			//file size validation
			if (file.size > maxSize) {
				errorMaxSize.push(file.name);
				continue;
			};

			//max count validation
			if (maxCount && (count >= maxCount)) {
				errorMaxCount.push(file.name);
				continue;
			};

			//uploading
			uploadImageFile($loaderItem, w, h, name, file, url);

			count++;
		};

		//loader visibility
		if (maxCount !== null && count >= maxCount)
			$loaderItem.addClass('hidden');

		//loader data
		checkLoaderData($loaderItem);

		//make and show error message
		showError($uploadimage, {'errorMaxSize': errorMaxSize, 'errorMaxCount': errorMaxCount});
	};

	function uploadImageFile($loaderItem, w, h, name, file, url)
	{
		var $imageItem = $('<div />').addClass('uploadimage-item loading').css({'width': w, 'height': h}),
			formData = new FormData(),
			xhr = new XMLHttpRequest();

		$imageItem.insertBefore($loaderItem);

		if (('upload' in xhr) && ('onprogress' in xhr.upload))
			uploadImageProgress($imageItem, xhr, file);

		formData.append(name, file);

		xhr.onreadystatechange = function() {
			if (xhr.readyState == 4) {
				if (xhr.status == 200) {

					var data = JSON.parse(xhr.responseText);
					if (data['items'].length) {
						var $item = $(data['items'][0]);
						$imageItem.replaceWith($item);
						itemSetIndex($item);
					} else {
						showError($imageItem.closest('.uploadimage-widget'), data);
						$imageItem.remove();
						$loaderItem.removeClass('hidden');
						checkLoaderData($loaderItem);
					};

				} else {
					alert(xhr.statusText);
					$imageItem.remove();
					$loaderItem.removeClass('hidden');
					checkLoaderData($loaderItem);
				};
			};
		};

		xhr.open('POST', url, true);
		xhr.send(formData);
	};

	function uploadImageLegacy($uploadimage)
	{
		var $loader = $uploadimage.find('.uploadimage-loader');

		$loader.addClass('loading');
		$uploadimage.closest('form').ajaxSubmit({
			'url': $uploadimage.data('url'),
			'dataType': 'json',
			'error': function(xhr) {
				alert(xhr.statusText);
				$loader.removeClass('loading');
			},
			'success': function(data) {
				var $loaderItem = $loader.closest('.uploadimage-item'), $item,
					max = $uploadimage.data('maxCount');

				if (max) {
					max -= $uploadimage.find('.uploadimage-item').length - 1;
					data['items'].splice(max);
					data['errorMaxCount'] = data['names'].slice(max);
				}

				$.each(data['items'], function(i, item) {
					$item = $(item).insertBefore($loaderItem);
					itemSetIndex($item);
				});

				if (data['items'].length == max)
					$loaderItem.addClass('hidden');

				checkLoaderData($loaderItem);

				showError($uploadimage, data);

				$loader.removeClass('loading');

			}
		});
	};

	function uploadImageProgress($item, xhr, file)
	{
		$item.append('<div class="uploadimage-progress"><div /></div>');

		var $pos = $item.find('.uploadimage-progress > div');

		xhr.upload.onprogress = function(e) {
			$pos.css('width', (e.loaded / e.total * 100) + '%');
		};
	};

	function itemSetIndex($item)
	{
		var $uploadimage = $item.closest('.uploadimage-widget'),
			$inputs = $item.find('input');

		$uploadimage.trigger('ui-imgload');

		if (!/\[\]\[[\da-z_]+\]$/i.test($inputs.attr('name')))
			return;

		var idx = -1, m, i;
		$uploadimage.find('input').not(':file').each(function() {
			if (m = this.name.match(/\[(\d+)\]\[[\da-z_]+\]$/i)) {
				if ((i = parseInt(m[1])) > idx) idx = i;
			}
		});

		idx++;
		$inputs.each(function() {
			this.name = this.name.replace(/\[\](\[[\da-z_]+\])$/i, '[' + idx + ']$1');
		});
	};

	function showError($uploadimage, errors)
	{
		//make and show error message
		var msgMaxSize = $uploadimage.data('msgMaxSize'),
			msgMaxCount = $uploadimage.data('msgMaxCount'),
			msgFormat = $uploadimage.data('msgFormat'),
			msgOther = $uploadimage.data('msgOther'),
			msg = '';

		if (('errorMaxSize' in errors) && errors['errorMaxSize'].length) {
			var s = errors['errorMaxSize'].map(function(s) { return '"' + s + '"'}).join(', ');
			msg += msgMaxSize.replace('{files}', s) + '\n';
		};
		if (('errorMaxCount' in errors) && errors['errorMaxCount'].length) {
			var s = errors['errorMaxCount'].map(function(s) { return '"' + s + '"'}).join(', ');
			msg += msgMaxCount.replace('{files}', s) + '\n';
		};
		if (('errorFormat' in errors) && errors['errorFormat'].length) {
			var s = errors['errorFormat'].map(function(s) { return '"' + s + '"'}).join(', ');
			msg += msgFormat.replace('{files}', s) + '\n';
		};
		if (('errorOther' in errors) && errors['errorOther'].length) {
			var s = errors['errorOther'].map(function(s) { return '"' + s + '"'}).join(', ');
			msg += msgOther.replace('{files}', s) + '\n';
		};

		if (msg !== '')
			alert(msg);
	};

	function itemPreview($item)
	{
		var $overlay = $('<div class="uploadimage-overlay" />').click(itemPreviewClose),
			$preview = $('<div class="uploadimage-preview"><div class="buffer"><img></div><img class="image"><div class="loading" /><a href="#" class="close"><i class="glyphicon glyphicon-remove"></i></a></div>'),
			wWidth = $(window).width(),
			wHeight = $(window).height();

		$('body').append($overlay, $preview);

		$overlay.fadeIn(200);

		$preview.css({
			'left': (wWidth - $preview.outerWidth()) / 2,
			'top': (wHeight - $preview.outerHeight()) / 2
		});

		$preview.find('.close, .loading').click(function(e) {
			e.preventDefault();
			itemPreviewClose();
		});

		$preview.find('.buffer img').on('load', function() {
			$preview.find('.loading').css('opacity', 0);
			$preview.find('.image').attr('src', this.src);

			previewResize();
		}).attr('src', $item.find('.uploadimage-image').attr('href'));

		$(window).on('keydown', windowPreviewKeyDown);
	};

	function previewResize()
	{
		var $preview = $('.uploadimage-preview'),
			$buffer = $preview.find('.buffer img'),
			$image = $preview.find('.image'),
			pw = $preview.width(),
			ph = $preview.height(),
			vw = $(window).width() - $preview.outerWidth() + pw,
			vh = $(window).height() - $preview.outerHeight() + ph,
			iw = $buffer.width(),
			ih = $buffer.height(),
			a = iw / ih,
			mw = vw - 40,
			mh = vh - 40,
			w = iw,
			h = ih;

		if (iw > mw || ih > mh) {
			if (a > mw / mh) {
				w = mw;
				h = w / a;
			} else {
				h = mh;
				w = h * a;
			};
		};

		$image.css({
			'width': w,
			'height': h
		});

		if ($image.is(':hidden')) {
			$preview.animate({
				'width': w,
				'height': h,
				'left': (vw - w) / 2,
				'top': (vh - h) / 2
			}, 200, function() {
				$image.fadeIn(200);
			});
		} else {
			$preview.css({
				'width': w,
				'height': h,
				'left': (vw - w) / 2,
				'top': (vh - h) / 2
			});
		};
	};

	function itemPreviewClose()
	{
		$(window).off('keydown', windowPreviewKeyDown);

		$('.uploadimage-overlay, .uploadimage-preview').fadeOut(200, function() {
			$(this).remove();
		});
	};

	function itemApply($item, $data, initCrop)
	{
		var $image = $item.find('.uploadimage-image'),
			$thumb = $item.find('.uploadimage-image > img'),
			o, n;

		//image
		o = $image.attr('href');
		n = $data.find('.uploadimage-image').attr('href');
		$item.find(':input[value="' + o + '"]').val(n);
		$image.attr('href', n);
		
		//thumb
		o = $thumb.attr('src');
		n = $data.find('.uploadimage-image > img').attr('src');
		$item.find(':input[value="' + o + '"]').val(n);
		$thumb.replaceWith($data.find('.uploadimage-image > img'));

		//crop
		if (initCrop !== false && $item.hasClass('crop'))
			itemCropSrc($item, $image.attr('href'));

		//token
		var token = $data.data('token');
		$item.find('a.uploadimage-btn').each(function() {
			this.href = this.href.replace(/(token=)[\da-f]+/i, '$1' + token);
		});

		//unblocking
		$item.removeData('blocked');
	};

	function itemRemove($item)
	{
		if ($item.data('blocked'))
			return;

		var $loaderItem = $item.closest('.uploadimage-widget').find('.uploadimage-loader').closest('.uploadimage-item');

		$.get($item.find('.uploadimage-btn.remove').attr('href'));

		$item.remove();

		$loaderItem.removeClass('hidden');
		checkLoaderData($loaderItem);
	};

	function itemRotate($item)
	{
		if ($item.data('blocked'))
			return;

		$item.data('blocked', true);
		$.get($item.find('.uploadimage-btn.rotate').attr('href'), function(data) {
			itemApply($item, $(data));
		});
	};

	function itemCropEnable($item)
	{
		if ($item.data('blocked'))
			return;

		$item.addClass('crop').find('.uploadimage-btn.crop').addClass('active');

		var $image = $item.find('.uploadimage-image'),
			$img = $image.find('img')
			$crop = $('<span class="uploadimage-crop"><img></span>');



		$crop.attr('style', $img.attr('style'));
		$image.append($crop);

		itemCropSrc($item, $image.attr('href'));
	};

	function itemCropSrc($item, src)
	{
		var $buffer = $('<div><img></div>');
		$buffer.css({
			'height': 1,
			'left': 0,
			'overflow': 'hidden',
			'position': 'absolute',
			'top': 0,
			'width': 1
		}).appendTo('body');

		$buffer.find('img').on('load', function() {
			var $this = $(this),
				$crop = $item.find('.uploadimage-crop'),
				$cropImg = $crop.find('img'),
				iw = $this.width(),
				ih = $this.height(),
				a = iw / ih,
				cw = $crop.width(),
				ch = $crop.height(),
				w, h, x, y;

			if (a > cw /ch) {
				h = ch;
				w = h * a;
				$cropImg.data('scale', ih / h);
			} else {
				w = cw;
				h = w / a;
				$cropImg.data('scale', iw / w);
			}

			$cropImg.css({
				'opacity': 1,
				'width': w,
				'height': h,
				'left': x = Math.round((cw - w) / 2),
				'top': y = Math.round((ch - h) / 2)
			}).attr('src', $this.attr('src'));

			$buffer.remove();

			if (!$cropImg.data('croped')) {
				$cropImg.data('croped', true);
				itemCrop($item, x, y);
			}
		}).attr('src', src);
	};

	function itemCropDisable($item)
	{
		$item.find('.uploadimage-crop').remove();
		$item.find('.uploadimage-image img').show();

		$item.removeClass('crop').find('.uploadimage-btn.crop').removeClass('active');
	};

	function itemCrop($item, x, y)
	{
		var $img = $item.find('.uploadimage-crop img'),
			scale = $img.data('scale');

		if (x === undefined || y === undefined) {
			var p = $img.position();
			if (x === undefined) x = p.left;
			if (y === undefined) y = p.top;
		}

		$item.data('blocked', true);
		$.get($item.find('.uploadimage-btn.crop').attr('href'), {
			'x': -Math.round(x * scale),
			'y': -Math.round(y * scale)
		}, function(data) {
			itemApply($item, $(data), false);
		});
	};

	function customItem($item)
	{
		return customManage($item);
	};

	function customOther($item)
	{
		return customManage($item.closest('.uploadimage-widget').find('.uploadimage-item[data-token]').not($item));
	};

	function customManage($items)
	{
		return {
			'button': function(id) {
				return $items.find('.uploadimage-btn[data-id="' + id + '"]');
			},
			'data': function(name, value) {
				var data = {};

				if (typeof(name) == 'string') {
					if (value === undefined)
						return customItemData($items, name).val();

					data[name] = value;
				} else {
					data = name;
				};

				if (!$.isPlainObject(data))
					return;

				$.each(data, function(name, value) {
					customItemData($items, name).val(value);
				});
			}
		};
	};

	function customItemData($items, name)
	{
		name = name.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");

		var regexp = new RegExp('\\[' + name + '\\]$'),
			$input = $();

		$items.find('input').each(function() {
			if (regexp.test(this.name)) {
				$input = $(this);
				return false;
			}
		});

		return $input;
	};

});
