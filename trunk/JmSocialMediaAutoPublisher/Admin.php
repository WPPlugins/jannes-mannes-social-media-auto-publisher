<?php
/**
 * Created by PhpStorm.
 * User: janhenkes
 * Date: 22/07/16
 * Time: 15:43
 */

namespace JmSocialMediaAutoPublisher;


class Admin {
	const settings_group = 'jm-smap-setting-group';
	const option = 'jm-smap';
	public static $options = null;

	public static function add_menu_page() {
		add_menu_page( _x( 'Auto Publisher', '', Plugin::$text_domain ), 'Auto Publisher', Plugin::capability, Plugin::$text_domain, [
			Admin::class,
			'auto_publisher_page'
		] );
		add_submenu_page( Plugin::$text_domain, _x( 'Connect accounts', '', Plugin::$text_domain ), 'Connect accounts', Plugin::capability, Plugin::$text_domain . '-connect-accounts', [
			Admin::class,
			'connect_accounts_page'
		] );
		add_action( 'admin_init', [ Admin::class, 'session_start' ] );
	}

	public static function session_start() {
		if ( is_admin() && isset( $_GET['page'] ) && in_array( $_GET['page'], [
					'jm-social-media-auto-publisher-connect-accounts',
					'jm-social-media-auto-publisher',
				] )
		) {
			if ( ! session_id() ) {
				session_start();
			}
		}
	}

	public static function register_settings() {
		//register our settings
		register_setting( self::settings_group, self::option, array( Admin::class, 'sanitize' ) );

		add_settings_section( 'section_media', // ID
			'Social Media accounts', // Title
			'', //array( Admin::class, 'print_section_info' ), // Callback
			self::settings_group // Page
		);

		add_settings_field( 'facebook_app_id', 'Facebook App ID', array(
			Admin::class,
			'facebook_app_id_callback'
		), self::settings_group, 'section_media' );

		add_settings_field( 'facebook_app_secret', 'Facebook App Secret', array(
			Admin::class,
			'facebook_app_secret_callback'
		), self::settings_group, 'section_media' );

		add_settings_field( 'yammer_client_id', 'Yammer client ID', array(
			Admin::class,
			'yammer_client_id_callback'
		), self::settings_group, 'section_media' );

		add_settings_field( 'yammer_client_secret', 'Yammer client secret', array(
			Admin::class,
			'yammer_client_secret_callback'
		), self::settings_group, 'section_media' );

		add_settings_field( 'twitter_api_key', 'Twitter API key', array(
			Admin::class,
			'twitter_api_key_callback'
		), self::settings_group, 'section_media' );

		add_settings_field( 'twitter_api_secret', 'Twitter API secret', array(
			Admin::class,
			'twitter_api_secret_callback'
		), self::settings_group, 'section_media' );

		add_settings_field( 'linkedin_client_id', 'LinkedIn Client ID', array(
			Admin::class,
			'linkedin_client_id_callback'
		), self::settings_group, 'section_media' );

		add_settings_field( 'linkedin_client_secret', 'LinkedIn Client Secret', array(
			Admin::class,
			'linkedin_client_secret_callback'
		), self::settings_group, 'section_media' );

		add_settings_field( 'linkedin_pages', 'LinkedIn pages', array(
			Admin::class,
			'linkedin_pages_callback'
		), self::settings_group, 'section_media' );

		add_settings_field( 'linkedin_account', 'LinkedIn account', array(
			Admin::class,
			'linkedin_account_callback'
		), self::settings_group, 'section_media' );

		add_settings_field( 'facebook_account', 'Facebook account', array(
			Admin::class,
			'facebook_account_callback'
		), self::settings_group, 'section_media' );

		add_settings_field( 'facebook_pages', 'Facebook pages', array(
			Admin::class,
			'facebook_pages_callback'
		), self::settings_group, 'section_media' );

		add_settings_field( 'twitter_account', 'Twitter account', array(
			Admin::class,
			'twitter_account_callback'
		), self::settings_group, 'section_media' );

		add_settings_field( 'yammer_network', 'Yammer network', array(
			Admin::class,
			'yammer_network_callback'
		), self::settings_group, 'section_media' );

		add_settings_field( 'post_types', 'Post types', array(
			Admin::class,
			'post_types_callback'
		), self::settings_group, 'section_media' );
	}

