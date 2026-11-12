<?php
/**
 * Plugin setup.
 *
 * @package Welcome_Email_Editor
 */

namespace Weed;

use Weed\Helpers\Content_Helper;

defined( 'ABSPATH' ) || die( "Can't access directly" );

/**
 * Sets up the plugin and loads its modules.
 */
class Setup {

	/**
	 * Init the plugin.
	 */
	public static function init() {

		$class = new self();

		add_action( 'plugins_loaded', array( $class, 'setup' ) );

	}

	/**
	 * On deactivation: stop the daily cleanup of old email logs.
	 */
	public static function deactivate() {

		wp_clear_scheduled_hook( 'weed_delete_old_email_logs' );

	}

	/**
	 * Set up the plugin.
	 */
	public function setup() {

		// Load the saved settings once at the start of each request.
		$this->set_initial_data();

		// Use settings saved during this request right away (first save: add_option, later: update_option).
		add_action( 'update_option_weed_settings', array( $this, 'handle_updated_option' ), 10, 2 );
		add_action( 'add_option_weed_settings', array( $this, 'handle_added_option' ), 10, 2 );
		add_action( 'init', array( $this, 'update_initial_data' ), 1 );
		add_filter( 'plugin_action_links_' . WEED_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );

		$this->load_modules();

	}

	/**
	 * Provide the settings to the modules before translations are loaded.
	 *
	 * The real defaults contain translated strings, so they are merged in on `init` (see update_initial_data()).
	 */
	private function set_initial_data() {

		$settings = get_option( 'weed_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();

		$defaults = array(
			// General settings.
			'from_email'                                   => '',
			'force_from_email'                             => false,
			'from_name'                                    => '',
			'force_from_name'                              => false,
			'content_type'                                 => 'text',
			'mailer_type'                                  => 'smtp',

			// Test email.
			'test_smtp_recipient_email'                    => '',

			// SMTP settings.
			'smtp_host'                                    => '',
			'smtp_port'                                    => 25,
			'smtp_encryption'                              => '',
			'smtp_username'                                => '',
			'smtp_password'                                => '',

			// Mailjet API settings.
			'mailjet_api_key'                              => '',
			'mailjet_secret_key'                           => '',

			// Email logs.
			'email_logs_retention_days'                    => 60,

			// Welcome email settings - for user.
			'user_welcome_email_subject'                   => '',
			'user_welcome_email_body'                      => '',
			'user_welcome_email_attachment_url'            => '',
			'user_welcome_email_reply_to_email'            => '',
			'user_welcome_email_reply_to_name'             => '',
			'user_welcome_email_additional_headers'        => '',

			// Welcome email settings - for admin.
			'admin_new_user_notif_email_subject'           => '',
			'admin_new_user_notif_email_body'              => '',
			'admin_new_user_notif_email_custom_recipients' => '',

			// Reset password email settings.
			'reset_password_email_subject'                 => '',
			'reset_password_email_body'                    => '',
		);

		Vars::set( 'values', wp_parse_args( $settings, $defaults ) );

	}

	/**
	 * Merge the real (translated) defaults into the settings.
	 */
	public function update_initial_data() {

		Vars::set( 'values', ( new Content_Helper() )->parse_settings() );

	}

	/**
	 * Refresh the settings after they were saved.
	 *
	 * @param mixed $old_value The old option value.
	 * @param mixed $value     The new option value.
	 */
	public function handle_updated_option( $old_value, $value ) {

		Vars::set( 'values', ( new Content_Helper() )->parse_settings( is_array( $value ) ? $value : array() ) );

	}

	/**
	 * Refresh the settings after they were saved for the first time.
	 *
	 * @param string $option The option name.
	 * @param mixed  $value  The option value.
	 */
	public function handle_added_option( $option, $value ) {

		$this->handle_updated_option( null, $value );

	}

	/**
	 * Load the modules.
	 */
	public function load_modules() {

		$modules = array(
			'Weed\\Settings\\Settings_Module'       => WEED_PLUGIN_DIR . '/modules/settings/class-settings-module.php',
			'Weed\\Smtp\\Smtp_Module'               => WEED_PLUGIN_DIR . '/modules/smtp/class-smtp-module.php',
			'Weed\\Mailjet_Api\\Mailjet_Api_Module' => WEED_PLUGIN_DIR . '/modules/mailjet-api/class-mailjet-api-module.php',
			'Weed\\Logs\\Logs_Module'               => WEED_PLUGIN_DIR . '/modules/logs/class-logs-module.php',
		);

		$modules = apply_filters( 'weed_modules', $modules );

		foreach ( $modules as $class => $file ) {
			// The folder name of the module, e.g. "mailjet-api" gives the filter "weed_mailjet_api".
			$module_name = basename( dirname( $file ) );
			$filter_name = 'weed_' . str_ireplace( '-', '_', $module_name );

			// Allows preventing a module from loading.
			if ( ! apply_filters( $filter_name, true ) ) {
				continue;
			}

			require_once $file;

			$module = new $class();
			$module->setup();
		}

	}

	/**
	 * Add the "Settings" link on the plugins page.
	 *
	 * @param array $links The action links.
	 *
	 * @return array
	 */
	public function plugin_action_links( $links ) {

		$settings = array( '<a href="' . esc_url( admin_url( 'admin.php?page=weed_settings' ) ) . '">' . esc_html__( 'Settings', 'welcome-email-editor' ) . '</a>' );

		return array_merge( $settings, $links );

	}

	/**
	 * Add heatbox's body classes on the settings page.
	 *
	 * @param string $classes The body classes.
	 *
	 * @return string
	 */
	public function admin_body_class( $classes ) {

		$screen = get_current_screen();

		if ( ! $screen || 'toplevel_page_weed_settings' !== $screen->id ) {
			return $classes;
		}

		return $classes . ' heatbox-admin has-header';

	}
}
