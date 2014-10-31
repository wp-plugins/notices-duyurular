/**
 * pencere modundaki duyuruları gösterecek fonksiyon
 */
jQuery.fn.Window = function (content, isClass) {
	this.currentIndex = 0;
	this.return = false;
	this.getContent = function () {
		isClass ? this.content = jQuery(content) : this.content = content;
		if (isClass) this.content.remove();//window class ına sahip nesneleri sayfadan temizledik
	}
	/**
	 * Duyurunun ekranda gösterim süresini alan fonksiyon
	 */
	this.getDisplayTime = jQuery.proxy(function () {
		console.log(jQuery(this.content[this.currentIndex]).attr('displaytime'));
	}, this);
	/**
	 * sayfadaki  konumu  yeniden  düzenler
	 */
	this.reLocate = function () {
		jQuery('.window').css({'max-height': (window.innerHeight - 100), 'max-width': (window.innerWidth - 100)});
		jQuery('.window *').css({'max-height': (window.innerHeight - 128), 'max-width': (window.innerWidth - 128)});
		setTimeout(function(){
			var windowBoxWidth = jQuery('#windowBox').width();
			var windowBoxHeight = jQuery('#windowBox').children('.alert').outerHeight(); console.log('height='+windowBoxHeight);
			var windowBoxLeft = (window.innerWidth - windowBoxWidth) / 2;
			var windowBoxTop = (window.innerHeight - windowBoxHeight) / 2;console.log('top='+windowBoxTop);console.log('window.innerHeight='+window.innerHeight);
			jQuery('#windowBox').css({
				'left'      : windowBoxLeft,
				'top'       : windowBoxTop,
				'max-height': (window.innerHeight - 100),
				'max-width' : (window.innerWidth - 100)
			});
		},500);//yeni boyuta göre yüksekliğin ayarlanması ve sonra konumlandırılması için bekleme


	};

	/**
	 * sonraki  duyuruyu getirir
	 *
	 */
	this.next = function () {
		this.currentIndex--;
		if (this.currentIndex < 0) this.currentIndex = this.content.length - 1;
		jQuery('#windowBox').fadeOut(jQuery.proxy(function () {
			jQuery('#windowBox').find('.window').replaceWith(this.content[this.currentIndex]);
			this.getDisplayTime();
			jQuery('#windowBox').css({'display': 'block'});
			jQuery('.window .close').click(jQuery.proxy(function () {
				this.hide();
			}, this));
			this.reLocate();
		}, this));
	};

	/**
	 * önceki duyuruyu getirir
	 *
	 */
	this.prev = function () {
		this.currentIndex++;
		if (this.currentIndex > this.content.length - 1) this.currentIndex = 0;
		jQuery('#windowBox').fadeOut(jQuery.proxy(function () {
			jQuery('#windowBox').css({'display': 'block'});
			jQuery('#windowBox').find('.window').replaceWith(this.content[this.currentIndex]);
			this.getDisplayTime();
			jQuery('.window .close').click(jQuery.proxy(function () {
				this.hide();
			}, this));
			this.reLocate();
		}, this));
	};
	/**
	 * pencereyi ekranda gösterir
	 */
	this.show = function () {
		this.getContent();
		jQuery('body').append('<div id="windowBackground" class="notice-class"><div class="windowBackground"></div></div>');
		jQuery('#windowBackground').append('<div id="windowBox" class=""></div>');//window class lı nesnenin ekleneceği div eklendi
		if (isClass) {
			jQuery('#windowBox').append(this.content[this.currentIndex]);
			this.getDisplayTime();
		} else {
			jQuery('#windowBox').append(this.content);
		}
		if (this.content.length > 1 && isClass) {
			jQuery('#windowBox').append('<a href="javascript:;" class="window-nav window-nav-previous" title="Previous"><span></span></a>');
			jQuery('#windowBox').append('<a href="javascript:;" class="window-nav window-nav-next" title="Next"><span></span></a>');
			jQuery('.window-nav-previous').click(jQuery.proxy(function () {
				this.prev();
			}, this));
			jQuery('.window-nav-next').click(jQuery.proxy(function () {
				this.next();
			}, this));
		}
		jQuery('.window .close').click(jQuery.proxy(function () {
			this.hide();
		}, this));
		//arka plana tıklayınca silinsin
		jQuery('.windowBackground').click(jQuery.proxy(function () {
			this.close();
		}, this));
		this.reLocate();
	};
	this.hide = function () {
		var icerik = '<div class="alert window alert-info">' +
				'<h4></h4>' +
				'<p>' + message.content + '</p>' +
				'<div id="yes-no" class="center">' +
				'<button id="yes" class="btn">' + message.dontShow + '</button> - <button id="no" class="btn">' + message.close + '</button>' +
				'</div>' +
				'</div>';
		var genislik = jQuery('#windowBox').width();
		jQuery('#windowBox').find('.window').replaceWith(icerik);
		jQuery('#windowBox .window').width(genislik);
		jQuery('#yes-no #yes').click(jQuery.proxy(function () {
			currentId = this.content[this.currentIndex].id;
			var reg = /\d/g;
			currentId = currentId.match(reg).join('');
			jQuery.ajax({
				type: "GET",
				data: "GB_D_noticeId=" + currentId
			});
			this.content.splice(this.currentIndex, 1);
			if (this.content.length > 0) {
				this.next();
				if (this.content.length == 1)jQuery('.window-nav').remove();
			} else {
				close(jQuery('#windowBackground'));
			}
		}, this));
		jQuery('#yes-no #no').click(jQuery.proxy(function () {
			this.content.splice(this.currentIndex, 1);
			if (this.content.length > 0) {
				this.next();
				if (this.content.length == 1)jQuery('.window-nav').remove();
			} else {
				close(jQuery('#windowBackground'));
			}
		}, this));
	}
	this.close = function () {
		close(jQuery('#windowBackground'));
	}
	return this;
};
/**
 * parametre ile girilen nesneyi  siler
 * @param obj
 */
function close(obj) {
	obj.fadeOut('slow', function () {
		jQuery(this).detach();
	});
};

var duyuruWindow = jQuery(document.body).Window('.window', true);

jQuery(document).ready(function () {
	//adminbar yüksekiliği notice container e aktarılıyor
	jQuery('.noticeContainer').css({'top': jQuery('#wpadminbar').height()});

	jQuery('.bar .close').click(function () {
		//Aktif duyurunun id bilgisi  alınıyor
		var currentId = jQuery(this).parent()[0].id;

		var reg = /\d/g;
		currentId = currentId.match(reg).join(''); //id  değerinin sadece sayı olduğu doğrulanıyor.

		// çoklu  dil desteği için message nesnesi kullanılıyor ilgili fonksiyon: GB_D_addScriptAndStyle
		var icerik =
				'<div class="bar alert alert-info">' +
						'<h4></h4>' +
						'<p>' + message.content + '</p>' +
						'<button id="yes" class="btn">' + message.dontShow + '</button> - <button id="no" class="btn">' + message.close + '</button>' +
				'</div>';

		jQuery('.noticeContainer').find('#bar-' + currentId).replaceWith(icerik);

		jQuery('#yes').click(function () {
			jQuery.ajax({
				type: "GET",
				data: "GB_D_noticeId=" + currentId
			});
			close(jQuery(this).parent());
		});

		jQuery('#no').click(function () {
			close(jQuery(this).parent());
		});
	});
});
//Sayfa boyutu değiştirildiğinde duyuru yeniden konumlandırılıyor.
jQuery(window).resize(function () {
	duyuruWindow.reLocate();
});