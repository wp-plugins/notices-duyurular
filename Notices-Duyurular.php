<?php
/*
 * Plugin Name: Notices-Duyurular
 * Plugin URI: http://gencbilisim.net/notices-duyurular-eklentisi/
 * Description: Easy way to publish Notices in your Wordpress
 * Author: Samet ATABAŞ
 * Version: 1.6.2
 * Author URI: http://www.gencbilisim.net
 * Text Domain: Notices-Duyurular
 * Domain Path: /lang
 */
//todo Multi  site için uyumlu  hale gelecek #14
//todo Admin panelde  gözükmesi sağlanacak check box ile denetlenebilir.
//todo * Çöpe taşınıca metaların boşalması #11

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

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
	public $noticeContent = '<div class="noticeContainer notice-class">';
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
	 * The single instance of the class
	 * @var null
	 */
	protected static $_instance = null;
	/**
	 * Pencere modunda duyurunun olup olmadığını  belirtir
	 */
	public $isThereWindowType = false;

	/**
	 *
	 *
	 * @return GB_Duyurular|null
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		$this->path    = plugin_dir_path( __FILE__ );
		$this->pathUrl = plugin_dir_url( __FILE__ );
		load_plugin_textDomain( $this->textDomainString, false, basename( dirname( __FILE__ ) ) . '/lang' );
		add_action( 'add_meta_boxes', array( &$this, 'addMetaBox' ) );
		add_action( 'init', array( &$this, 'addPostType' ) );
		add_action( 'save_post', array( &$this, 'saveNotice' ) );
		add_action( 'edit_post', array( &$this, 'editNotice' ) );
		add_action( 'wp_trash_post', array( &$this, 'moveTrashNotice' ) );
		add_action( 'trash_to_publish', array( &$this, 'trashToPublish' ) );
		add_action( 'wp_footer', array( &$this, 'showNotice' ) );
		add_action( 'after_setup_theme', array( &$this, 'addScriptAndStyle' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'addStyleToAdminPage' ) );
		add_action( 'template_redirect', array( &$this, 'markAsRead' ) );

	}

	/**
	 * init action a Duyurular için yeni  post type ın  özelliklerini belirler.
	 * add_action('init', array(&$this, 'addPostType'));
	 */
	public function addPostType() {
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
			)
		);
	}

	/**
	 * Duyuru ayarlarını  belirlemek için Meta Box ekler
	 *
	 * add_action('add_meta_boxes', array(&$this, 'addMetaBox'));
	 */
	public function addMetaBox() { //todo #5
		add_meta_box( 'GB_noticeMetaBox', __( 'Notice Settings', $this->textDomainString ), array(
			&$this,
			'noticeMetaBox'
		), 'Notice', 'side', 'default' );
	}

	/**
	 * Duuru ayarları için  metabox içeriğini  oluşturur
	 */
	public function noticeMetaBox() {
		global $post_id, $wp_locale;
		$this->getMeta( $post_id );
		if ( empty( $this->meta['lastDisplayDate'] ) ) {
			$date = $this->getDate();
			$date['month'] ++; // ön tanımlı tarih o anın bir ay sonrası
			if ( $date['month'] < 10 ) {
				$date['month'] = '0' . $date['month'];
			}
		} else {
			$date = $this->getDate( $this->meta['lastDisplayDate'] );
		}
		$x = array('01','02','03','04','05','06','07','08','09','10','11','12',); //get_date_from_gtm fonkisiyonun da 1 yerine 01 olması gerekiyor
		echo '
		<form>
		  <div class="misc-pub-section">
		    <span><b>' . __( 'Who can see :', $this->textDomainString ) . '</b></span>
		    <select name="GB_D_meta[whoCanSee]">
		      <option ' . selected( $this->meta['whoCanSee'], 'everyone', false ) . ' value="everyone">' . __( 'Everyone', $this->textDomainString ) . '</option>
		      <option ' . selected( $this->meta['whoCanSee'], 'onlyUser', false ) . ' value="onlyUser">' . __( 'Only User', $this->textDomainString ) . '</option>
		    </select>
		  </div>
		  <div class="misc-pub-section">
		    <span><b>' . __( 'Display Mode :', $this->textDomainString ) . '</b></span>
		    <select name="GB_D_meta[displayMode]">
		      <option ' . selected( $this->meta['displayMode'], 'window', false ) . ' value="window">' . __( 'Window', $this->textDomainString ) . '</option>
		      <option ' . selected( $this->meta['displayMode'], 'bar', false ) . ' value="bar">' . __( 'Bar', $this->textDomainString ) . '</option>
		    </select>
		  </div>
		  <div class="clear"></div>
		  <div class="misc-pub-section curtime">
		    <span id="timestamp"><b>' . __( 'Last display date :', $this->textDomainString ) . '</b></span>
		    <br/>
		    <input type="text" maxlength="2" size="2" value="' . $date["day"] . '" name="GB_D_date[day]" id="jj">.
		    <select name="GB_D_date[month]" id="mm">';
		for ( $i = 0; $i < 12; $i ++ ) {
			echo '
			  <option ' . selected( $x[ $i ], $date['month'], false ) . ' value="' . $x[ $i ] . '">'
			     . $x[ $i ] . '-' . $wp_locale->get_month_abbrev( $wp_locale->get_month( $x[ $i ] ) ) . '
			  </option>';
		}
		echo '
		    </select>.
		    <input type="text" maxlength="4" size="4" value="' . $date["year"] . '" name="GB_D_date[year]" id="aa">@<input type="text" maxlength="2" size="2" value="' . $date["hour"] . '" name="GB_D_date[hour]" id="hh">:<input type="text" maxlength="2" size="2" value="' . $date["minute"] . '" name="GB_D_date[minute]" id="mn">
		  </div>
		  <div class="misc-pub-section">
		    <span><b>' . __( 'Type :', $this->textDomainString ) . '</b></span>
		    <div class="alert">
		      <input type="radio" ' . checked( $this->meta['type'], "", false ) . ' name="GB_D_meta[type]" value="">' . __( 'Default', $this->textDomainString ) . '
		    </div>
		    <div class="alert alert-white">
		      <input type="radio" ' . checked( $this->meta['type'], "alert-white", false ) . ' name="GB_D_meta[type]" value="alert-white">' . __( 'White', $this->textDomainString ) . '
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
		  <div class="misc-pub-section misc-pub-section-last">
		  	<span><b>' . __( 'No Border :', $this->textDomainString ) . '</b></span>
		  	<input type="checkbox" name="GB_D_meta[noBorder]" ' . checked( $this->meta['noBorder'], 'on', false ) . ' />
		  </div>';
		/*<div class="misc-pub-section misc-pub-section-last">
		  	<span><b>' . __( 'Display Time :', $this->textDomainString ) . '</b></span>
		  	<input type="text" name="GB_D_meta[displayTime]" value="' . $this->meta['displayTime'] . '" />
		  </div>
		 */
		echo '
		</form>';
	}

	/**
	 * Meta box dan  gelen duyuru ayarlarını  kaydeder
	 *
	 *  add_action('save_post', array(&$this, 'saveNotice'));
	 */
	public function saveNotice() {
		$post_id   = get_the_ID();
		$post_type = get_post_type( $post_id );
		if ( $post_type != 'notice' ) {
			return;
		}
		$this->meta                    = $_POST['GB_D_meta'];
		$GB_D_date                     = $_POST['GB_D_date'];
		$this->meta['lastDisplayDate'] = $GB_D_date['year'] . '-' . $GB_D_date['month'] . '-' . $GB_D_date['day'] . ' ' . $GB_D_date['hour'] . ':' . $GB_D_date['minute'] . ':00';
		add_post_meta( $post_id, "GB_D_meta", $this->meta, true );

	}

	/**
	 * Duyuru güncellendiğinde meta box daki  verileri ile duyuru ayarlarını günceller
	 *
	 * add_action('edit_post', array(&$this, 'editNotice'));
	 */
	public function editNotice() {
		$post_id   = get_the_ID();
		$post_type = get_post_type( $post_id );
		if ( $post_type != 'notice' ) {
			return;
		}
		$this->meta                    = $_POST['GB_D_meta'];
		$GB_D_date                     = $_POST['GB_D_date'];
		$this->meta['lastDisplayDate'] = $GB_D_date['year'] . '-' . $GB_D_date['month'] . '-' . $GB_D_date['day'] . ' ' . $GB_D_date['hour'] . ':' . $GB_D_date['minute'] . ':00';
		update_post_meta( $post_id, "GB_D_meta", $this->meta );
	}

	/**
	 * Duyuru Çöpe yollandığında çöpe yollanan duyurunun okundu  bilgileri silinir.
	 * add_action('wp_trash_post', array(&$this, 'moveTrashNotice'));
	 */
	public function moveTrashNotice() {
		$post_id   = get_the_ID();
		$post_type = get_post_type( $post_id );
		if ( $post_type != 'notice' ) {
			return;
		}
		$this->unmarkAsRead( $post_id );
	}

	/**
	 * İd numarası  verilen duyurunun meta değerleri $this->meta değişkenine aktarılır.
	 *
	 * @param $id meta bilgileri alınan duyurunun id numarası
	 */
	public function getMeta( $id ) {
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
	public function getNotice() {
		global $wpdb;
		$notices = $wpdb->get_results( "SELECT ID,post_date_gmt,post_content,post_title FROM $wpdb->posts WHERE post_type='notice' AND post_status='publish' ORDER BY ID DESC", ARRAY_A );
		$out     = array();
		foreach ( $notices as $notice ) {
			$this->getMeta( $notice['ID'] );
			$notice = array_merge( $notice, $this->meta ); //Meta bilgileri  ekleniyor.
			$out[]  = $notice;
		}

		return $out;
	}

	/**
	 * Uygun duyuruları sayfayada gösterir
	 *  add_action('wp_footer', array(&$this, 'showNotice'));
	 */
	public function showNotice() {
		foreach ( $this->getNotice() as $notice ):
			if ( $notice['lastDisplayDate'] < date_i18n( 'Y-m-d H:i:s' ) ) { // Son gösterim tarihi geçen duyuru çöpe taşınır
				wp_trash_post( $notice['ID'] );
				//log
				$data ='kullanıcı ip adresi:'.$_SERVER['REMOTE_ADDR'].'|'.$_SERVER['HTTP_USER_AGENT']."\n";
				$data .= date_i18n( 'Y-m-d H:i:s' ).' | '. $notice['ID'] . ' id numaralı ' . get_the_title( $notice['ID'] ) . ' duyurusu silindi. Duyuru son gösterim tarihi:'.$notice['lastDisplayDate'].' Aktif kullanıcı: '.wp_get_current_user()->user_login."\n";
				file_put_contents( $this->path . "/log.txt", $data, FILE_APPEND );
				continue;
			}
			if ( $this->isRead( $notice['ID'] ) ) {
				continue;
			}
			$title   = get_the_title( $notice["ID"] ) != '' ? '<h4>' . ucfirst( get_the_title( $notice["ID"] ) ) . '</h4>' : null;
			$content = do_shortcode( wpautop( $notice['post_content'] ) );
			@$noBorder = $notice['noBorder'] === 'on' ? 'noborder' : ''; //set noborder class
			switch ( $notice['displayMode'] ) {
				case 'window':
					if ( $notice['whoCanSee'] == 'everyone' || is_user_logged_in() ) {
						$this->isThereWindowType=true;
						$this->noticeContent .= sprintf(
							'<div id="window-%d" class="alert window %s %s" displayTime="%d" >
								<button type="button" class="close" >&times;</button>
								%s %s
							</div>', $notice['ID'], $notice['type'], $noBorder, @$notice['displayTime'], $title, $content );
					}
				break;
				case 'bar':
					if ( $notice['whoCanSee'] == 'everyone' || is_user_logged_in() ) {
						$this->noticeContent .= sprintf(
							'<div id="bar-%d" class="bar alert %s">
								<button type="button" class="close" >&times;</button>
								%s %s
							</div>', $notice['ID'], $notice['type'], $title, $content );
					}
				break;
			}
		endforeach;
		if($this->isThereWindowType){
			$this->noticeContent .= '</div>
			<script type="application/javascript">
				jQuery(document).ready(function ($) {
					$(".noticeContainer").GBWindow()
				});
			</script>
			';
		}
		echo $this->noticeContent;

	}
	/**
	 * Tema yüklendikten sonra script ve style  dosyalarını  ekler
	 * add_action( 'after_setup_theme', array( &$this, 'addScriptAndStyle' ) );
	 */
	public function  addScriptAndStyle() {
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueueScriptAndStyle' ) );
	}

	/**
	 * style ve script dosyalarını  yükler
	 * add_action('wp_enqueue_scripts', array(&$this, 'enqueueScriptAndStyle'));
	 */
	public function  enqueueScriptAndStyle() {
		wp_register_script( 'imagesloaded_script', plugins_url( 'js/imagesloaded.pkgd.min.js', __FILE__ ), array( 'jquery' ) );
		wp_register_script( 'notice_script', plugins_url( 'js/default.js', __FILE__ ), array( 'jquery','imagesloaded_script' ) );

		wp_register_style( 'notice_style', plugins_url( 'style.css', __FILE__ ), array( 'notice_style-reset' ) );
		wp_register_style( 'notice_style-reset', plugins_url( 'style-reset.css', __FILE__ ) );

		wp_enqueue_script( 'jquery' );
		wp_enqueue_style( 'notice_style' );
		wp_enqueue_style( 'notice_style-reset' );
		wp_enqueue_script( 'notice_script' );
		wp_enqueue_script( 'imagesloaded_script' );

		/*
		 *  Javascript dosyasında çoklu  dil  desteği
		 * <?php wp_localize_script( $handle, $name, $data ); ?>
		 * $handle -> Çoklu  dil  desteğinin  sağlanacağı js dosyasının enqueue kayıt ismi
		 * $name   -> Dizeleri  taşıyan java nesnesinin  adı
		 * $data   -> Dil desteği  sağlanan dizeler
		 */
		$closeMessage_translation_array = array(
			'content'                => __( 'If you do not want to see again this notice,click &#34;do not show again&#34;.', $this->textDomainString ),
			'dontShow'               => __( 'Do not show again', $this->textDomainString ),
			'close'                  => __( 'Close', $this->textDomainString )
		);
		$backgroundClickMessage_translation_array= array(
			'content'=> __( 'if you close notices with click background, you see notices again and again. İf you dont want see notices again, close notices with close button.', $this->textDomainString  )
		);
		wp_localize_script( 'notice_script', 'closeMessage', $closeMessage_translation_array );
		wp_localize_script( 'notice_script', 'backgroundClickMessage', $backgroundClickMessage_translation_array );
	}

	/**
	 * Admin paneline style dosyasını ekler
	 * add_action('admin_enqueue_scripts', array(&$this, 'addStyleToAdminPage'));
	 */
	public function addStyleToAdminPage() {
		/**
		 * Admin paneline eklenecek style dosyasını wordpress e kaydediyorum
		 */
		wp_register_style( 'notice_style', plugins_url( 'style.css', __FILE__ ) );
		/**
		 * Admin paneline eklenecek style dosyasını wordpress e ekliyorum
		 */
		wp_enqueue_style( 'notice_style' );
	}

	/**
	 * Duyurudaki  okundu linki tıklandığında ilgili duyuruyu okundu olarak kaydeder
	 *
	 * add_action('template_redirect','markAsRead');
	 */
	public function markAsRead() {
		$blog_id = get_current_blog_id();
		if ( isset( $_POST['GB_D_noticeId'] ) ) {
			$noticeId = $_POST['GB_D_noticeId'];
		} else {
			return;
		}
		if ( is_user_logged_in() ) {
			global $current_user;
			get_currentuserinfo();
			$okunanDuyurular   = get_user_meta( $current_user->ID, "GB_D_{$blog_id}_okunanDuyurular", true );
			$okunanDuyurular[] = $noticeId;
			update_user_meta( $current_user->ID, "GB_D_{$blog_id}_okunanDuyurular", $okunanDuyurular );

		} else {
			$this->getMeta( $noticeId );
			$expire = $this->getDate( $this->meta['lastDisplayDate'], true );
			$name= 'GB_D_'.$blog_id.'_'.md5(get_site_url($blog_id).'|'.$noticeId);
			setcookie( $name, true, $expire, '/', $_SERVER['HTTP_HOST'],is_ssl(),true );
		}
		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			wp_redirect( $_SERVER['HTTP_REFERER'] );
		}
	}

	/**
	 * Okundu  olarak  işaretlenen Duyurunun okundu  işaretini  kaldırır
	 *
	 * @param $noticeId
	 */
	public function unmarkAsRead( $noticeId ) {
		global $wpdb;
		$blog_id  = get_current_blog_id();
		$user_ids = $wpdb->get_col( "SELECT user_id FROM $wpdb->usermeta where meta_key='GB_D_{$blog_id}_okunanDuyurular'" );
		foreach ( $user_ids as $user_id ) {
			$okunanDuyurular = get_user_meta( $user_id, "GB_D_{$blog_id}_okunanDuyurular", true );
			if ( array_search( $noticeId, $okunanDuyurular ) !== false ) {
				unset( $okunanDuyurular[ array_search( $noticeId, $okunanDuyurular ) ] );
				$okunanDuyurular = array_merge( $okunanDuyurular ); //indexler  yeniden düzenleniyor
				update_user_meta( $user_id, "GB_D_{$blog_id}_okunanDuyurular", $okunanDuyurular );
			} else {
				continue;
			}
		}
		/**
		 * Okundu işareti kaldırılan duyurunun eğer cookiesi  varsa o cookie yi siliyor
		 * okunmadı olarak işaretle işlemini kullanıcı kendisi yapamadığı için bu işlem şimdilik amaçsız
		 */
		if ( isset( $_COOKIE['GB_D_'.$blog_id.'_'.md5(get_site_url($blog_id).'|'.$noticeId)] ) ) {
			$name= 'GB_D_'.$blog_id.'_'.md5(get_site_url($blog_id).'|'.$noticeId);
			$expire = time() - 36000;
			setcookie( $name, true, $expire, '/', $_SERVER['HTTP_HOST'],is_ssl(),true );
		}
	}

	/**
	 * ID numarası  belirtilen duyurunun okundu olarak işaretlenmiş olup olmadığını kontrol eder
	 *
	 * Kontrol edilecek duyurunun ID numarası
	 * @param $noticeId
	 *
	 * @return bool
	 */
	public function isRead( $noticeId ) {
		global $blog_id;
		if ( is_user_logged_in() ) {
			global $current_user;
			get_currentuserinfo();
			$okunanDuyurular = get_user_meta( $current_user->ID, 'GB_D_' . $blog_id . '_okunanDuyurular', true );

			return empty( $okunanDuyurular ) ? false : in_array( $noticeId, $okunanDuyurular );
		} else {
			$name= 'GB_D_'.$blog_id.'_'.md5(get_site_url($blog_id).'|'.$noticeId);

			return isset( $_COOKIE[ $name ] );
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
	public function getDate( $date = null, $mktime = false ) {
		if ( is_null( $date ) ) {
			$date = date_i18n( 'Y-m-d H:i:s' );
		}
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
		} else {
			return $datearr;
		}
	}

	/**
	 * Çöpten çıkarılan duyurunun meta bilgisini öntanımlı ayarlara döndürüyor.
	 */
	public function trashToPublish() {
		$post_id = get_the_ID();
		$date    = $this->getDate();
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

/**
 * GB_Duyurular sınıfını  çağırır
 * @return GB_Duyurular|null
 */
function GB_D() {
	return GB_Duyurular::instance();
}

$GLOBALS['GB_D'] = GB_D();