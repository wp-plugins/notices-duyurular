//todo gözükme süresi
//todo arka plana tıklayınca uyarı ve kapatma #35
jQuery(document).ready(function ($) {
	$.fn.GBWindow = function (parameters) {
		var param = $.extend({
			'noticesClass': 'window'
		}, parameters);

		var notices = $('.' + param.noticesClass, this).hide();
		var activeIndex = 0;
		/**
		 * Duyuru kapatılmadan önce tekrar gösterilip gösterilmeyeceğinin belirelemek için gösterilecek mesaj
		 *
		 * @type {*|jQuery|HTMLElement}
		 */
		var isShowAgain = $(
				'<div class="alert window alert-info" style="width: 100%">' +
					'<p>' + closeMessage.content + '</p>' +
					'<div id="closeButtons" class="center">' +
						'<button id="dontShow" class="btn">' + closeMessage.dontShow + '</button> - <button id="closeNotice" class="btn">' + closeMessage.close + '</button>' +
					'</div>' +
				'</div>');
		var isBackgrounClicked= false;
		var isClickBackground= $(
				'<div class="alert window alert-error" style="width: 100%">' +
					'<p>' + backgroundClickMessage.content + '</p>' +
				'</div>');		/**
		 * Duyuru içeriğindeki resim yüklenirken gösterilecek animasyon
		 *
		 * @type {*|jQuery|HTMLElement}
		 */
		var loadingAnimation=
			'<div id="noticeLoading" class="spinner">' +
			'		<div class="bounce1"></div>' +
			'		<div class="bounce2"></div>' +
			'		<div class="bounce3"></div>' +
			'</div>';

		/**
		 * bir mili saniye erteleme sonrası sayfa boyutlarına göre maksimum ve minimum boyutları belirler ve uygular
		 */
		function reLocate() {
			$('#windowBox').imagesLoaded().done(function(instance){
				var maxHeight = window.innerHeight - 80; //
				$('#windowBox .window *').css({'max-height': maxHeight})
				var top = (window.innerHeight - notices.eq(activeIndex).height()) / 2;
				var maxWidth = window.innerWidth - 115;
				$('#windowBox').css({'top': top, 'max-width': maxWidth});
				$('#windowBox .window').css({'max-width': maxWidth});
			});
		}

		/**
		 * windowBox id sine sahip nesnenin genişliğini duyurunun genişliğine ayarlayıp duyuruyu windowBox nesnesine ekler
		 * konumlandırır ve fade in animasyonu ile gösterir
		 */
		function showNotice() {
			$('#windowBox').imagesLoaded()
					.progress(function (instance, image) {
						if(!$('div').is('#noticeLoading')){
							$('#windowBox').append(loadingAnimation);
						}
					})
					.done(function (instance) {
						$('#noticeLoading','#windowBox').remove();
						$('#windowBox').width(notices.eq(activeIndex).width()).append(notices.eq(activeIndex));
						reLocate();
						notices.eq(activeIndex).fadeIn();
					})
		}

		/**
		 * body etiketi içine duyuruların gözükmesini sağlayan arka plan ekleniyor.
		 * todo show notice içerisine alına bilir, eğer yoksa ekle şeklinde
		 */
		$('body').append(
				'<div id="GBWindow">' +
				'<div class="windowBackground"></div>' +
				'<div id="windowBox">' +
				'</div>' +
				'</div>'
		);
		/**
		 * eğer birden fazla duyuru varsa ileri ve geri butonları ekleniyor
		 */
		if (notices.length > 1) {
			var previousButton = $('<a title="Previous" class="window-nav window-nav-previous" href="javascript:;"><span></span></a>');
			var nextButton = $('<a title="Next" class="window-nav window-nav-next" href="javascript:;"><span></span></a>');
			$('#windowBox').append(nextButton);
			$('#windowBox').append(previousButton);
			/**
			 * İleri butonuna tıklandığında aktif index numarasını bir artırarak sonraki duyuruyu gösterir
			 */
			nextButton.click(function () {
				notices.eq(activeIndex).fadeOut(function () {
					activeIndex++;
					if (activeIndex > notices.length - 1) activeIndex = 0;
					showNotice()
				});
			});
			/**
			 * Geri butonuna basıldığında aktif index numarasını bir azaltıp önceki duyuruyu gösterir
			 */
			previousButton.click(function () {
				notices.eq(activeIndex).fadeOut(function () {
					activeIndex--;
					if (activeIndex < 0) activeIndex = notices.length - 1;
					showNotice()
				});
			});

		}
		/**
		 *  kapat butonuna basıldığında bir daha gösterilsin mi uyarısı gösterir ve sonrasında gelen yanıta göre
		 *  duyuruyu kapatır ve varsa sonraki duyuruyu gösterir
		 */
		$('.close').click(function () {
			notices.eq(activeIndex).replaceWith(isShowAgain);
			$('#windowBox').width(350);
			isShowAgain.show();
			reLocate();
			if (notices.length > 1) {
				nextButton.hide();
				previousButton.hide();
			}
			$('#closeButtons #dontShow').click(function () {
				var currentId = notices.eq(activeIndex).attr('id');
				var reg = /\d/g;
				currentId = currentId.match(reg).join(''); // sadece sayı kısmı alınıyor
				$.post('', {GB_D_noticeId: +currentId}, 'json'); //okundu olarak işaretleme yapılıyor
				close();
			});
			$('#closeButtons #closeNotice').click(function () {
				close();
			});
			/**
			 * duyuruyu kapatıp varsa sonraki duyuruyu gösterir
			 */
			function close() {
				notices.eq(activeIndex).remove();
				notices.splice(activeIndex, 1);// duyurulardan kapatılan duyuru kaldırılıyor
				if (notices.length > 0) {
					if (notices.length == 1) {// eğer tek bir duyuru kaldıysa ileri ve geri butonları kaldırılıyor.
						nextButton.remove();
						previousButton.remove();
					} else {
						nextButton.show();
						previousButton.show();
					}

					activeIndex++;
					if (activeIndex > notices.length - 1) activeIndex = 0;
					isShowAgain.remove();
					showNotice()

				} else {//eğer duyuru kalmadıysa duyuru penceresi kapatılıyor.
					$('#GBWindow').remove();
				}
			}
		});

		$('.windowBackground').click(function () {
			if (!isBackgrounClicked) {
				isBackgrounClicked=true;
				notices.eq(activeIndex).replaceWith(isClickBackground);
				$('#windowBox').width(350);
				isClickBackground.show();
				nextButton.hide();
				previousButton.hide();
				reLocate();
				setTimeout(function () {
					$('#GBWindow').fadeOut();
				}, 5500);
			}
		});

		/**
		 * ekran yeniden boyutlandırıldığında duyuruyu sayfada yeniden konumlandırır
		 */
		$(window).resize(function () {
			reLocate();
		});

		showNotice();
	};
});

jQuery(document).ready(function () {
	//adminbar yüksekiliği notice container e aktarılıyor
	jQuery('.noticeContainer').css({'top': jQuery('#wpadminbar').height()});

	jQuery('.bar .close').click(function () {
		//Aktif duyurunun id bilgisi  alınıyor
		var currentId = jQuery(this).parent()[0].id;

		var reg = /\d/g;
		currentId = currentId.match(reg).join(''); //id  değerinin sadece sayı olduğu doğrulanıyor.

		// çoklu  dil desteği için closeMessage nesnesi kullanılıyor ilgili fonksiyon: GB_D_addScriptAndStyle
		var icerik =
				'<div class="bar alert alert-info">' +
						'<h4></h4>' +
						'<p>' + closeMessage.content + '</p>' +
						'<button id="yes" class="btn">' + closeMessage.dontShow + '</button> - <button id="no" class="btn">' + closeMessage.close + '</button>' +
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
