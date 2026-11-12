<?php
/**
 * Settings module.
 *
 * @package Welcome_Email_Editor
 */

namespace Weed\Settings;

defined( 'ABSPATH' ) || die( "Can't access directly" );

/**
 * The settings page and the welcome & reset password emails.
 */
class Settings_Module {

	/**
	 * The settings page's screen ID.
	 *
	 * @var string
	 */
	const SCREEN_ID = 'toplevel_page_weed_settings';

	/**
	 * Shown (as dots) in the password fields instead of a saved password, which never goes into the page.
	 *
	 * @var string
	 */
	const SAVED_SECRET = '__weed_saved__';

	/**
	 * Set up the module.
	 */
	public function setup() {

		add_action( 'init', array( $this, 'set_plugin_priority' ) );
		add_action( 'admin_menu', array( $this, 'menu_page' ), 20 );
		add_action( 'admin_init', array( $this, 'add_settings' ) );
		add_action( 'load-' . self::SCREEN_ID, array( $this, 'reset_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_filter( 'option_page_capability_weed-settings-group', array( $this, 'capability' ) );

		require_once __DIR__ . '/class-settings-output.php';
		( new Settings_Output() )->setup();

		require_once __DIR__ . '/ajax/class-test-emails.php';
		add_action( 'wp_ajax_weed_test_emails', array( Ajax\Test_Emails::get_instance(), 'ajax_handler' ) );

	}

	/**
	 * The capability needed to manage the settings.
	 *
	 * @return string
	 */
	public function capability() {

		return apply_filters( 'weed_settings_capability', 'manage_options' );

	}

	/**
	 * Load the plugin first, so its wp_new_user_notification() replaces WordPress' one before other plugins load.
	 */
	public function set_plugin_priority() {

		$active_plugins = get_option( 'active_plugins' );

		if ( ! is_array( $active_plugins ) ) {
			return;
		}

		$current_index = array_search( WEED_PLUGIN_BASENAME, $active_plugins, true );

		// Not in the list (e.g. network activated) or already first.
		if ( ! $current_index ) {
			return;
		}

		array_splice( $active_plugins, $current_index, 1 );
		array_unshift( $active_plugins, WEED_PLUGIN_BASENAME );
		update_option( 'active_plugins', $active_plugins );

	}

	/**
	 * Add the menu page.
	 */
	public function menu_page() {

		add_menu_page(
			__( 'Swift SMTP', 'welcome-email-editor' ),
			__( 'Swift SMTP', 'welcome-email-editor' ),
			$this->capability(),
			'weed_settings',
			array( $this, 'page_content' ),
			'dashicons-email',
			60
		);

	}

	/**
	 * The settings page.
	 */
	public function page_content() {

		require __DIR__ . '/templates/settings-page.php';

	}

	/**
	 * Load the settings page's styles & scripts.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function admin_assets( $hook_suffix ) {

		if ( self::SCREEN_ID !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'heatbox', WEED_PLUGIN_URL . '/assets/css/heatbox.css', array(), WEED_PLUGIN_VERSION );
		wp_enqueue_style( 'weed-settings', WEED_PLUGIN_URL . '/assets/css/settings.css', array( 'heatbox' ), WEED_PLUGIN_VERSION );
		wp_enqueue_script( 'weed-settings', WEED_PLUGIN_URL . '/assets/js/settings.js', array( 'jquery' ), WEED_PLUGIN_VERSION, true );

		wp_localize_script(
			'weed-settings',
			'weedSettings',
			array(
				'nonces' => array(
					'adminWelcomeEmail'   => wp_create_nonce( WEED_PLUGIN_DIR . '_Admin_Welcome_Email' ),
					'userWelcomeEmail'    => wp_create_nonce( WEED_PLUGIN_DIR . '_User_Welcome_Email' ),
					'resetPasswordEmail'  => wp_create_nonce( WEED_PLUGIN_DIR . '_Reset_Password_Email' ),
					'testSmtpEmail'       => wp_create_nonce( WEED_PLUGIN_DIR . '_Test_SMTP_Email' ),
					'testMailjetApiEmail' => wp_create_nonce( WEED_PLUGIN_DIR . '_Test_Mailjet_API_Email' ),
				),
				'i18n'   => array(
					'resetSettings' => __( 'Reset all Swift SMTP settings? This can\'t be undone.', 'welcome-email-editor' ),
					'error'         => __( 'Something went wrong. Please try again.', 'welcome-email-editor' ),
					'saveError'     => __( 'Your settings could not be saved, so the test email wasn\'t sent. Please reload the page and try again.', 'welcome-email-editor' ),
				),
			)
		);

	}

	/**
	 * Delete all settings when "Reset Settings" was clicked.
	 */
	public function reset_settings() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Checked below.
		if ( ! isset( $_GET['action'] ) || 'weed_reset_settings' !== $_GET['action'] ) {
			return;
		}

		if ( ! current_user_can( $this->capability() ) || ! check_admin_referer( 'weed_reset_settings' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'welcome-email-editor' ), 403 );
		}

		delete_option( 'weed_settings' );

		wp_safe_redirect( admin_url( 'admin.php?page=weed_settings&weed-reset=1' ) );
		exit;

	}

