jQuery(document).ready(function ($) {
	/* çarpıya  basınca  uyarıyı  ekrandan kaldırma işlemi */
	$('.close').click(function () {
		$(this).parent().remove();
	});
});