/**
 * pencere modundaki duyuruları gösterecek fonksiyon
 */
function showWindowType() {
	var notices = jQuery.makeArray(jQuery('.window'));// window class ına sahip nesneleri diziye çevirip notices değişkenine atadık
	jQuery('.window').remove();//window class ına sahip nesneleri sayfadan temizledik
	jQuery('body').append('<div id="windowBackground"><div class="windowBackground"></div></div>');//body etiketine window tipli duyuruların gözükmesi için arkaplan div i ekledik
	jQuery('#windowBackground').fadeIn();
	jQuery('#windowBackground .windowBackground').click(function () {
		jQuery(this).parent().fadeOut('slow', function () {
			jQuery(this).remove()
		});
	});//arka plana tıklayınca silinsin
	jQuery('#windowBackground').append('<div id="windowBox" class=""></div>');//window class lı nesnenin ekleneceği div eklendi
	var i = 0;
	jQuery('#windowBox').append(notices[0]);//ilk duyuru windowBox id li  div içine eklendi
	if (notices.length > 1) {
		jQuery('#windowBox').append('<a href="javascript:;" class="window-nav window-nav-previous" title="Previous"><span></span></a>');
		jQuery('#windowBox').append('<a href="javascript:;" class="window-nav window-nav-next" title="Next"><span></span></a>');
		jQuery('.window-nav-previous').click(function () {
			i--;
			if (i < 0) i = (notices.length - 1);
			jQuery('#windowBox .window').fadeOut(function () {
				jQuery(this).css({'display': 'block'});
				jQuery(this).replaceWith(notices[i]);
				reLocate();
			});
		});
		jQuery('.window-nav-next').click(function () {
			i++;
			if (i > (notices.length - 1)) i = 0;
			jQuery('#windowBox .window').fadeOut(function () {
				jQuery(this).css({'display': 'block'});
				jQuery(this).replaceWith(notices[i]);
				reLocate();
			});
		});
	}
	reLocate();
}

/**
 * sayfadaki  konumu  yeniden  düzenler
 */
function reLocate() {
	jQuery('.window').css({'max-height': (window.innerHeight / 2), 'max-width': (window.innerWidth / 2)});
	var windowBoxWidth = jQuery('#windowBox').width();
	var windowBoxHeight = jQuery('#windowBox').height();
	var windowBoxLeft = (window.innerWidth - windowBoxWidth) / 2;
	var windowBoxTop = (window.innerHeight - windowBoxHeight) / 2;
	jQuery('#windowBox').css({
		'left': windowBoxLeft,
		'top' : windowBoxTop
	});

}

jQuery(document).ready(function () {
	/* çarpıya  basınca  uyarıyı  ekrandan kaldırma işlemi */
	jQuery('.close').click(function () {
		jQuery(this).parent().fadeOut(function () {
			jQuery(this).remove()
		});
	});
	jQuery('.noticeContainer').css({'top': jQuery('#wpadminbar').height()});//adminbar yüksekiliği notice container e aktarılıyor
});

jQuery(window).resize(function () {
	reLocate();
});
//todo #20