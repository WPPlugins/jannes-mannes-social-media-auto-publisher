<?php
/**
 * Created by PhpStorm.
 * User: janhenkes
 * Date: 20/01/17
 * Time: 17:31
 */

namespace JmSocialMediaAutoPublisher\Tests;

use JmSocialMediaAutoPublisher\Yammer;

require dirname( __FILE__ ) . '/../../../../../wp-load.php';

class PostYammerMessageTest extends \PHPUnit_Framework_TestCase {
	public function test() {
		$post_id = wp_insert_post( [
			'post_title'   => 'Test post Yammer',
			'post_status'  => 'publish',
			'post_content' => 'Test post Yammer content',
		] );

		if ( is_wp_error( $post_id ) ) {
			throw new \Exception( $post_id->get_error_message() );
		}

		Yammer::post_link( $post_id, get_permalink( $post_id ), get_the_title( $post_id ) );

		wp_delete_post( $post_id );
	}

}
