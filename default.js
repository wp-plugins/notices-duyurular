var jQ = jQuery.noConflict(true); //jQ kullanabilmek için jQuery ataması bu  yeterli mi  araştırmak lazım

/**
 * pencere modundaki duyuruları gösterecek fonksiyon
 */
function showWindowType() {
	var notices = jQ.makeArray(jQ('.window'));// window class ına sahip nesneleri diziye çevirip notices değişkenine atadık
	jQ('.window').remove();//window class ına sahip nesneleri sayfadan temizledik
	jQ('body').append('<div id="windowBackground"><div class="windowBackground"></div></div>');//body etiketine window tipli duyuruların gözükmesi için arkaplan div i ekledik
	jQ('#windowBackground').fadeIn();
	jQ('#windowBackground .windowBackground').click(function () {
		jQ(this).parent().fadeOut('slow', function () {
			jQ(this).remove()
		});
	});//arka plana tıklayınca silinsin
	jQ('#windowBackground').append('<div id="windowBox" class=""></div>');//window class lı nesnenin ekleneceği div eklendi
	var i = 0;
	jQ('#windowBox').append(notices[0]);//ilk duyuru windowBox id li  div içine eklendi
	if (notices.length > 1) {
		jQ('#windowBox').append('<a href="javascript:;" class="window-nav window-nav-previous" title="Previous"><span></span></a>');
		jQ('#windowBox').append('<a href="javascript:;" class="window-nav window-nav-next" title="Next"><span></span></a>');
		jQ('.window-nav-previous').click(function () {
			i--;
			if (i < 0) i = (notices.length - 1);
			jQ('#windowBox .window').fadeOut(function () {
				jQ(this).css({'display': 'block'});
				jQ(this).replaceWith(notices[i]);
				reLocate();
			});
		});
		jQ('.window-nav-next').click(function () {
			i++;
			if (i > (notices.length - 1)) i = 0;
			jQ('#windowBox .window').fadeOut(function () {
				jQ(this).css({'display': 'block'});
				jQ(this).replaceWith(notices[i]);
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
	jQ('.window').css({'max-height': (window.innerHeight / 2), 'max-width': (window.innerWidth / 2)});
	var windowBoxWidth = jQ('#windowBox').width();
	var windowBoxHeight = jQ('#windowBox').height();
	var windowBoxLeft = (window.innerWidth - windowBoxWidth) / 2;
	var windowBoxTop = (window.innerHeight - windowBoxHeight) / 2;
	jQ('#windowBox').css({
		'left': windowBoxLeft,
		'top' : windowBoxTop
	});

}

jQ(document).ready(function () {
	/* çarpıya  basınca  uyarıyı  ekrandan kaldırma işlemi */
	jQ('.close').click(function () {
		jQ(this).parent().fadeOut(function () {
			jQ(this).remove()
		});
	});
	jQ('.noticeContainer').css({'top': jQ('#wpadminbar').height()});//adminbar yüksekiliği notice container e aktarılıyor
});

jQ(window).resize(function () {
	reLocate();
});
