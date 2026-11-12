<?php
/**
 * Email logs module.
 *
 * @package Welcome_Email_Editor
 */

namespace Weed\Logs;

defined( 'ABSPATH' ) || die( "Can't access directly" );

use Weed\Vars;

/**
 * Records every outgoing email when "Email Logging" is enabled.
 */
class Logs_Module {

	/**
	 * The global variable name for storing the current email's details.
	 *
	 * @var string
	 */
	const LOG_GLOBAL_VAR = 'weed_current_email_log';

	/**
	 * The daily cron event that deletes old logs.
	 *
	 * @var string
	 */
	const CLEANUP_HOOK = 'weed_delete_old_email_logs';

	/**
	 * Same as LOG_GLOBAL_VAR, kept for code that reads the property.
	 *
	 * @var string
	 */
	public $log_global_var = self::LOG_GLOBAL_VAR;

	/**
	 * The module url.
	 *
	 * @var string
	 */
	public $url;

	/**
	 * Module constructor.
	 */
	public function __construct() {

		$this->url = WEED_PLUGIN_URL . '/modules/logs';

	}

	/**
	 * Set up the module.
	 */
	public function setup() {

		$values = Vars::get( 'values' );

		add_action( self::CLEANUP_HOOK, array( $this, 'delete_old_logs' ) );
		add_action( 'init', array( $this, 'schedule_cleanup' ) );

		if ( empty( $values['enable_email_logging'] ) ) {
			return;
		}

		add_action( 'init', array( $this, 'register_email_logs_cpt' ), 20 );
		add_action( 'admin_menu', array( $this, 'email_logs_submenu' ), 20 );
		add_filter( 'wp_mail', array( $this, 'capture_email_details_for_logging' ) );
		add_action( 'phpmailer_init', array( $this, 'capture_email_sender' ) );
		add_action( 'wp_mail_succeeded', array( $this, 'handle_success_email' ) );
		add_action( 'wp_mail_failed', array( $this, 'handle_failed_email' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'email_logs_styles' ) );
		add_action( 'admin_init', array( $this, 'set_logs_capabilities' ) );

		require_once __DIR__ . '/class-logs-output.php';

		( new Logs_Output() )->setup();

	}

