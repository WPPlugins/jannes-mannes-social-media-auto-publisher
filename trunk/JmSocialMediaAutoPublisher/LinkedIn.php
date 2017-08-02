<?php
/**
 * Created by PhpStorm.
 * User: janhenkes
 * Date: 01/10/16
 * Time: 22:12
 *
 * @link https://packagist.org/packages/happyr/linkedin-api-client
 * @link https://www.linkedin.com/developer/apps/4280814/auth
 */

namespace JmSocialMediaAutoPublisher;


class LinkedIn {
	private static $instance = null;
	const transient_access_token = 'jm-smap-linkedin-access-token';
	const transient_access_token_expires_at = 'jm-smap-linkedin-access-token-expiration';

	public static function notify_access_token_expired_event() {
		$to = get_option( 'admin_email' );
		wp_mail( $to, 'LinkedIn access token verlopen', "Dear {$to}, your LinkedIn access token is expired. Please log in to your site and refresh the access token. " . get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=jm-social-media-auto-publisher' );
	}

	public static function instance() {
		if ( ! is_null( self::$instance ) ) {
			return self::$instance;
		}

		return self::$instance = new \Happyr\LinkedIn\LinkedIn( Admin::get_options()['linkedin_client_id'],
			Admin::get_options()['linkedin_client_secret'] );
	}

	public static function is_authenticated() {
		try {
			return self::instance()
			           ->isAuthenticated();
		} catch ( \Exception $e ) {
			return false;
		}
	}

	public static function save_access_token() {
		$access_token = self::instance()
		                    ->getAccessToken();
		if ( $access_token === null ) {
			return;
		}

		$expiration_time = 60 * 60 * 24 * 60;
		$expires_at      = time() + $expiration_time;
		Plugin::cache_instance()
		      ->set( self::transient_access_token_expires_at, $expires_at, $expiration_time );

		$schedule = wp_next_scheduled( 'jm_smap_linkedin_notify_access_token_expired_event' );
		wp_unschedule_event( $schedule, 'jm_smap_linkedin_notify_access_token_expired_event' );
		wp_schedule_single_event( $expires_at, 'jm_smap_linkedin_notify_access_token_expired_event' );

		Plugin::cache_instance()
		      ->set( self::transient_access_token, (string) $access_token, $expiration_time );
	}

	public static function get_access_token() {
		return Plugin::cache_instance()
		             ->get( self::transient_access_token );
	}


	public static function is_access_token_expired() {
		if ( ! Plugin::cache_instance()
		             ->get( self::transient_access_token_expires_at )
		) {
			return true;
		}
		$expring_date = self::get_access_token_expiry_date();
		$now          = new \DateTime( 'now', new \DateTimeZone( Plugin::get_timezone() ) );

		return $expring_date < $now;
	}

	public static function get_access_token_expiry_date() {
		$datetime = new \DateTime( '', new \DateTimeZone( 'UTC' ) );
		$datetime->setTimestamp( Plugin::cache_instance()
		                               ->get( self::transient_access_token_expires_at ) );

		return $datetime;
	}

	public static function get_login_url() {
		$url = self::instance()
		           ->getLoginUrl();
		echo "<a href='$url'>Connect LinkedIn</a>";
	}

	public static function get_pages() {
		if ( ! self::get_access_token() ) {
			return [];
		}

		self::instance()
		    ->setAccessToken( self::get_access_token() );

		$options = [
			'query' => [
				'is-company-admin' => 'true',
				'format'           => 'json',
			]
		];
		$result  = self::instance()
		               ->get( 'v1/companies', $options );

		return isset( $result['values'] ) ? $result['values'] : [];
	}

	public static function post_link( $post_id, $link, $message, $title = '', $description = '', $image = '' ) {
		$access_token = self::get_access_token();
		if ( ! $access_token ) {
			return false;
		}

		$linkedIn = self::instance();
		$linkedIn->setAccessToken( $access_token );

		$options = array(
			'json' => array(
				'comment'    => $message,
				'visibility' => array(
					'code' => 'anyone'
				),
				'content'    => [
					'title'               => $title,
					'description'         => $description,
					'submitted-url'       => $link,
					'submitted-image-url' => $image,
				]
			)
		);

		$result = $linkedIn->post( 'v1/people/~/shares', $options );

		update_post_meta( $post_id, 'linkedin_post_key', $result['updateKey'] );
		update_post_meta( $post_id, 'linkedin_post_url', $result['updateUrl'] );

		return true;
	}

	public static function post_page_link( $post_id, $link, $message, $page_id, $title = '', $description = '', $image = '' ) {
		self::instance()
		    ->setAccessToken( self::get_access_token() );

		$options = array(
			'json' => array(
				'comment'    => $message,
				'visibility' => array(
					'code' => 'anyone'
				),
				'content'    => [
					'title'               => $title,
					'description'         => $description,
					'submitted-url'       => $link,
					'submitted-image-url' => $image,
				]
			)
		);

		$result = self::instance()
		              ->post( 'v1/companies/' . $page_id . '/shares', $options );

		update_post_meta( $post_id, 'linkedin_post_key', $result['updateKey'] );
		update_post_meta( $post_id, 'linkedin_post_url', $result['updateUrl'] );

		return true;
	}
}