<?php
/*
 * Plugin Name: Notices-Duyurular
 * Plugin URI: http://gencbilisim.net/notices-duyurular-eklentisi/
 * Description: Easy way to publish Notices in your Wordpress site
 * Author: Samet ATABAŞ
 * Version: 1.3
 * Author URI: http://www.gençbilişim.net
 * Text Domain: Notices-Duyurular
 * Domain Path: /lang
 */
//todo Multi  site için uyumlu  hale gelecek #14
//todo Admin panelde  gözükmesi sağlanacak check box ile denetlenebilir.
//todo * Çöpe taşınıca metaların boşalması #11
//todo duyurudaki çarpıya basınca kullanıcıya birdaha gösterilsin mi sorusu sorulacak ve ona göre işlem yapılacak okundu linki kaldırılacak
class GB_Duyurular {

	/**
	 * Eklenti  dizinini tutar
	 * @var string
	 */
	public $path;
	/**
	 * eklenti  dizinini  url olarak  tutar
	 * @var string
	 */
	public $pathUrl;
	/**
	 * wp_footer  a  eklenecek  duyuruların html kodlarını  barındırır
	 * @var string
	 */
	public $noticeContent = '<div class="noticeContainer">';
	/**
	 * Çoklu dil için eklenti  text domain bilgisini tutar
	 * @var string
	 */
	public $textDomainString = 'Notices-Duyurular';
	/**
	 * Duyuruya ait meta bilgilerini tutar
	 * @var array
	 */
	private $meta = array();

	/**
	 * Pencere modunda duyurunun olup olmadığını  belirtir
	 */
	public $isThereWindowType = false;

	public function __construct() {
		$this->path    = plugin_dir_path( __FILE__ );
		$this->pathUrl = plugin_dir_url( __FILE__ );
		load_plugin_textDomain( $this->textDomainString, false, basename( dirname( __FILE__ ) ) . '/lang' );
		add_action( 'add_meta_boxes', array( &$this, 'GB_D_addMetaBox' ) );
		add_action( 'init', array( &$this, 'GB_D_addPostType' ) );
		add_action( 'save_post', array( &$this, 'GB_D_saveNotice' ) );
		add_action( 'edit_post', array( &$this, 'GB_D_editNotice' ) );
		add_action( 'wp_trash_post', array( &$this, 'GB_D_moveTrashNotice' ) );
		add_action( 'trash_to_publish', array( &$this, 'GB_D_trashToPublish' ) );
		add_action( 'wp_footer', array( &$this, 'GB_D_showNotice' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'GB_D_addScriptAndStyle' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'GB_D_addStyleToAdminPage' ) );
		add_action( 'template_redirect', array( &$this, 'GB_D_markAsRead' ) );

	}

	/**
	 * init action a Duyurular için yeni  post type ın  özelliklerini belirler.
	 * add_action('init', array(&$this, 'GB_D_addPostType'));
	 */
	public function GB_D_addPostType() {
		register_post_type( 'Notice',
			array(
				'labels'       => array(
					'name'               => __( 'Notice', $this->textDomainString ),
					'singular_name'      => __( 'Notice', $this->textDomainString ),
					'add_new'            => __( 'New Notice', $this->textDomainString ),
					'add_new_item'       => __( 'Add New Notice', $this->textDomainString ),
					'edit_item'          => __( 'Edit Notice', $this->textDomainString ),
					'new_item'           => __( 'New Notice', $this->textDomainString ),
					'all_items'          => __( 'All Notice', $this->textDomainString ),
					'view_item'          => __( 'View Notice', $this->textDomainString ),
					'search_items'       => __( 'Search Notice', $this->textDomainString ),
					'not_found'          => __( 'Notice Not Found', $this->textDomainString ),
					'not_found_in_trash' => __( 'Notice Not Found In Trash', $this->textDomainString ),
					'parent_item_colon'  => '',
					'menu_name'          => __( 'Notices', $this->textDomainString )
				),
				'public'       => false,
				'has_archive'  => true,
				'show_ui'      => true,
				'show_in_menu' => true,
				'menu_icon'    => $this->pathUrl . 'duyuru.png'
			)
		);
		/**
		 * Admin paneline eklenecek style dosyasını wp scriptlerine ekleriyor
		 */
		wp_register_style( 'notice_style', plugins_url( 'style.css', __FILE__ ) );
	}