	public static function get_options() {
		if ( ! is_null( self::$options ) ) {
			return self::$options;
		}

		return self::$options = get_option( self::option );
	}

	public static function auto_publisher_page() {
		// Set class property
		self::get_options();
		?>
		<div class="wrap">
			<div id="col-container">
				<div class="" id="col-right">
					<div class="col-wrap">
						<?php self::print_section_info() ?>
					</div>
				</div>
				<div class="" id="col-left">
					<div class="col-wrap">
						<h1><?php _ex( 'Auto publisher', 'page title', Plugin::$text_domain ) ?></h1>
						<form method="post" action="options.php">
							<?php
							// This prints out all hidden setting fields
							settings_fields( self::settings_group );
							do_settings_sections( self::settings_group );
							submit_button();
							?>
						</form>
					</div>
				</div>
			</div>
		</div>
		<?php
	}


	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 *
	 * @return array
	 */
	public static function sanitize( $input ) {
		$new_input = array();

		$new_input['linkedin_pages'] = isset( $input['linkedin_pages'] ) ? $input['linkedin_pages'] : [];
		$new_input['facebook_pages'] = isset( $input['facebook_pages'] ) ? $input['facebook_pages'] : [];
		$new_input['post_types']     = isset( $input['post_types'] ) ? $input['post_types'] : [];

		if ( isset( $input['linkedin_account'] ) ) {
			$new_input['linkedin_account'] = $input['linkedin_account'];
		}

		if ( isset( $input['facebook_account'] ) ) {
			$new_input['facebook_account'] = $input['facebook_account'];
		}

		if ( isset( $input['twitter_account'] ) ) {
			$new_input['twitter_account'] = $input['twitter_account'];
		}

		if ( isset( $input['yammer_network'] ) ) {
			$new_input['yammer_network'] = $input['yammer_network'];
		}

		if ( isset( $input['facebook_app_id'] ) ) {
			$new_input['facebook_app_id'] = $input['facebook_app_id'];
		}

		if ( isset( $input['facebook_app_secret'] ) ) {
			$new_input['facebook_app_secret'] = $input['facebook_app_secret'];
		}

		if ( isset( $input['yammer_client_id'] ) ) {
			$new_input['yammer_client_id'] = $input['yammer_client_id'];
		}

		if ( isset( $input['yammer_client_secret'] ) ) {
			$new_input['yammer_client_secret'] = $input['yammer_client_secret'];
		}

		if ( isset( $input['twitter_api_key'] ) ) {
			$new_input['twitter_api_key'] = $input['twitter_api_key'];
		}

		if ( isset( $input['twitter_api_secret'] ) ) {
			$new_input['twitter_api_secret'] = $input['twitter_api_secret'];
		}

		if ( isset( $input['linkedin_client_id'] ) ) {
			$new_input['linkedin_client_id'] = $input['linkedin_client_id'];
		}

		if ( isset( $input['linkedin_client_secret'] ) ) {
			$new_input['linkedin_client_secret'] = $input['linkedin_client_secret'];
		}

		return $new_input;
	}