	/**
	 * Run the daily cleanup of old logs while logging is on and logs expire.
	 */
	public function schedule_cleanup() {

		$values    = Vars::get( 'values' );
		$needed    = ! empty( $values['enable_email_logging'] ) && ! empty( $values['email_logs_retention_days'] );
		$scheduled = wp_next_scheduled( self::CLEANUP_HOOK );

		if ( $needed && ! $scheduled ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_HOOK );
		} elseif ( ! $needed && $scheduled ) {
			wp_clear_scheduled_hook( self::CLEANUP_HOOK );
		}

	}

	/**
	 * Delete logs older than the "Keep Logs For" setting.
	 */
	public function delete_old_logs() {

		$values = Vars::get( 'values' );
		$days   = isset( $values['email_logs_retention_days'] ) ? absint( $values['email_logs_retention_days'] ) : 0;

		if ( ! $days ) {
			return;
		}

		// In batches, so a site with many old logs doesn't time out. The next run continues.
		for ( $batch = 0; $batch < 10; $batch++ ) {
			$log_ids = get_posts(
				array(
					'post_type'        => 'weed_email_logs',
					'post_status'      => 'any',
					'fields'           => 'ids',
					'numberposts'      => 100,
					'orderby'          => 'date',
					'order'            => 'ASC',
					'suppress_filters' => true,
					'date_query'       => array(
						array(
							'before' => $days . ' days ago',
						),
					),
				)
			);

			foreach ( $log_ids as $log_id ) {
				wp_delete_post( $log_id, true );
			}

			if ( count( $log_ids ) < 100 ) {
				break;
			}
		}

	}

	/**
	 * Register the post type that holds the email logs.
	 */
	public function register_email_logs_cpt() {

		$labels = array(
			'name'               => _x( 'Email Logs', 'post type general name', 'welcome-email-editor' ),
			'singular_name'      => _x( 'Email Log', 'post type singular name', 'welcome-email-editor' ),
			'menu_name'          => _x( 'Email Logs', 'admin menu', 'welcome-email-editor' ),
			'name_admin_bar'     => _x( 'Email Log', 'add new on admin bar', 'welcome-email-editor' ),
			'edit_item'          => __( 'Email Log', 'welcome-email-editor' ),
			'view_item'          => __( 'View Email Log', 'welcome-email-editor' ),
			'all_items'          => __( 'All Email Logs', 'welcome-email-editor' ),
			'search_items'       => __( 'Search Email Logs', 'welcome-email-editor' ),
			'not_found'          => __( 'No email logs found.', 'welcome-email-editor' ),
			'not_found_in_trash' => __( 'No email logs found in Trash.', 'welcome-email-editor' ),
		);

		$capabilities = array(
			'edit_post'          => 'edit_log',
			'read_post'          => 'read_log',
			'delete_post'        => 'delete_log',
			'edit_posts'         => 'edit_logs',
			'edit_others_posts'  => 'edit_others_logs',
			'delete_posts'       => 'delete_logs',
			'publish_posts'      => 'publish_logs',
			'read_private_posts' => 'read_private_logs',
			// Logs are only created by the plugin.
			'create_posts'       => 'do_not_allow',
		);

		register_post_type(
			'weed_email_logs',
			array(
				'labels'          => $labels,
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => false,
				'query_var'       => false,
				'rewrite'         => false,
				'capability_type' => array( 'log', 'logs' ),
				'capabilities'    => $capabilities,
				'map_meta_cap'    => false,
				'has_archive'     => false,
				'hierarchical'    => false,
				'supports'        => false,
			)
		);

	}

	/**
	 * Add "Email Logs" below the Swift SMTP menu.
	 */
	public function email_logs_submenu() {

		add_submenu_page(
			'weed_settings',
			__( 'Email Logs', 'welcome-email-editor' ),
			__( 'Email Logs', 'welcome-email-editor' ),
			'manage_options',
			'edit.php?post_type=weed_email_logs'
		);

	}

	/**
	 * Remember the email's details before it's sent.
	 *
	 * @param array $args The wp_mail() arguments.
	 *
	 * @return array
	 */
	public function capture_email_details_for_logging( $args ) {

		$GLOBALS[ $this->log_global_var ] = array(
			'subject'     => isset( $args['subject'] ) ? $args['subject'] : '',
			'email_body'  => isset( $args['message'] ) ? $args['message'] : '',
			'recipient'   => isset( $args['to'] ) ? ( is_array( $args['to'] ) ? implode( ', ', $args['to'] ) : $args['to'] ) : '',
			'headers'     => isset( $args['headers'] ) ? $args['headers'] : '',
			'attachments' => isset( $args['attachments'] ) ? $args['attachments'] : array(),
			'sender'      => '',
		);

		return $args;

	}

	/**
	 * Remember the sender.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer The PHPMailer instance.
	 */
	public function capture_email_sender( $phpmailer ) {

		if ( isset( $GLOBALS[ $this->log_global_var ] ) ) {
			$GLOBALS[ $this->log_global_var ]['sender'] = $phpmailer->From; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer property.
		}

	}

	/**
	 * Log a sent email.
	 */
	public function handle_success_email() {

		$this->log_email_event( 'Success', __( 'Email sent successfully.', 'welcome-email-editor' ) );

	}

	/**
	 * Log a failed email.
	 *
	 * @param \WP_Error $wp_error The error.
	 */
	public function handle_failed_email( $wp_error ) {

		$this->log_email_event( 'Failed', $wp_error->get_error_message() );

	}

	/**
	 * Save the current email as a log entry.
	 *
	 * @param string $status          "Success" or "Failed".
	 * @param string $server_response Server response.
	 */
	public function log_email_event( $status, $server_response = '' ) {

		if ( ! isset( $GLOBALS[ $this->log_global_var ] ) ) {
			return;
		}

		$email_log = $GLOBALS[ $this->log_global_var ];

		unset( $GLOBALS[ $this->log_global_var ] );

		// wp_insert_post() expects slashed data.
		wp_insert_post(
			wp_slash(
				array(
					'post_title'   => $email_log['subject'],
					'post_type'    => 'weed_email_logs',
					'post_status'  => 'publish',
					'post_content' => $email_log['email_body'],
					'meta_input'   => array(
						'subject'         => $email_log['subject'],
						'sender'          => $email_log['sender'],
						'recipient'       => $email_log['recipient'],
						'status'          => $status,
						'server_response' => $server_response,
					),
				)
			)
		);

	}

	/**
	 * Load the styles on the email logs screens.
	 */
	public function email_logs_styles() {

		$screen = get_current_screen();

		if ( $screen && 'weed_email_logs' === $screen->post_type ) {
			wp_enqueue_style( 'weed-email-logs', $this->url . '/assets/css/email-logs.css', array(), WEED_PLUGIN_VERSION );
		}

	}

	/**
	 * Give administrators access to the email logs.
	 */
	public function set_logs_capabilities() {

		$role = get_role( 'administrator' );

		if ( ! $role || $role->has_cap( 'read_private_logs' ) ) {
			return;
		}

		foreach ( array( 'edit_log', 'read_log', 'delete_log', 'edit_logs', 'edit_others_logs', 'delete_logs', 'publish_logs', 'read_private_logs' ) as $cap ) {
			$role->add_cap( $cap );
		}

	}

}
