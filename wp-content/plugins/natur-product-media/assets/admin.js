/* Natur – Video & 360°: metabox produs */
jQuery(function ($) {
	var $box = $('.natur-pm');
	if (!$box.length) return;

	var $videos = $box.find('.natur-pm-videos');
	var $frames = $box.find('.natur-pm-frames');
	var $input = $box.find('.natur-pm-frames-input');
	var $count = $box.find('.natur-pm-count');

	/* ---- video ---- */
	$box.on('click', '.natur-pm-add-video', function () {
		var $row = $videos.find('.natur-pm-video-row').first().clone();
		$row.find('input').val('');
		$videos.append($row);
	});

	$box.on('click', '.natur-pm-remove-video', function () {
		var $rows = $videos.find('.natur-pm-video-row');
		var $row = $(this).closest('.natur-pm-video-row');
		if ($rows.length > 1) $row.remove();
		else $row.find('input').val('');
	});

	$box.on('click', '.natur-pm-pick-video', function () {
		var $field = $(this).siblings('input');
		var frame = wp.media({
			title: 'Alege fișier video',
			library: { type: 'video' },
			button: { text: 'Folosește acest video' },
			multiple: false
		});
		frame.on('select', function () {
			$field.val(frame.state().get('selection').first().get('url'));
		});
		frame.open();
	});

	/* ---- 360 ---- */
	function sync() {
		var ids = $frames.children().map(function () { return $(this).data('id'); }).get();
		$input.val(ids.join(','));
		$count.text(ids.length + (ids.length === 1 ? ' cadru' : ' cadre'));
	}

	$frames.sortable({ update: sync, tolerance: 'pointer' });

	$box.on('click', '.natur-pm-pick-frames', function () {
		var frame = wp.media({
			title: 'Selectează cadrele 360°',
			library: { type: 'image' },
			button: { text: 'Adaugă cadrele' },
			multiple: 'add'
		});
		frame.on('open', function () {
			var selection = frame.state().get('selection');
			$input.val().split(',').filter(Boolean).forEach(function (id) {
				var att = wp.media.attachment(id);
				att.fetch();
				selection.add(att);
			});
		});
		frame.on('select', function () {
			$frames.empty();
			frame.state().get('selection').each(function (att) {
				var a = att.toJSON();
				var src = a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url;
				$('<li/>').attr({ 'data-id': a.id, 'data-name': a.filename })
					.append($('<img/>').attr('src', src))
					.appendTo($frames);
			});
			sync();
		});
		frame.open();
	});

	$box.on('click', '.natur-pm-sort-frames', function () {
		var items = $frames.children().get().sort(function (a, b) {
			return String($(a).data('name')).localeCompare(String($(b).data('name')), undefined, { numeric: true });
		});
		$frames.append(items);
		sync();
	});

	$box.on('click', '.natur-pm-clear-frames', function () {
		if (window.confirm('Ștergeți toate cadrele 360° din acest produs? (imaginile rămân în Biblioteca Media)')) {
			$frames.empty();
			sync();
		}
	});
});
