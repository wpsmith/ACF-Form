(function ($) {
	var $input = $('.acf-field-password').find('input');
	$input.after("<span style=\"position:relative;float:right;margin-top:-25px;margin-right:15px;z-index:2;\" " +
		"toggle=\"input[name='" + $input.attr('name') + "']\" class=\"dashicons dashicons-visibility field-icon toggle-password\"></span>")

	$(".toggle-password").click(function () {
		$(this).toggleClass("dashicons-visibility dashicons-hidden");
		var input = $($(this).attr("toggle"));
		if (input.attr("type") == "password") {
			input.attr("type", "text");
		} else {
			input.attr("type", "password");
		}
	});
})(jQuery);

// (function (){
// 	acf.addAction('load_field/name=is_singular_bucket_list_item', function(field) {
// 		debugger;
// 	});
// 	acf.addAction('ready_field/name=is_singular_bucket_list_item', function(field) {
// 		debugger;
// 	});
// 	acf.addAction('hide_field/name=is_singular_bucket_list_item', function(field) {
// 		debugger;
// 	});
// 	acf.addAction('hide_field/name=bucket_list_item', function(field) {
// 		debugger;
// 	});
// 	acf.addAction('disable_field/name=is_singular_bucket_list_item', function(field) {
// 		debugger;
// 	});
// })();