	/**
	 * Register the setting.
	 */
	public function add_settings() {

		register_setting(
			'weed-settings-group',
			'weed_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

	}

	/**
	 * Sanitize the settings.
	 *
	 * @param array $input The submitted values.
	 *
	 * @return array
	 */
	public function sanitize_settings( $input ) {

		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();

		// Single-line text fields.
		$text_fields = array(
			'from_email',
			'from_name',
			'smtp_host',
			'user_welcome_email_subject',
			'user_welcome_email_reply_to_email',
			'user_welcome_email_reply_to_name',
			'admin_new_user_notif_email_subject',
			'admin_new_user_notif_email_custom_recipients',
			'reset_password_email_subject',
		);

		foreach ( $text_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$sanitized[ $field ] = sanitize_text_field( $input[ $field ] );
			}
		}

		// Logins & keys: kept as typed (sanitize_text_field() would break passwords like "a%20b" or "<pass>"), minus line breaks.
		foreach ( array( 'smtp_username', 'smtp_password', 'mailjet_api_key', 'mailjet_secret_key' ) as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$sanitized[ $field ] = trim( preg_replace( '/[\r\n\t\0]/', '', (string) $input[ $field ] ) );
			}
		}

		// Password fields left unchanged keep the saved password.
		$saved = get_option( 'weed_settings', array() );

		foreach ( array( 'smtp_password', 'mailjet_secret_key' ) as $field ) {
			if ( isset( $sanitized[ $field ] ) && self::SAVED_SECRET === $sanitized[ $field ] ) {
				$sanitized[ $field ] = is_array( $saved ) && isset( $saved[ $field ] ) ? $saved[ $field ] : '';
			}
		}

		// Email bodies.
		foreach ( array( 'user_welcome_email_body', 'admin_new_user_notif_email_body', 'reset_password_email_body' ) as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$sanitized[ $field ] = wp_kses_post( $input[ $field ] );
			}
		}

		// Toggles.
		foreach ( array( 'force_from_email', 'force_from_name', 'enable_email_logging', 'remove_on_uninstall' ) as $field ) {
			if ( ! empty( $input[ $field ] ) ) {
				$sanitized[ $field ] = 1;
			}
		}

		if ( isset( $input['content_type'] ) ) {
			$sanitized['content_type'] = 'html' === $input['content_type'] ? 'html' : 'text';
		}

		if ( isset( $input['mailer_type'] ) ) {
			$sanitized['mailer_type'] = 'mailjet_api' === $input['mailer_type'] ? 'mailjet_api' : 'smtp';
		}

		if ( isset( $input['smtp_encryption'] ) ) {
			$sanitized['smtp_encryption'] = in_array( $input['smtp_encryption'], array( 'ssl', 'tls' ), true ) ? $input['smtp_encryption'] : '';
		}

		if ( isset( $input['email_logs_retention_days'] ) ) {
			// A negative number means "forever" (absint() would turn -5 into 5 days).
			$sanitized['email_logs_retention_days'] = min( max( 0, intval( $input['email_logs_retention_days'] ) ), 3650 );
		}

		if ( isset( $input['smtp_port'] ) ) {
			$sanitized['smtp_port'] = absint( $input['smtp_port'] );
		}

		if ( isset( $input['test_smtp_recipient_email'] ) ) {
			$sanitized['test_smtp_recipient_email'] = sanitize_email( $input['test_smtp_recipient_email'] );
		}

		if ( isset( $input['user_welcome_email_attachment_url'] ) ) {
			$sanitized['user_welcome_email_attachment_url'] = esc_url_raw( $input['user_welcome_email_attachment_url'] );
		}

		if ( isset( $input['user_welcome_email_additional_headers'] ) ) {
			$sanitized['user_welcome_email_additional_headers'] = sanitize_textarea_field( $input['user_welcome_email_additional_headers'] );
		}

		return $sanitized;

	}

}
