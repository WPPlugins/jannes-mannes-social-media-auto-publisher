<?php
/**
 * Created by PhpStorm.
 * User: janhenkes
 * Date: 22/07/16
 * Time: 16:00
 */

namespace JmSocialMediaAutoPublisher;


use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

class Facebook {
	private static $facebook_instance = null;

	public static $transient_access_token_expires_at = 'fb_access_token_expires_at_v1';
	public static $transient_access_token = 'fb_access_token_v1';

	private static $accounts = null;

	public static function notify_access_token_expired_event() {
		$to = get_option( 'admin_email' );
		wp_mail( $to, 'Facebook access token verlopen', "Dear {$to}, your Facebook access token is expired. Please log in to your site and refresh the access token. " . get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=jm-social-media-auto-publisher' );
	}

	public static function facebookInstance() {
		if ( ! is_null( self::$facebook_instance ) ) {
			return self::$facebook_instance;
		}

		return self::$facebook_instance = new \Facebook\Facebook( [
			'app_id'                => Admin::get_options()['facebook_app_id'],
			'app_secret'            => Admin::get_options()['facebook_app_secret'],
			'default_graph_version' => 'v2.2',
		] );
	}

	public static function getLoginUrl() {
		$helper = self::facebookInstance()->getRedirectLoginHelper();

		$permissions = [ 'email', 'publish_actions', 'manage_pages', 'publish_pages' ]; // Optional permissions
		$loginUrl    = $helper->getLoginUrl( Plugin::redirect_url() . '&facebook-callback', $permissions );

		return '<a href="' . htmlspecialchars( $loginUrl ) . '">Connect Facebook</a>';
	}

	public static function saveAccessToken() {
		$helper = self::facebookInstance()->getRedirectLoginHelper();

		try {
			$accessToken = $helper->getAccessToken();

			$oAuth2Client = self::facebookInstance()->getOAuth2Client();

			// Get the access token metadata from /debug_token
			$tokenMetadata = $oAuth2Client->debugToken( $accessToken );
			$expires_at    = $tokenMetadata->getExpiresAt();
			$expires_at    = ! $expires_at instanceof \DateTime ? ( new \DateTime() )->modify( '+60 days' ) :
				$expires_at;
			Plugin::cache_instance()
			      ->set( self::$transient_access_token_expires_at, $expires_at->getTimestamp(), $expires_at->getTimestamp() - time() );

			$schedule = wp_next_scheduled( 'jm_smap_facebook_notify_access_token_expired_event' );
			wp_unschedule_event( $schedule, 'jm_smap_facebook_notify_access_token_expired_event' );
			wp_schedule_single_event( $expires_at->getTimestamp(), 'jm_smap_facebook_notify_access_token_expired_event' );

			// Validation (these will throw FacebookSDKException's when they fail)
			$tokenMetadata->validateAppId( Admin::get_options()['facebook_app_id'] ); // Replace {app-id} with your app id
			// If you know the user ID this access token belongs to, you can validate it here
			//$tokenMetadata->validateUserId('123');
			$tokenMetadata->validateExpiration();

			if ( ! $accessToken->isLongLived() ) {
				// Exchanges a short-lived access token for a long-lived one
				$accessToken = $oAuth2Client->getLongLivedAccessToken( $accessToken );
			}

			Plugin::cache_instance()
			      ->set( self::$transient_access_token, (string) $accessToken, $expires_at->getTimestamp() - time() );

			//wp_redirect( admin_url( 'admin.php?page=' . Plugin::$text_domain . '-connect-accounts' ) );
		} catch ( \Facebook\Exceptions\FacebookResponseException $e ) {
			// When Graph returns an error
			echo 'Graph returned an error: ' . $e->getMessage();
			exit;
		} catch ( \Facebook\Exceptions\FacebookSDKException $e ) {
			// When validation fails or other local issues
			echo 'Facebook SDK returned an error: ' . $e->getMessage();
			exit;
		}

		if ( ! isset( $accessToken ) ) {
			if ( $helper->getError() ) {
				header( 'HTTP/1.0 401 Unauthorized' );
				echo "Error: " . $helper->getError() . "\n";
				echo "Error Code: " . $helper->getErrorCode() . "\n";
				echo "Error Reason: " . $helper->getErrorReason() . "\n";
				echo "Error Description: " . $helper->getErrorDescription() . "\n";
			} else {
				header( 'HTTP/1.0 400 Bad Request' );
				echo 'Bad request';
			}
			exit;
		}
	}

	public static function getAccessToken() {
		return Plugin::cache_instance()->get( self::$transient_access_token );
	}


	public static function isAccessTokenExpired() {
		if ( ! Plugin::cache_instance()->get( self::$transient_access_token_expires_at ) ) {
			return true;
		}
		$expring_date = self::getAccessTokenExpiringDate();
		$now          = new \DateTime( 'now', new \DateTimeZone( get_option( 'timezone_string' ) ?
			get_option( 'timezone_string' ) : 'Europe/Amsterdam' ) );

		return $expring_date < $now;
	}

	public static function getAccessTokenExpiringDate() {
		$datetime = new \DateTime( '', new \DateTimeZone( 'UTC' ) );
		$datetime->setTimestamp( Plugin::cache_instance()->get( self::$transient_access_token_expires_at ) );

		return $datetime;
	}

	public static function get_pages() {
		try {
			$response = self::facebookInstance()->get( '/me/accounts', self::getAccessToken() );

			return $response->getDecodedBody()['data'];
		} catch ( FacebookResponseException $e ) {
			error_log( $e->__toString(), 0 );

			return [];
		} catch ( FacebookSDKException $e ) {
			error_log( $e->__toString(), 0 );

			return [];
		}
	}

	public static function get_access_token_by_id( $id ) {
		$access_token = null;
		if ( is_null( self::$accounts ) ) {
			$accounts_req   = Facebook::facebookInstance()->get( '/me/accounts', Facebook::getAccessToken() );
			self::$accounts = $accounts_req->getDecodedBody()['data'];
		}
		$accounts = array_filter( self::$accounts, function ( $a ) use ( $id ) {
			return $a['id'] == $id;
		} );
		if ( $accounts ) {
			$account      = array_shift( $accounts );
			$access_token = $account['access_token'];
		}

		return $access_token;
	}

	public static function post_link( $post_id, $link, $message, $fb_user_or_page_id = 'me' ) {
		$fb = self::facebookInstance();

		$access_token = self::getAccessToken();

		if ( $fb_user_or_page_id != 'me' ) {
			$access_token = self::get_access_token_by_id( $fb_user_or_page_id ) ?: $access_token;
		}

		$linkData = [
			'link'    => $link,
			'message' => $message,
		];

		if ( JM_SMAP_DEBUG ) {
			$linkData['privacy'] = [ 'value' => 'SELF' ];
		}

		try {
			// Returns a `Facebook\FacebookResponse` object
			$response = $fb->post( '/' . $fb_user_or_page_id . '/feed', $linkData, $access_token );
		} catch ( FacebookResponseException $e ) {
			error_log( $e->__toString(), 0 );
			update_post_meta( $post_id, 'facebook_publish_error', 'Graph returned an error: ' . $e->getMessage() );

			return false;
		} catch ( FacebookSDKException $e ) {
			error_log( $e->__toString(), 0 );
			update_post_meta( $post_id, 'facebook_publish_error', 'Facebook SDK returned an error: ' . $e->getMessage() );

			return false;
		}

		$graphNode = $response->getGraphNode();

		update_post_meta( $post_id, 'graph_node_id', $graphNode['id'] );

		return true;
	}
}