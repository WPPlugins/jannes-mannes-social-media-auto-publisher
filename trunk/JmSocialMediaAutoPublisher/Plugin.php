<?php
/**
 * Created by PhpStorm.
 * User: janhenkes
 * Date: 22/07/16
 * Time: 15:42
 */

namespace JmSocialMediaAutoPublisher;

use JmSocialMediaAutoPublisher\Admin\Post;
use phpFastCache\CacheManager;

class Plugin {
	public static $text_domain = 'jm-social-media-auto-publisher';
	const capability = 'jm_smap_settings';
	private static $cache_dir = '';
	private static $cache_instance = null;

	public static function init() {
		add_action( 'admin_menu', [ Admin::class, 'add_menu_page' ] );
		add_action( 'admin_init', [ Admin::class, 'register_settings' ] );
		add_filter( 'option_page_capability_jm-smap-setting-group', [Plugin::class, 'get_capability'] );

		add_action( 'save_post', [ Publisher::class, 'schedule_publishes' ], 10, 3 );
		add_action( Publisher::event_publish, [ Publisher::class, 'publish' ], 10, 4 );

		add_action( 'jm_smap_facebook_notify_access_token_expired_event', [
			Facebook::class,
			'notify_access_token_expired_event'
		] );

		add_action( 'jm_smap_linkedin_notify_access_token_expired_event', [
			LinkedIn::class,
			'notify_access_token_expired_event'
		] );

		add_action( 'jm_smap_twitter_notify_access_token_expired_event', [
			Twitter::class,
			'notify_access_token_expired_event'
		] );

		add_action( 'jm_smap_yammer_notify_access_token_expired_event', [
			Yammer::class,
			'notify_access_token_expired_event'
		] );

		add_action( 'admin_init', [ get_called_class(), 'add_capabilities' ] );

		Post::init();
	}

	public static function redirect_url () {
		return admin_url( 'admin.php?page=' . Plugin::$text_domain . '-connect-accounts' );
	}

	public static function get_capability() {
		return self::capability;
	}

	public static function add_capabilities() {
		$editor_roles = [ get_role( 'editor' ), get_role( 'administrator' ) ];
		foreach ( $editor_roles as $editor_role ) {
			if ( $editor_role instanceof \WP_Role ) {
				$editor_role->add_cap( self::capability );
			}
		}
	}

	public static function clear_cache() {
		self::cache_instance()
		    ->clean();
	}

	public static function cache_instance() {
		if ( ! is_null( self::$cache_instance ) ) {
			return self::$cache_instance;
		}

		self::check_cache_dir();
		CacheManager::setup( [ 'path' => self::$cache_dir ] );

		return self::$cache_instance = CacheManager::Files();
	}

	private static function check_cache_dir() {
		self::$cache_dir = trailingslashit( WP_CONTENT_DIR ) . "uploads/jm-smap-cache";
		if ( ! file_exists( self::$cache_dir ) ) {
			mkdir( self::$cache_dir );
			$h = fopen( self::$cache_dir . '/.htaccess', 'a+' );
			if ( ! $h ) {
				throw new \Exception( 'Could not create htaccess file in dir' );
			}

			flock( $h, LOCK_EX ); // exclusive lock, will get released when the file is closed

			fseek( $h, 0 ); // go to the start of the file

			// truncate the file
			ftruncate( $h, 0 );

			// Serializing along with the TTL
			$data = "order deny,allow\r\ndeny from all";
			if ( fwrite( $h, $data ) === false ) {
				throw new \Exception( 'Could not write to htaccess file' );
			}
			fclose( $h );
		}
	}

	public static function get_timezone() {
		return get_option( 'timezone_string' ) ? get_option( 'timezone_string' ) : date_default_timezone_get();
	}

	public static function on_activate() {
		global $wpdb;
		$posts = $wpdb->get_results("SELECT ID FROM $wpdb->posts WHERE post_status IN ('publish', 'future', 'trash', 'private')");
		foreach ($posts as $post) {
			update_post_meta( $post->ID, 'jm_smap_published', 1 );
		}
	}
}