	/**
	 * Duyuru ayarlarını  belirlemek için Meta Box ekler
	 *
	 * add_action('add_meta_boxes', array(&$this, 'GB_D_addMetaBox'));
	 */
	public function GB_D_addMetaBox() { //todo #5
		add_meta_box( 'GB_noticeMetaBox', __( 'Notice Settings', $this->textDomainString ), array( &$this, 'noticeMetaBox' ), 'Notice', 'side', 'default' );
	}

	/**
	 * Duuru ayarları için  metabox içeriğini  oluşturur
	 */
	public function noticeMetaBox() {
		global $post_id, $wp_locale;
		$this->GB_D_getMeta( $post_id );
		if ( empty( $this->meta['lastDisplayDate'] ) ) {
			$date = $this->GB_D_getDate();
			$date['month'] ++;
		}
		else {
			$date = $this->GB_D_getDate( $this->meta['lastDisplayDate'] );
		}
		$x = array( '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', ); //get_date_from_gtm fonkisiyonun da 1 yerine 01 olması gerekiyor
		echo '
		<form>
		  <div class="misc-pub-section">
		    <span><b>' . __( 'Who can see:', $this->textDomainString ) . '</b></span>
		    <select name="GB_D_meta[whoCanSee]">
		      <option ' . selected( $this->meta['whoCanSee'], 'everyone', false ) . ' value="everyone">' . __( 'Everyone', $this->textDomainString ) . '</option>
		      <option ' . selected( $this->meta['whoCanSee'], 'onlyUser', false ) . ' value="onlyUser">' . __( 'Only User', $this->textDomainString ) . '</option>
		    </select>
		  </div>
		  <div class="misc-pub-section">
		    <span><b>' . __( 'Display Mode:', $this->textDomainString ) . '</b></span>
		    <select name="GB_D_meta[displayMode]">
		      <option ' . selected( $this->meta['displayMode'], 'window', false ) . ' value="window">' . __( 'Window', $this->textDomainString ) . '</option>
		      <option ' . selected( $this->meta['displayMode'], 'bar', false ) . ' value="bar">' . __( 'Bar', $this->textDomainString ) . '</option>
		    </select>
		  </div>
		  <div class="clear"></div>
		  <div class="misc-pub-section curtime">
		    <span id="timestamp"><b>' . __( 'Last display date', $this->textDomainString ) . '</b></span>
		    <br/>
		    <input type="text" maxlength="2" size="2" value="' . $date["day"] . '" name="GB_D_date[day]" id="jj">.
		    <select name="GB_D_date[month]" id="mm">';
		for ( $i = 0; $i < 12; $i ++ ) {
			echo '
			  <option ' . selected( $x[$i], $date['month'], false ) . ' value="' . $x[$i] . '">'
					. $x[$i] . '-' . $wp_locale->get_month_abbrev( $wp_locale->get_month( $x[$i] ) ) . '
			  </option>';
		}
		echo '
		    </select>.
		    <input type="text" maxlength="4" size="4" value="' . $date["year"] . '" name="GB_D_date[year]" id="aa">@<input type="text" maxlength="2" size="2" value="' . $date["hour"] . '" name="GB_D_date[hour]" id="hh">:<input type="text" maxlength="2" size="2" value="' . $date["minute"] . '" name="GB_D_date[minute]" id="mn">
		  </div>
		  <div class="misc-pub-section misc-pub-section-last">
		    <span><b>' . __( 'Type:', $this->textDomainString ) . '</b></span>
		    <div class="alert">
		      <input type="radio" ' . checked( $this->meta['type'], "", false ) . ' name="GB_D_meta[type]" value="">' . __( 'Default', $this->textDomainString ) . '
		    </div>
		    <div class="alert alert-error">
		      <input type="radio" ' . checked( $this->meta['type'], "alert-error", false ) . ' name="GB_D_meta[type]" value="alert-error">' . __( 'Error', $this->textDomainString ) . '
		    </div>
		    <div class="alert alert-info">
		      <input type="radio" ' . checked( $this->meta['type'], "alert-info", false ) . ' name="GB_D_meta[type]" value="alert-info">' . __( 'Info', $this->textDomainString ) . '
		    </div>
		    <div class="alert alert-success">
		      <input type="radio" ' . checked( $this->meta['type'], "alert-success", false ) . ' name="GB_D_meta[type]" value="alert-success">' . __( 'Success', $this->textDomainString ) . '
		    </div>
		    <div class="clear"></div>
		  </div>
		</form>';
	}

