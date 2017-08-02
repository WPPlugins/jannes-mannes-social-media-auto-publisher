<?php
/**
 * Created by PhpStorm.
 * User: janhenkes
 * Date: 01/10/16
 * Time: 22:21
 */

namespace JmSocialMediaAutoPublisher;


class Publisher {
	const event_publish = 'jm_smap_publish';
	const medium_facebook = 'facebook';
	const medium_linkedin = 'linkedin';
	const medium_twitter = 'twitter';
	const medium_yammer = 'yammer';
	const type_user = 'user';
	const type_page = 'page';

	public static function unschedule_publishes( $post_id, $options, $force_replubish = true ) {
		if ( isset( $options['facebook_account'] ) ) {
			wp_clear_scheduled_hook( self::event_publish, [
				$post_id,
				self::medium_facebook,
				'me'
			] );
		}

		foreach ( $options['facebook_pages'] as $facebook_page ) {
			wp_clear_scheduled_hook( self::event_publish, [
				$post_id,
				self::medium_facebook,
				$facebook_page
			] );
		}

		if ( isset( $options['linkedin_account'] ) ) {
			wp_clear_scheduled_hook( self::event_publish, [
				$post_id,
				self::medium_linkedin,
				'',
				self::type_user
			] );
		}

		foreach ( $options['linkedin_pages'] as $linkedin_page ) {
			wp_clear_scheduled_hook( self::event_publish, [
				$post_id,
				self::medium_linkedin,
				$linkedin_page,
				self::type_page
			] );
		}

		if ( isset( $options['twitter_account'] ) ) {
			wp_clear_scheduled_hook( self::event_publish, [
				$post_id,
				self::medium_twitter,
			] );
		}

		if ( isset( $options['yammer_network'] ) ) {
			wp_clear_scheduled_hook( self::event_publish, [
				$post_id,
				self::medium_yammer,
			] );
		}

		if ( $force_replubish ) {
			update_post_meta( $post_id, 'jm_smap_published', 0 );
		}
	}

	public static function schedule_publishes( $post_id, $post, $update ) {
		$options = Admin::get_options();

		/**
		 * ! $update => $update is true als er een nieuwe post wordt aangemaakt, voordat je hem gepubliceerd hebt
		 * wp_is_post_revision( $post_id ) => post mag geen revision zijn
		 * ! in_array( $post->post_type, $options['post_types'] ) => post type moet in de admin aangevinkt zijn
		 * $post->post_status != 'publish' => de post moet wel gepubliceerd zijn
		 */
		if ( ! $update || wp_is_post_revision( $post_id ) || ! in_array( $post->post_type, $options['post_types'] ) || ! in_array( $post->post_status, [
				'publish',
				'future'
			] ) || strlen( $post->post_password )
		) {
			/**
			 *
			 */
			if ( in_array( $post->post_status, [ 'draft', 'pending', 'private' ] ) || strlen( $post->post_password ) ) {
				self::unschedule_publishes( $post_id, $options, true );
			}

			return;
		}

		$published = isset( $_POST['jm_smap_published'] ) && $_POST['jm_smap_published'] == "1";

		if ( ! $published ) {
			self::unschedule_publishes( $post_id, $options );

			$time = JM_SMAP_DEBUG ? time() + 60 * 5 : time() + 60 * 5;

			if ( $post->post_status == 'future' ) {
				$time = new \DateTime( $post->post_date, new \DateTimeZone( get_option( 'timezone_string' ) ) );
				$time = $time->getTimestamp() + 60 * 5;
			}

			if ( isset( $options['facebook_account'] ) ) {
				$args          = [
					$post_id,
					self::medium_facebook,
					'me'
				];
				$next_schedule = wp_next_scheduled( self::event_publish, $args );
				if ( ! $next_schedule ) {
					wp_schedule_single_event( $time, self::event_publish, $args );
				}
			}

			foreach ( $options['facebook_pages'] as $facebook_page ) {
				$args          = [
					$post_id,
					self::medium_facebook,
					$facebook_page
				];
				$next_schedule = wp_next_scheduled( self::event_publish, $args );
				if ( ! $next_schedule ) {
					wp_schedule_single_event( $time, self::event_publish, $args );
				}
			}

			if ( isset( $options['linkedin_account'] ) ) {
				$args          = [
					$post_id,
					self::medium_linkedin,
					'',
					self::type_user
				];
				$next_schedule = wp_next_scheduled( self::event_publish, $args );
				if ( ! $next_schedule ) {
					wp_schedule_single_event( $time, self::event_publish, $args );
				}
			}

			foreach ( $options['linkedin_pages'] as $linkedin_page ) {
				$args          = [
					$post_id,
					self::medium_linkedin,
					$linkedin_page,
					self::type_page
				];
				$next_schedule = wp_next_scheduled( self::event_publish, $args );
				if ( ! $next_schedule ) {
					wp_schedule_single_event( $time, self::event_publish, $args );
				}
			}

			if ( isset( $options['twitter_account'] ) ) {
				$args          = [
					$post_id,
					self::medium_twitter,
				];
				$next_schedule = wp_next_scheduled( self::event_publish, $args );
				if ( ! $next_schedule ) {
					wp_schedule_single_event( $time, self::event_publish, $args );
				}
			}

			if ( isset( $options['yammer_network'] ) ) {
				$args          = [
					$post_id,
					self::medium_yammer,
				];
				$next_schedule = wp_next_scheduled( self::event_publish, $args );
				if ( ! $next_schedule ) {
					wp_schedule_single_event( $time, self::event_publish, $args );
				}
			}

			update_post_meta( $post_id, 'jm_smap_published', 1 );
		}
	}

