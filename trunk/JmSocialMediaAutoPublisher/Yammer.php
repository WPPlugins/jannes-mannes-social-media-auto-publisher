<?php
/**
 * Created by PhpStorm.
 * User: janhenkes
 * Date: 10/01/17
 * Time: 16:25
 */

namespace JmSocialMediaAutoPublisher;


use YammerPHP\YammerPHPException;

class Yammer {
	private static $instance = null;
	const transient_access_token = 'yammer_access_token_v2';
	const transient_access_token_expires_at = 'yammer_access_token_expires_at_v1';
	const cache_key_full_access_token = 'yammer_full_access_token_v1';

	public static function instance() {
		if ( ! is_null( self::$instance ) ) {
			return self::$instance;
		}

		return self::$instance = new \YammerPHP\YammerPHP( [
			'consumer_key'    => Admin::get_options()['yammer_client_id'],
			'consumer_secret' => Admin::get_options()['yammer_client_secret'],
			'callbackUrl'     => Plugin::redirect_url() . '&yammer-callback',
		] );
	}

	public static function getLoginUrl() {
		return self::instance()->getAuthorizationUrl();
	}

	public static function notify_access_token_expired_event() {
		$to = get_option( 'admin_email' );
		wp_mail( $to, 'Yammer access token verlopen', "Dear {$to}, your Yammer access token is expired. Please log in to your site and refresh the access token. " . get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=jm-social-media-auto-publisher' );
	}

	public static function saveAccessToken() {
		try {
			$code        = $_GET['code'];
			$accessToken = self::instance()->getAccessToken( $code );

			$expires_at = new \DateTime();
			$expires_at->modify( '+30 days' );
			Plugin::cache_instance()
			      ->set( self::transient_access_token_expires_at, $expires_at->getTimestamp(), $expires_at->getTimestamp() - time() );
			Plugin::cache_instance()
			      ->set( self::transient_access_token, (string) $accessToken->access_token->token, $expires_at->getTimestamp() - time() );
			Plugin::cache_instance()
			      ->set( self::cache_key_full_access_token, $accessToken, $expires_at->getTimestamp() - time() );

			$schedule = wp_next_scheduled( 'jm_smap_yammer_notify_access_token_expired_event' );
			wp_unschedule_event( $schedule, 'jm_smap_yammer_notify_access_token_expired_event' );
			wp_schedule_single_event( $expires_at->getTimestamp(), 'jm_smap_yammer_notify_access_token_expired_event' );

			//wp_redirect( admin_url( 'admin.php?page=' . Plugin::$text_domain . '-connect-accounts' ) );
		} catch ( \Exception $exception ) {
			error_log( $exception->__toString(), 0 );
		}
	}

	public static function getAccessToken( $full = false ) {
		return Plugin::cache_instance()->get( ! $full ? self::transient_access_token :
				self::cache_key_full_access_token );
	}

	public static function isAccessTokenExpired() {
		if ( ! Plugin::cache_instance()->get( self::transient_access_token_expires_at ) ) {
			return true;
		}
		$expring_date = self::getAccessTokenExpiringDate();
		$now          = new \DateTime( 'now', new \DateTimeZone( get_option( 'timezone_string' ) ?
				get_option( 'timezone_string' ) : 'Europe/Amsterdam' ) );

		return $expring_date < $now;
	}

	public static function getAccessTokenExpiringDate() {
		$datetime = new \DateTime( '', new \DateTimeZone( 'UTC' ) );
		$datetime->setTimestamp( Plugin::cache_instance()->get( self::transient_access_token_expires_at ) );

		return $datetime;
	}

	public static function post_link( $post_id, $link, $message ) {
		$access_token = self::getAccessToken();
		self::instance()->setOAuthToken( $access_token );

		if ( ! self::instance()->testAuth() ) {
			// Handle this.
		}

		$full_access_token = self::getAccessToken( true );

		//var_dump( $full_access_token );

		try {
			$message = self::instance()->request( 'https://www.yammer.com/api/v1/messages.json', [
					'body'    => $message . ' ' . $link,
					'network' => $full_access_token->network->id,
				], true );
			//var_dump( $message );

			$message = array_shift( $message->messages );
			update_post_meta( $post_id, 'yammer_message_id', $message->id );
			update_post_meta( $post_id, 'yammer_message_api_url', $message->url );
			update_post_meta( $post_id, 'yammer_message_web_url', $message->web_url );

		} catch ( YammerPHPException $e ) {
			error_log( $e->__toString(), 0 );
		}

		return true;
	}
}