	/**
	 * Meta box dan  gelen duyuru ayarlarını  kaydeder
	 *
	 *  add_action('save_post', array(&$this, 'GB_D_saveNotice'));
	 */
	public function GB_D_saveNotice() {
		$post_id   = get_the_ID();
		$post_type = get_post_type( $post_id );
		if ( $post_type != 'notice' ) return;
		$this->meta                    = $_POST['GB_D_meta'];
		$GB_D_date                     = $_POST['GB_D_date'];
		$this->meta['lastDisplayDate'] = $GB_D_date['year'] . '-' . $GB_D_date['month'] . '-' . $GB_D_date['day'] . ' ' . $GB_D_date['hour'] . ':' . $GB_D_date['minute'] . ':00';
		add_post_meta( $post_id, "GB_D_meta", $this->meta, true );
	}

	/**
	 * Duyuru güncellendiğinde meta box daki  verileri ile duyuru ayarlarını günceller
	 *
	 * add_action('edit_post', array(&$this, 'GB_D_editNotice'));
	 */
	public function GB_D_editNotice() {
		$post_id   = get_the_ID();
		$post_type = get_post_type( $post_id );
		if ( $post_type != 'notice' ) return;
		$this->meta                    = $_POST['GB_D_meta'];
		$GB_D_date                     = $_POST['GB_D_date'];
		$this->meta['lastDisplayDate'] = $GB_D_date['year'] . '-' . $GB_D_date['month'] . '-' . $GB_D_date['day'] . ' ' . $GB_D_date['hour'] . ':' . $GB_D_date['minute'] . ':00';
		update_post_meta( $post_id, "GB_D_meta", $this->meta );
	}

	/**
	 * Duyuru Çöpe yollandığında çöpe yollanan duyurunun okundu  bilgileri silinir.
	 * add_action('wp_trash_post', array(&$this, 'GB_D_moveTrashNotice'));
	 */
	public function GB_D_moveTrashNotice() {
		$post_id   = get_the_ID();
		$post_type = get_post_type( $post_id );
		if ( $post_type != 'notice' ) return;
		$this->GB_D_unmarkAsRead( $post_id );
	}

	/**
	 * İd numarası  verilen duyurunun meta değerleri $this->meta değişkenine aktarılır.
	 *
	 * @param $id meta bilgileri alınan duyurunun id numarası
	 */
	public function GB_D_getMeta( $id ) {
		$this->meta = get_post_meta( $id, 'GB_D_meta', true );
	}

	/**
	 * Duyuru bilgilerini  array olarak  getirir
	 * array(8) {
	 *  ["ID"]=>
	 *  ["post_date_gmt"]=>
	 *  ["post_content"]=>
	 *  ["post_title"]=>
	 *  ["whoCanSee"]=>
	 *  ["displayMode"]=>
	 *  ["lastDisplayDate"]=>
	 *  ["type"]=>
	 *}
	 *
	 * @return array
	 */
	public function GB_D_getNotice() {
		global $wpdb;
		$notices = $wpdb->get_results( "SELECT ID,post_date_gmt,post_content,post_title FROM $wpdb->posts WHERE post_type='notice' AND post_status='publish' ORDER BY ID DESC", ARRAY_A );
		$out     = array();
		foreach ( $notices as $notice ) {
			$this->GB_D_getMeta( $notice['ID'] );
			$notice = array_merge( $notice, $this->meta );
			$out[]  = $notice;
		}
		//echo '<pre>';print_r( $out );echo '</pre>';
		return $out;
	}

