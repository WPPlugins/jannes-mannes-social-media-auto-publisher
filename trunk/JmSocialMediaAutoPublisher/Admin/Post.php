<?php

namespace JmSocialMediaAutoPublisher\Admin;

use JmSocialMediaAutoPublisher\Admin;
use JmSocialMediaAutoPublisher\Plugin;

class Post {
	public static function init() {
		add_action( 'load-post.php', [ get_called_class(), 'post_meta_boxes_setup' ] );
		add_action( 'load-post-new.php', [ get_called_class(), 'post_meta_boxes_setup' ] );
	}

	public static function post_meta_boxes_setup() {
		add_meta_box( 'jmsmap-post-settings', esc_html__( 'Social media publisher', Plugin::$text_domain ), [
			get_called_class(),
			'meta_box'
		], Admin::get_options()['post_types'], 'side', 'default' );
		add_action( 'save_post', [ get_called_class(), 'save_metabox' ], 10, 2 );
	}

	public static function meta_box( \WP_Post $object, $box ) {
		// Add nonce for security and authentication.
		wp_nonce_field( 'jmsmap-post-settings', 'jmsmap_nonce' );
		?>
		<p>
			<label>
				<input class="widefat" type="checkbox" value="1" name="jm_smap_published" id="jm_smap_published" <?php echo get_post_meta( $object->ID, 'jm_smap_published', true ) == '1' ?
					'checked="checked"' : ''; ?>/>
				<?php _e( 'Post is published to social media', Plugin::$text_domain ) ?>
			</label><br />
			<em><?php _e( 'Un-check this box to reschedule the publication of this post to social media', Plugin::$text_domain ) ?></em>
		</p>
		<?php
	}

	/**
	 * Handles saving the meta box.
	 *
	 * @param int $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 *
	 * @return null
	 */
	public function save_metabox( $post_id, $post ) {
		// Add nonce for security and authentication.
		$nonce_name   = isset( $_POST['jmsmap_nonce'] ) ? $_POST['jmsmap_nonce'] : '';
		$nonce_action = 'jmsmap-post-settings';

		// Check if nonce is set.
		if ( ! isset( $nonce_name ) ) {
			return;
		}

		// Check if nonce is valid.
		if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) ) {
			return;
		}

		// Check if user has permissions to save data.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check if not an autosave.
		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Check if not a revision.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Do not set it to 1 here, that happens earlier when saving the post
		/*update_post_meta( $post_id, 'jm_smap_published', isset( $_POST['jm_smap_published'] ) && $_POST['jm_smap_published'] == "1" ?
			1 : 0 );*/
	}
}