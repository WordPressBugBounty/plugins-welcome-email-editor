<?php
/**
 * Test emails.
 *
 * @package Welcome_Email_Editor
 */

namespace Weed\Settings\Ajax;

use Weed\Mailjet_Api\Mailjet_Api_Sender;
use Weed\Vars;

defined( 'ABSPATH' ) || die( "Can't access directly" );

/**
 * Sends the test emails from the settings page.
 */
class Test_Emails {

	/**
	 * The class instance.
	 *
	 * @var self
	 */
	public static $instance;

	/**
	 * Email types and their nonce actions.
	 *
	 * @var array
	 */
	private $email_types = array(
		'admin_new_user_notif_email' => '_Admin_Welcome_Email',
		'user_welcome_email'         => '_User_Welcome_Email',
		'reset_password_email'       => '_Reset_Password_Email',
		'test_smtp_email'            => '_Test_SMTP_Email',
		'test_mailjet_api_email'     => '_Test_Mailjet_API_Email',
	);

	/**
	 * Get the instance of the class.
	 *
	 * @return self
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * The ajax handler.
	 */
	public function ajax_handler() {

		$email_type = isset( $_POST['email_type'] ) ? sanitize_key( wp_unslash( $_POST['email_type'] ) ) : '';

		if ( ! current_user_can( apply_filters( 'weed_settings_capability', 'manage_options' ) ) ) {
			wp_send_json_error( __( 'You are not allowed to do this.', 'welcome-email-editor' ), 403 );
		}

		if ( ! isset( $this->email_types[ $email_type ] ) ) {
			wp_send_json_error( __( 'Unknown email type.', 'welcome-email-editor' ), 400 );
		}

		check_ajax_referer( WEED_PLUGIN_DIR . $this->email_types[ $email_type ], 'nonce' );

		// Errors of earlier emails in this request don't count.
		Vars::set( 'wp_mail_failed', '' );

		// The welcome & reset password emails go to the current user only.
		add_filter( 'weed_test_email_recipient', array( $this, 'set_testing_recipient' ) );

		switch ( $email_type ) {
			case 'admin_new_user_notif_email':
				wp_new_user_notification( get_current_user_id(), null, 'admin' );
				$this->send_result( true );
				break;

			case 'user_welcome_email':
				wp_new_user_notification( get_current_user_id(), null, 'user' );
				$this->send_result( true );
				break;

			case 'reset_password_email':
				$this->send_result( retrieve_password( wp_get_current_user()->user_login ) );
				break;

			case 'test_smtp_email':
				$this->test_smtp_email();
				break;

			case 'test_mailjet_api_email':
				$this->test_mailjet_api_email();
				break;
		}

	}

	/**
	 * Send the result of a test email back to the settings page.
	 *
	 * @param bool|\WP_Error $result Whether the email was sent.
	 */
	private function send_result( $result ) {

		$error_msg = Vars::get( 'wp_mail_failed' );

		if ( is_wp_error( $result ) ) {
			$error_msg = $result->get_error_message();
		}

		if ( $error_msg || ! $result ) {
			wp_send_json_error( $error_msg ? $error_msg : __( 'The email could not be sent.', 'welcome-email-editor' ) );
		}

		wp_send_json_success( __( 'Email has been sent successfully.', 'welcome-email-editor' ) );

	}

	/**
	 * The recipient of the SMTP / Mailjet test email.
	 *
	 * @return string
	 */
	private function test_recipient() {

		$recipient = isset( $_POST['to_email'] ) ? sanitize_email( wp_unslash( $_POST['to_email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked in ajax_handler().

		if ( ! is_email( $recipient ) ) {
			wp_send_json_error( __( 'Please enter a valid email address.', 'welcome-email-editor' ) );
		}

		return $recipient;

	}

	/**
	 * The body of the SMTP / Mailjet test email.
	 *
	 * @return string
	 */
	private function test_body() {

		$values = Vars::get( 'values' );
		$suffix = isset( $values['content_type'] ) && 'html' === $values['content_type'] ? '-html' : '';

		ob_start();
		require WEED_PLUGIN_DIR . '/modules/settings/templates/emails/smtp-test' . $suffix . '-email.php';

		return ob_get_clean();

	}

	/**
	 * Send the SMTP test email.
	 */
	public function test_smtp_email() {

		$recipient = $this->test_recipient();

		$this->send_result( wp_mail( $recipient, __( 'Swift SMTP: Test Email', 'welcome-email-editor' ), $this->test_body() ) );

	}

	/**
	 * Send the Mailjet API test email (straight through the API, without the SMTP fallback).
	 */
	public function test_mailjet_api_email() {

		$recipient = $this->test_recipient();

		require_once WEED_PLUGIN_DIR . '/modules/mailjet-api/class-mailjet-api-sender.php';

		$result = Mailjet_Api_Sender::get_instance()->send_email( $recipient, __( 'Swift SMTP: Mailjet API Test Email', 'welcome-email-editor' ), $this->test_body() );

		$this->send_result( $result );

	}

	/**
	 * The current user receives the test emails.
	 *
	 * @return string
	 */
	public function set_testing_recipient() {

		return wp_get_current_user()->user_email;

	}

}