	/**
	 * Print the Section text
	 */
	public static function print_section_info() {
		?>
		<h2><?php _ex( 'How to', 'page title', Plugin::$text_domain ) ?></h2>
		<ol>
			<li>Facebook:
				<ol>
					<li>Create a <a href="https://developers.facebook.com/apps/" target="_blank">Facebook App</a> and
						copy your Facebook App ID and Facebook App Secret
					</li>
					<li>Paste your Facebook App ID and Facebook App Secret in the fields below</li>
				</ol>
			</li>
			<li>LinkedIn:
				<ol>
					<li>Create a <a href="https://www.linkedin.com/developer/apps" target="_blank">LinkedIn App</a> and
						copy your LinkedIn Client ID
						and LinkedIn Client Secret
					</li>
					<li>Paste the LinkedIn Client Id and LinkedIn
						Client Secret in the fields below
					</li>
				</ol>
			</li>
			<li>Twitter:
				<ol>
					<li>Create a <a href="https://apps.twitter.com/" target="_blank">Twitter App</a> and copy your
						Twitter API key
						and Twitter API Secret
					</li>
					<li>Paste the Twitter API key and Twitter API Secret in the fields below
					</li>
				</ol>
			</li>
			<li>Yammer:
				<ol>
					<li>Create a <a href="https://www.yammer.com/client_applications" target="_blank">Yammer App</a> and
						copy your Yammer Client ID
						and Yammer Client Secret
					</li>
					<li>Paste the Yammer Client Id and Yammer
						Client Secret in the fields below
					</li>
				</ol>
			</li>
			<li>Click "Connect" for all the social media you created an app for</li>
			<li>Check all the boxes for pages and accounts you wish your posts get published to</li>
			<li>Check all the post types you wish to be published</li>
		</ol>
		<?php
	}

	// LINKEDIN

	public static function linkedin_client_id_callback() {
		if ( ! isset( self::$options['linkedin_client_id'] ) ) {
			self::$options['linkedin_client_id'] = '';
		}
		printf( '<input type="text" name="%s[linkedin_client_id]" id="linkedin_client_id" value="%s" />', self::option, self::$options['linkedin_client_id'] );
	}