	/**
	 * Uygun duyuruları sayfaya basar
	 *  add_action('wp_footer', array(&$this, 'GB_D_showNotice'));
	 */
	public function GB_D_showNotice() {
		foreach ( $this->GB_D_getNotice() as $notice ):
			if ( $notice['lastDisplayDate'] < date_i18n( 'Y-m-d H:i:s' ) ) { // Son gösterim tarihi geçen duyuru çöpe taşınır
				wp_trash_post( $notice['ID'] );
				continue;
			}
			if ( $this->GB_D_isRead( $notice['ID'] ) ) continue;
			switch ( $notice['displayMode'] ) {
				case 'window':
					$this->isThereWindowType = true;
					if ( $notice['whoCanSee'] == 'everyone' ) {
						$this->noticeContent .= '
					  <div id="fancy-' . $notice['ID'] . '" class="alert window ' . $notice['type'] . '" >
					  	<button type="button" class="close" >&times;</button>
					    <h4>' . ucfirst( get_the_title( $notice["ID"] ) ) . '</h4>
					    ' . do_shortcode( wpautop( $notice['post_content'] ) ) . '
					  </div>';
					}
					else {
						if ( is_user_logged_in() ) {
							$this->noticeContent .= '
						    <div id="fancy-' . $notice['ID'] . '" class="alert window ' . $notice['type'] . '" >
						    	<button type="button" class="close" >&times;</button>
						      <h4>' . ucfirst( get_the_title( $notice["ID"] ) ) . '</h4>
						      ' . do_shortcode( wpautop( $notice['post_content'] ) ) . '
						    </div>';
						}
					}
					break;
				case 'bar':
					if ( $notice['whoCanSee'] == 'everyone' ) {
						$this->noticeContent .= '
					    <div id="bar-' . $notice['ID'] . '" class="bar alert ' . $notice['type'] . '">
					      <button type="button" class="close" >&times;</button>
					      <h4>' . ucfirst( get_the_title( $notice["ID"] ) ) . '</h4>
					      ' . do_shortcode( wpautop( $notice['post_content'] ) ) . '
					    </div>';
					}
					else {
						if ( is_user_logged_in() ) {
							$this->noticeContent .= '
						  <div id="bar-' . $notice['ID'] . '" class="bar alert ' . $notice['type'] . '">
						    <button type="button" class="close">&times;</button>
						    <h4>' . ucfirst( get_the_title( $notice["ID"] ) ) . '</h4>
						    ' . do_shortcode( wpautop( $notice['post_content'] ) ) . '
						  </div>';
						}
					}
					break;
			}
		endforeach;
		$this->GB_D_noticeContent();
	}

	/**
	 * Duyuruları saklayan <div class="noticeContainer"> tağını döner/yazdırır.
	 *
	 * @param bool $echo
	 *
	 * @return string
	 */
	public function GB_D_noticeContent( $echo = true ) {
		if ( $this->isThereWindowType ) {
			$this->noticeContent .= '
				<script type="text/javascript">
					duyuruWindow.show();
				</script>
			</div>';
		}
		else $this->noticeContent .= '</div>';
		if ( $echo ) {
			echo $this->noticeContent;
		}
		else {
			return $this->noticeContent;
		}
	}

