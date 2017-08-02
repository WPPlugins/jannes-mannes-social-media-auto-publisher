<?php
/**
 * Created by PhpStorm.
 * User: janhenkes
 * Date: 16/01/17
 * Time: 21:49
 */

namespace JmSocialMediaAutoPublisher\Tests;

use JmSocialMediaAutoPublisher\Twitter;

require dirname( __FILE__ ) . '/../../../../../wp-load.php';

class PostTweetTest extends \PHPUnit_Framework_TestCase {
	public function test() {
		$post_id = wp_insert_post( [
			'post_title'   => 'Test post',
			'post_status'  => 'publish',
			'post_content' => 'Test post content',
		] );

		if ( is_wp_error( $post_id ) ) {
			throw new \Exception( $post_id->get_error_message() );
		}

		Twitter::post_link( $post_id, get_permalink( $post_id ), get_the_title( $post_id ) );

		wp_delete_post( $post_id );
	}
}