	public static function linkedin_client_secret_callback() {
		if ( ! isset( self::$options['linkedin_client_secret'] ) ) {
			self::$options['linkedin_client_secret'] = '';
		}
		printf( '<input type="text" name="%s[linkedin_client_secret]" id="linkedin_client_secret" value="%s" />', self::option, self::$options['linkedin_client_secret'] );
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public static function linkedin_pages_callback() {
		if ( ! isset( self::$options['linkedin_pages'] ) ) {
			self::$options['linkedin_pages'] = [];
		}
		$linkedin_pages = LinkedIn::get_pages();
		if ( ! empty( $linkedin_pages ) ) {
			foreach ( $linkedin_pages as $linkedin_page ) {
				?>
				<label><input type="checkbox" name="<?php echo self::option ?>[linkedin_pages][]"
						<?php echo in_array( $linkedin_page['id'], self::$options['linkedin_pages'] ) ?
							' checked="checked"' : '' ?>
							  value="<?php echo $linkedin_page['id'] ?>"/> <?php echo $linkedin_page['name'] ?></label>
				<br/>
				<?php
			}
		} else {
			LinkedIn::get_login_url();
		}
	}

	public static function linkedin_account_callback() {
		if ( LinkedIn::get_access_token() && ! LinkedIn::is_access_token_expired() ) {
			printf( '<label><input type="checkbox" name="%s[linkedin_account]" id="linkedin_account" %s value="1" /> %s</label>', self::option, isset( self::$options['linkedin_account'] ) ?
				' checked="checked"' :
				'', _x( 'Publish to your LinkedIn profile', 'Admin page for selecting accounts', Plugin::$text_domain ) );
		} else {
			LinkedIn::get_login_url();
		}
	}

	// FACEBOOK

	public static function facebook_app_id_callback() {
		if ( ! isset( self::$options['facebook_app_id'] ) ) {
			self::$options['facebook_app_id'] = '';
		}
		printf( '<input type="text" name="%s[facebook_app_id]" id="facebook_app_id" value="%s" />', self::option, self::$options['facebook_app_id'] );
	}

	public static function facebook_app_secret_callback() {
		if ( ! isset( self::$options['facebook_app_secret'] ) ) {
			self::$options['facebook_app_secret'] = '';
		}
		printf( '<input type="text" name="%s[facebook_app_secret]" id="facebook_app_secret" value="%s" />', self::option, self::$options['facebook_app_secret'] );
	}

	public static function facebook_pages_callback() {
		if ( ! isset( self::$options['facebook_pages'] ) ) {
			self::$options['facebook_pages'] = [];
		}
		$facebook_pages = Facebook::get_pages();
		if ( ! empty( $facebook_pages ) ) {
			foreach ( $facebook_pages as $facebook_page ) {
				?>
				<label><input type="checkbox" name="<?php echo self::option ?>[facebook_pages][]"
						<?php echo in_array( $facebook_page['id'], self::$options['facebook_pages'] ) ?
							' checked="checked"' : '' ?>
							  value="<?php echo $facebook_page['id'] ?>"/> <?php echo $facebook_page['name'] ?></label>
				<br/>
				<!--<small><?php
				/*				echo Facebook::get_access_token_by_id( $facebook_page['id'] )
								*/ ?></small><br />-->
				<?php
			}
		} else if ( ! Facebook::getAccessToken() || Facebook::isAccessTokenExpired() ) {
			echo Facebook::getLoginUrl();
		}
	}

	public static function facebook_account_callback() {
		if ( ! Facebook::getAccessToken() || Facebook::isAccessTokenExpired() ) {
			echo Facebook::getLoginUrl();
		} else {
			printf( '<label><input type="checkbox" name="%s[facebook_account]" id="facebook_account" %s value="1" /> %s</label>', self::option, isset( self::$options['facebook_account'] ) ?
				' checked="checked"' :
				'', _x( 'Publish to your Facebook profile', 'Admin page for selecting accounts', Plugin::$text_domain ) );
		}
	}

	// TWITTER

	public static function twitter_api_key_callback() {
		if ( ! isset( self::$options['twitter_api_key'] ) ) {
			self::$options['twitter_api_key'] = '';
		}
		printf( '<input type="text" name="%s[twitter_api_key]" id="twitter_api_key" value="%s" />', self::option, self::$options['twitter_api_key'] );
	}

	public static function twitter_api_secret_callback() {
		if ( ! isset( self::$options['twitter_api_secret'] ) ) {
			self::$options['twitter_api_secret'] = '';
		}
		printf( '<input type="text" name="%s[twitter_api_secret]" id="twitter_api_secret" value="%s" />', self::option, self::$options['twitter_api_secret'] );
	}

	public static function twitter_account_callback() {
		if ( ! Twitter::getAccessToken() || Twitter::isAccessTokenExpired() ) {
			echo '<a href="' . htmlspecialchars( Twitter::getLoginUrl() ) . '">Connect Twitter</a>';
		} else {
			printf( '<label><input type="checkbox" name="%s[twitter_account]" id="twitter_account" %s value="1" /> %s</label>', self::option, isset( self::$options['twitter_account'] ) ?
				' checked="checked"' :
				'', _x( 'Publish to your Twitter profile', 'Admin page for selecting accounts', Plugin::$text_domain ) );
		}
	}

	// YAMMER

	public static function yammer_client_id_callback() {
		if ( ! isset( self::$options['yammer_client_id'] ) ) {
			self::$options['yammer_client_id'] = '';
		}
		printf( '<input type="text" name="%s[yammer_client_id]" id="yammer_client_id" value="%s" />', self::option, self::$options['yammer_client_id'] );
	}

	public static function yammer_client_secret_callback() {
		if ( ! isset( self::$options['yammer_client_secret'] ) ) {
			self::$options['yammer_client_secret'] = '';
		}
		printf( '<input type="text" name="%s[yammer_client_secret]" id="yammer_client_secret" value="%s" />', self::option, self::$options['yammer_client_secret'] );
	}

	public static function yammer_network_callback() {
		if ( ! Yammer::getAccessToken() || Yammer::isAccessTokenExpired() ) {
			echo '<a href="' . htmlspecialchars( Yammer::getLoginUrl() ) . '">Connect Yammer</a>';
		} else {
			printf( '<label><input type="checkbox" name="%s[yammer_network]" id="yammer_network" %s value="1" /> %s</label>', self::option, isset( self::$options['yammer_network'] ) ?
				' checked="checked"' :
				'', _x( 'Publish to your Yammer network', 'Admin page for selecting accounts', Plugin::$text_domain ) );
		}
	}

	// POST TYPES

	public static function post_types_callback() {
		$post_types = get_post_types( [
			'public' => true,
		], 'objects' );

		if ( ! isset( self::$options['post_types'] ) ) {
			self::$options['post_types'] = [];
		}

		foreach ( $post_types as $post_type ) {
			?>
			<label><input type="checkbox" name="<?php echo self::option ?>[post_types][]"
					<?php echo in_array( $post_type->name, self::$options['post_types'] ) ? ' checked="checked"' : '' ?>
						  value="<?php echo $post_type->name ?>"/> <?php echo $post_type->labels->name ?></label><br/>
			<?php
		}
	}

	// CONNECT ACCOUNTS

	public static function connect_accounts_page() {

		if ( ! current_user_can( Plugin::capability ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		if ( isset( $_GET['facebook-callback'] ) ) {
			Facebook::saveAccessToken();
		}

		if ( isset( $_GET['yammer-callback'] ) ) {
			Yammer::saveAccessToken();
		}

		if ( isset( $_GET['twitter-callback'] ) ) {
			Twitter::saveAccessToken();
		}

		echo '<p>';
		if ( ! Facebook::getAccessToken() || Facebook::isAccessTokenExpired() ) {
			echo Facebook::getLoginUrl();
		} else {
			echo 'Facebook access token expires at ' . Facebook::getAccessTokenExpiringDate()->format( 'd-m-Y H:i:s' );
		}
		echo '</p>';

		echo '<p>';
		if ( LinkedIn::get_access_token() && ! LinkedIn::is_access_token_expired() ) {
			//we know that the user is authenticated now. Start query the API
			LinkedIn::instance()->setAccessToken( LinkedIn::get_access_token() );
			$user = LinkedIn::instance()->get( 'v1/people/~:(firstName,lastName)' );
			echo "Welcome " . $user['firstName'];
		} else {
			if ( LinkedIn::is_authenticated() ) {
				LinkedIn::save_access_token();
				$user = LinkedIn::instance()->get( 'v1/people/~:(firstName,lastName)' );
				echo "Welcome " . $user['firstName'];
			} else if ( LinkedIn::instance()->hasError() ) {
				echo "User canceled the login.";
			} else {
				LinkedIn::get_login_url();
			}
		}
		echo '</p>';

		echo '<p>';
		if ( ! Yammer::getAccessToken() || Yammer::isAccessTokenExpired() ) {
			echo '<a href="' . htmlspecialchars( Yammer::getLoginUrl() ) . '">Connect Yammer</a>';
		} else {
			echo 'Yammer access token expires at ' . Yammer::getAccessTokenExpiringDate()->format( 'd-m-Y H:i:s' );
		}
		echo '</p>';

		echo '<p>';
		if ( ! Twitter::getAccessToken() || Twitter::isAccessTokenExpired() ) {
			echo '<a href="' . htmlspecialchars( Twitter::getLoginUrl() ) . '">Connect Twitter</a>';
		} else {
			echo 'Twitter access token expires at ' . Twitter::getAccessTokenExpiringDate()->format( 'd-m-Y H:i:s' );
		}
		echo '</p>';
	}
}