	/**
	 * style ve script dosyalarını  yükler
	 * add_action('wp_enqueue_scripts', array(&$this, 'GB_D_addScriptAndStyle'));
	 */
	public function  GB_D_addScriptAndStyle() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_style( 'notice_style', plugins_url( 'style.css', __FILE__ ) );
		wp_enqueue_script( 'notice', plugins_url( 'default.js.php', __FILE__ ), array( 'jquery' ) );
	}

	/**
	 * Admin paneline style dosyasını ekler
	 * add_action('admin_enqueue_scripts', array(&$this, 'GB_D_addStyleToAdminPage'));
	 */
	public function GB_D_addStyleToAdminPage() {
		wp_enqueue_style( 'notice_style' );
	}

	/**
	 * Duyurudaki  okundu linki tıklandığında ilgili duyuruyu okundu olarak kaydeder
	 *
	 * add_action('template_redirect','GB_D_markAsRead');
	 */
	public function GB_D_markAsRead() {
		$blog_id = get_current_blog_id();
		if ( isset( $_REQUEST['GB_D_noticeId'] ) ) {
			$noticeId = $_REQUEST['GB_D_noticeId'];
		}
		else {
			return;
		}
		if ( is_user_logged_in() ) {
			global $current_user;
			get_currentuserinfo();
			$okunanDuyurular   = get_user_meta( $current_user->ID, "GB_D_{$blog_id}_okunanDuyurular", true );
			$okunanDuyurular[] = $noticeId;
			update_user_meta( $current_user->ID, "GB_D_{$blog_id}_okunanDuyurular", $okunanDuyurular );

		}
		else {
			$this->GB_D_getMeta( $noticeId );
			$expire = $this->GB_D_getDate( $this->meta['lastDisplayDate'], true );
			//todo setcookie zaman dilimini  yanlış hesaplıyor 1 saat 30 dk  fazladan ekliyor bu yüzden cookie zaman aşımı yanlış oluyor #12
			setcookie( "GB_D_{$blog_id}_okunanDuyurular[$noticeId]", 'true', $expire, '/', $_SERVER['HTTP_HOST'] );
		}
		if ( isset( $_SERVER['HTTP_REFERER'] ) ) wp_redirect( $_SERVER['HTTP_REFERER'] );
	}

	/**
	 * Okundu  olarak  işaretlenen Duyurunun okundu  işaretini  kaldırır
	 *
	 * @param $noticeId
	 */
	public function GB_D_unmarkAsRead( $noticeId ) {
		global $wpdb;
		$blog_id  = get_current_blog_id();
		$user_ids = $wpdb->get_col( "SELECT user_id FROM $wpdb->usermeta where meta_key='GB_D_{$blog_id}_okunanDuyurular'" );
		foreach ( $user_ids as $user_id ) {
			$okunanDuyurular = get_user_meta( $user_id, "GB_D_{$blog_id}_okunanDuyurular", true );
			if ( array_search( $noticeId, $okunanDuyurular ) !== false ) {
				unset( $okunanDuyurular[array_search( $noticeId, $okunanDuyurular )] );
				$okunanDuyurular = array_merge( $okunanDuyurular ); //indexler  yeniden düzenleniyor
				update_user_meta( $user_id, "GB_D_{$blog_id}_okunanDuyurular", $okunanDuyurular );
			}
			else continue;
		}
		if ( isset( $_COOKIE["GB_D_{$blog_id}_okunanDuyurular"] ) ) {
			$okunanDuyurular = $_COOKIE["GB_D_{$blog_id}_okunanDuyurular"];
			if ( array_key_exists( $noticeId, $okunanDuyurular ) ) {
				$expire = time() - 36000;
				setcookie( "GB_D_{$blog_id}_okunanDuyurular[$noticeId]", 'true', $expire, '/', $_SERVER['HTTP_HOST'] );
			}
		}
	}

	/**
	 * ID numarası  belirtilen duyurunun okundu olarak işaretlenmiş olup olmadığını kontrol eder
	 *
	 * @param $id Kontrol edilecek duyurunun ID numarası
	 *
	 * @return bool
	 */
	public function GB_D_isRead( $id ) {
		global $blog_id;
		if ( is_user_logged_in() ) {
			global $current_user;
			get_currentuserinfo();
			$okunanDuyurular = get_user_meta( $current_user->ID, 'GB_D_' . $blog_id . '_okunanDuyurular', true );
			return empty( $okunanDuyurular ) ? false : in_array( $id, $okunanDuyurular );
		}
		else {
			if ( isset( $_COOKIE['GB_D_' . $blog_id . '_okunanDuyurular'] ) ) {
				$okunanDuyurular = $_COOKIE['GB_D_' . $blog_id . '_okunanDuyurular'];
				return array_key_exists( $id, $okunanDuyurular );
			}
			else {
				return false;
			}
		}
	}

	/**
	 * ('Y-m-d H:i:s') Formatındaki tarihi dizi değişkeni olarak döndürür
	 * Eğer mktime true ise mktime işleminin sonucunu  döndürür
	 *
	 * @param null $date
	 * @param bool $mktime
	 *
	 * @return array|int
	 */
	public function GB_D_getDate( $date = null, $mktime = false ) {
		if ( is_null( $date ) ) $date = date_i18n( 'Y-m-d H:i:s' );
		$datearr = array(
			'year'   => substr( $date, 0, 4 ),
			'month'  => substr( $date, 5, 2 ),
			'day'    => substr( $date, 8, 2 ),
			'hour'   => substr( $date, 11, 2 ),
			'minute' => substr( $date, 14, 2 ),
			'second' => substr( $date, 17, 2 )
		);
		if ( $mktime ) {
			return mktime( $datearr['hour'], $datearr['minute'], $datearr['second'], $datearr['month'], $datearr['day'], $datearr['year'] );
		}
		else {
			return $datearr;
		}
	}

	/**
	 * Çöpten çıkarılan duyurunun meta bilgisini öntanımlı ayarlara döndürüyor.
	 */
	public function GB_D_trashToPublish() {
		$post_id = get_the_ID();
		$date    = $this->GB_D_getDate();
		$date['month'] ++;
		$lastDisplayDate = $date['year'] . '-' . $date['month'] . '-' . $date['day'] . ' ' . $date['hour'] . ':' . $date['minute'] . ':00';
		$this->meta      = array(
			'whoCanSee'       => 'everyone',
			'displayMode'     => 'window',
			'lastDisplayDate' => $lastDisplayDate,
			'type'            => ''
		);
		update_post_meta( $post_id, 'GB_D_meta', $this->meta );
	}
}

$GB_Duyurular = new GB_Duyurular();
?>