;(function ($) {
	$(document).on('click', '[data-submitwarning]', function (e) {
		return confirm($(this).attr('data-submitwarning'));
	})
})(jQuery);