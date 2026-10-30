jQuery(function ($) {
	var select = $('#medusa-a11y-colour');
	var custom = $('.medusa-a11y-custom');
	$('.medusa-a11y-picker').wpColorPicker();
	function sync() {
		custom.toggle(select.val() === 'custom');
	}
	select.on('change', sync);
	sync();
});
