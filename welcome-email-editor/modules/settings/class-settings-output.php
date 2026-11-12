<?php
/**
 * Settings module output.
 *
 * @package Welcome_Email_Editor
 */

namespace Weed\Settings;

use Weed\Helpers\Email_Helper;
use Weed\Vars;

defined( 'ABSPATH' ) || die( "Can't access directly" );

/**
 * Applies the "From" & content type settings and the custom reset password email.
 */
class Settings_Output {

	/**
	 * Set up the hooks.
	 */
	public function setup() {

		$this->set_email_headers();

		add_filter( 'retrieve_password_title', array( $this, 'retrieve_password_title' ), 10, 3 );
		add_filter( 'retrieve_password_message', array( $this, 'retrieve_password_message' ), 10, 4 );
		add_filter( 'wpmu_welcome_user_notification', array( $this, 'wpmu_new_user_notification' ), 10, 3 );

		/**
		 * Supporting https://s2member.com/ plugin.
		 * This support brought from the plugin's old code.
		 *
		 * @see https://www.s2member.com/codex/stable/s2member/email_configs/package-filters/#src_doc_ws_plugin__s2member_after_email_config_release
		 */
		add_action( 'ws_plugin__s2member_after_email_config_release', array( $this, 'set_email_from_headers' ) );

	}

	/**
	 * Retrieve password title.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/retrieve_password_title/
	 *
	 * @param string   $title Email subject.
	 * @param string   $user_login The username for the user.
	 * @param \WP_User $user_data WP_User object.
	 *
	 * @return string
	 */
	public function retrieve_password_title( $title, $user_login, $user_data ) {

		$values = Vars::get( 'values' );

		$saved_title = $values['reset_password_email_subject'];

		if ( ! $saved_title ) {
			return $title;
		}

		return ( new Email_Helper() )->replace_tags( $saved_title, $user_data );

	}

	/**
	 * Set email http headers.
	 */
	public function set_email_headers() {

		$values = Vars::get( 'values' );

		if ( $values['from_email'] ) {
			add_filter( 'wp_mail_from', array( $this, 'from_email' ) );
		}

		if ( $values['from_name'] ) {
			add_filter( 'wp_mail_from_name', array( $this, 'from_name' ) );
		}

		if ( 'html' === $values['content_type'] ) {
			add_filter( 'wp_mail_content_type', array( $this, 'html_content_type' ) );
			add_filter( 'wp_mail_charset', array( $this, 'charset' ) );
		}

	}

	/**
	 * Retrieve password message.
	 *
	 * @param string   $message Email message.
	 * @param string   $key The activation key.
	 * @param string   $user_login The user login.
	 * @param \WP_User $user_data WP_User object.
	 *
	 * @return string
	 */
	public function retrieve_password_message( $message, $key, $user_login, $user_data ) {

		$values = Vars::get( 'values' );

		$saved_message = $values['reset_password_email_body'];

		if ( ! $saved_message ) {
			return $message;
		}

		// Use add_query_arg() to build the reset URL.
		$reset_url = add_query_arg(
			array(
				'action' => 'rp',
				'key'    => $key,
				'login'  => rawurlencode( $user_login ),
			),
			wp_login_url()
		);

		return ( new Email_Helper() )->replace_tags(
			$saved_message,
			$user_data,
			array(
				'[site_url]'       => get_site_url(),
				'[reset_url]'      => $reset_url, // Deprecated, this is here for compatibility purpose.
				'[reset_pass_url]' => $reset_url,
			)
		);

	}

	/**
	 * Filters whether to bypass the welcome email after user activation.
	 *
	 * Returning false disables the welcome email.
	 *
	 * @param int    $user_id User ID.
	 * @param string $password User password.
	 * @param array  $meta Signup meta data. Default empty array.
	 */
	public function wpmu_new_user_notification( $user_id, $password, $meta = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Filter signature.

		// This function call doesn't return anything.
		wp_new_user_notification( $user_id, null, 'both' );

		// Since we already sent the email, we can return false to disable the default welcome email.
		return false;

	}

	/**
	 * Set email http headers but only the from_email and from_name.
	 */
	public function set_email_from_headers() {

		$values = Vars::get( 'values' );

		if ( $values['from_email'] ) {
			add_filter( 'wp_mail_from', array( $this, 'from_email' ) );
		}

		if ( $values['from_name'] ) {
			add_filter( 'wp_mail_from_name', array( $this, 'from_name' ) );
		}

	}

	/**
	 * Implement from to http header.
	 *
	 * @param string $from_email Email address to send from.
	 */
	public function from_email( $from_email ) {

		$values = Vars::get( 'values' );

		if ( ! $values['from_email'] ) {
			return $from_email;
		}

		// Return early if the feature is disabled via filter.
		if ( ! apply_filters( 'weed_use_from_email', true ) ) {
			return $from_email;
		}

		if ( ! $values['force_from_email'] ) {
			$default_from_email = ( new Email_Helper() )->get_default_wp_from_email();

			// When we don't force the "from email", we only set the value if it's not the default.
			if ( $from_email !== $default_from_email ) {
				return $from_email;
			}
		}

		$admin_email = get_option( 'admin_email' );

		$find = array(
			'[admin_email]',
		);

		$replace = array(
			$admin_email,
		);

		$custom_from_email = trim( str_ireplace( $find, $replace, $values['from_email'] ) );

		// A typo in the setting would make every email fail ("Invalid address"): keep the original sender then.
		return is_email( $custom_from_email ) ? $custom_from_email : $from_email;

	}

	/**
	 * Implement from to http header.
	 *
	 * @param string $from_name Name associated with the "from" email address.
	 */
	public function from_name( $from_name ) {

		$values = Vars::get( 'values' );

		if ( ! $values['from_name'] ) {
			return $from_name;
		}

		// Return early if the feature is disabled via filter.
		if ( ! apply_filters( 'weed_use_from_name', true ) ) {
			return $from_name;
		}

		if ( ! $values['force_from_name'] ) {
			// When we don't force the "from name", we only set the value if it's not the default.
			if ( 'WordPress' !== $from_name ) {
				return $from_name;
			}
		}

		$admin_email = get_option( 'admin_email' );

		$find = array(
			'[admin_email]',
		);

		$replace = array(
			$admin_email,
		);

		return str_ireplace( $find, $replace, $values['from_name'] );

	}

	/**
	 * Set content type header to text/html.
	 *
	 * @param string $content_type The current content type.
	 */
	public function html_content_type( $content_type ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Filter signature.

		return 'text/html';

	}

	/**
	 * Set charset.
	 *
	 * @param string $charset The current charset.
	 */
	public function charset( $charset ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Filter signature.

		return get_bloginfo( 'charset' );

	}

}
