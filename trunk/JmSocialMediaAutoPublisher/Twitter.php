<?php
/**
 * Created by PhpStorm.
 * User: janhenkes
 * Date: 10/01/17
 * Time: 16:25
 */

namespace JmSocialMediaAutoPublisher;


use Simplon\Twitter\TwitterException;

class Twitter {
	private static $instance = null;
	const transient_access_token = 'twitter_access_token_v2';
	const transient_access_token_secret = 'twitter_access_token_secret_v2';
	const transient_access_token_expires_at = 'twitter_access_token_expires_at_v2';

	public static function instance() {
		if ( ! is_null( self::$instance ) ) {
			return self::$instance;
		}

		return self::$instance = new \Simplon\Twitter\Twitter( Admin::get_options()['twitter_api_key'],
			Admin::get_options()['twitter_api_secret'] );
	}

	public static function getLoginUrl() {
		try {
			$oauthRequestTokenVo = self::instance()
			                           ->requestOauthRequestToken( Plugin::redirect_url() . '&twitter-callback' );

			return self::instance()
			           ->getAuthenticationUrl( $oauthRequestTokenVo->getOauthToken() );
		} catch ( TwitterException $e ) {
			var_dump( $e->getMessage() );
			error_log( $e->__toString(), 0 );

			return '';
		}
	}

	public static function notify_access_token_expired_event() {
		$to = get_option( 'admin_email' );
		wp_mail( $to, 'Twitter access token verlopen', "Dear {$to}, your Twitter access token is expired. Please log in to your site and refresh the access token. " . get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=jm-social-media-auto-publisher' );
	}

	public static function saveAccessToken() {
		try {
			$oauth_token        = $_GET['oauth_token'];
			$oauth_verifier     = $_GET['oauth_verifier'];
			$oauthAccessTokenVo = self::instance()
			                          ->requestOauthAccessToken( $oauth_token, $oauth_verifier );

			$expires_at = new \DateTime();
			$expires_at->modify( '+30 days' );
			Plugin::cache_instance()
			      ->set( self::transient_access_token_expires_at, $expires_at->getTimestamp(),
				      $expires_at->getTimestamp() - time() );
			Plugin::cache_instance()
			      ->set( self::transient_access_token, (string) $oauthAccessTokenVo->getOauthToken(),
				      $expires_at->getTimestamp() - time() );
			Plugin::cache_instance()
			      ->set( self::transient_access_token_secret, (string) $oauthAccessTokenVo->getOauthTokenSecret(),
				      $expires_at->getTimestamp() - time() );

			$schedule = wp_next_scheduled( 'jm_smap_twitter_notify_access_token_expired_event' );
			wp_unschedule_event( $schedule, 'jm_smap_twitter_notify_access_token_expired_event' );
			wp_schedule_single_event( $expires_at->getTimestamp(), 'jm_smap_twitter_notify_access_token_expired_event' );

			//wp_redirect( admin_url( 'admin.php?page=' . Plugin::$text_domain . '-connect-accounts' ) );
		} catch ( \Exception $exception ) {
			var_dump( $exception );
			error_log( $exception->__toString(), 0 );
		}
	}

	public static function getAccessToken() {
		return Plugin::cache_instance()
		             ->get( self::transient_access_token );
	}

	public static function getAccessTokenSecret() {
		return Plugin::cache_instance()
		             ->get( self::transient_access_token_secret );
	}

	public static function isAccessTokenExpired() {
		if ( ! Plugin::cache_instance()
		             ->get( self::transient_access_token_expires_at )
		) {
			return true;
		}
		$expring_date = self::getAccessTokenExpiringDate();
		$now          = new \DateTime( 'now',
			new \DateTimeZone( get_option( 'timezone_string' ) ? get_option( 'timezone_string' ) :
				'Europe/Amsterdam' ) );

		return $expring_date < $now;
	}

	public static function getAccessTokenExpiringDate() {
		$datetime = new \DateTime( '', new \DateTimeZone( 'UTC' ) );
		$datetime->setTimestamp( Plugin::cache_instance()
		                               ->get( self::transient_access_token_expires_at ) );

		return $datetime;
	}

	public static function post_link( $post_id, $link, $message ) {
		$access_token        = self::getAccessToken();
		$access_token_secret = self::getAccessTokenSecret();

		try {
			self::instance()
			    ->setOauthTokens( $access_token, $access_token_secret );

			$response = self::instance()
			                ->post( 'statuses/update', [ 'status' => $message . ' ' . $link ] );

			update_post_meta( $post_id, 'twitter_status_id', $response['id'] );

			return true;
		} catch ( TwitterException $e ) {
			error_log( $e->__toString(), 0 );
			update_post_meta( $post_id, 'twitter_publish_error', 'Twitter returned an error: ' . $e->getMessage() );

			return false;
		}
	}
}