	public static function publish( $post_id, $medium, $medium_id = '', $type = self::type_user ) {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$message          = get_the_title( $post );
		$title            = get_the_title( $post );
		$url              = get_permalink( $post );
		$image            = get_the_post_thumbnail_url( $post, 'large' );
		$link_description = get_the_excerpt( $post );

		if ( $_yoast_wpseo_opengraph_title = get_post_meta( $post_id, '_yoast_wpseo_opengraph-title', true ) ) {
			// if og title is set use it
			$title   = $_yoast_wpseo_opengraph_title;
			$message = $_yoast_wpseo_opengraph_title;
		} else if ( $_yoast_wpseo_title = get_post_meta( $post_id, '_yoast_wpseo_title', true ) ) {
			// otherwise use the seo title if set
			$title = $_yoast_wpseo_title;
		}

		if ( $_yoast_wpseo_opengraph_description = get_post_meta( $post_id, '_yoast_wpseo_opengraph-description', true ) ) {
			// if og description is set use it
			$link_description = $_yoast_wpseo_opengraph_description;
		} else if ( $_yoast_wpseo_metadesc = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true ) ) {
			// otherwise use the seo meta desc if set
			$link_description = $_yoast_wpseo_metadesc;
		}

		if ( $_yoast_wpseo_opengraph_image = get_post_meta( $post_id, '_yoast_wpseo_opengraph-image', true ) ) {
			// if og image is set use it
			$image = $_yoast_wpseo_opengraph_image;
		}

		switch ( $medium ) {
			case self::medium_facebook:
				Facebook::post_link( $post_id, $url, $message, $medium_id );
				break;
			case self::medium_linkedin:
				switch ( $type ) {
					default:
					case self::type_user:
						LinkedIn::post_link( $post_id, $url, $message, $title, $link_description, $image );
						break;
					case self::type_page:
						LinkedIn::post_page_link( $post_id, $url, $message, $medium_id, $title, $link_description, $image );
						break;
				}
				break;
			case self::medium_twitter:
				Twitter::post_link( $post_id, $url, $message );
				break;
			case self::medium_yammer:
				Yammer::post_link( $post_id, $url, $message );
				break;
		}
	}
}