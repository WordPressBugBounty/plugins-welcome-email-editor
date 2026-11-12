<?php
/**
 * SMTP module output.
 *
 * @package Welcome_Email_Editor
 */

namespace Weed\Smtp;

use Weed\Logs\Logs_Module;
use Weed\Mailjet_Api\Mailjet_Api_Sender;
use Weed\Vars;

defined( 'ABSPATH' ) || die( "Can't access directly" );

/**
 * Hooks into wp_mail() to send through SMTP or the Mailjet API.
 */
class Smtp_Output {

	/**
	 * Whether the Mailjet API failed for the current email (it then falls back to Mailjet's SMTP server).
	 *
	 * @var bool
	 */
	public $api_failed = false;

	/**
	 * Why the Mailjet API failed for the current email.
	 *
	 * @var string
	 */
	private $api_error = '';

	/**
	 * Set up the hooks.
	 */
	public function setup() {

		add_filter( 'pre_wp_mail', array( $this, 'maybe_use_mailjet_api' ), 10, 2 );
		add_action( 'phpmailer_init', array( $this, 'phpmailer_init' ), 9999 );
		add_action( 'wp_mail_failed', array( $this, 'add_api_error' ), 5 );
		add_action( 'wp_mail_failed', array( $this, 'wp_mail_failed' ), 10 );

	}

	/**
	 * Get the selected mailer: "smtp" or "mailjet_api".
	 *
	 * @return string
	 */
	private function mailer_type() {

		$values = Vars::get( 'values' );

		return ! empty( $values['mailer_type'] ) ? $values['mailer_type'] : 'smtp';

	}

	/**
	 * Send the email through the Mailjet API if it's the selected mailer.
	 *
	 * @param null|bool $short_circuit Short-circuit return value.
	 * @param array     $atts          The wp_mail() arguments.
	 *
	 * @return null|bool
	 */
	public function maybe_use_mailjet_api( $short_circuit, $atts ) {

		$this->api_failed = false;
		$this->api_error  = '';

		if ( null !== $short_circuit || 'mailjet_api' !== $this->mailer_type() ) {
			return $short_circuit;
		}

		require_once WEED_PLUGIN_DIR . '/modules/mailjet-api/class-mailjet-api-sender.php';

		$sender = Mailjet_Api_Sender::get_instance();

		$to          = isset( $atts['to'] ) ? $atts['to'] : '';
		$subject     = isset( $atts['subject'] ) ? $atts['subject'] : '';
		$message     = isset( $atts['message'] ) ? $atts['message'] : '';
		$headers     = isset( $atts['headers'] ) ? $atts['headers'] : '';
		$attachments = isset( $atts['attachments'] ) ? $atts['attachments'] : array();

		// The email log gets the sender from PHPMailer, which isn't used here.
		$from = $sender->get_from( $headers );
		$this->set_sender_for_logging( $from['Email'] );

		if ( $sender->send_email( $to, $subject, $message, $headers, $attachments ) ) {
			$this->trigger_mail_success_hook( $to, $subject, $message, $headers, $attachments );

			return true;
		}

		$values = Vars::get( 'values' );

		// Without keys there's no fallback either: fail with the API's error instead of sending through the server's mail().
		if ( empty( $values['mailjet_api_key'] ) || empty( $values['mailjet_secret_key'] ) ) {
			do_action( 'wp_mail_failed', new \WP_Error( 'wp_mail_failed', Vars::get( 'wp_mail_failed' ), $atts ) );

			return false;
		}

		// The API failed: let wp_mail() continue and send through Mailjet's SMTP server instead (see phpmailer_init()).
		$this->api_failed = true;
		$this->api_error  = (string) Vars::get( 'wp_mail_failed' );

		return null;

	}

	/**
	 * Configure PHPMailer to use SMTP.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $php_mailer The PHPMailer instance.
	 */
	public function phpmailer_init( $php_mailer ) {

		$values = Vars::get( 'values' );

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer properties.
		if ( 'mailjet_api' === $this->mailer_type() ) {
			// Mailjet's SMTP server is only the fallback when the API failed.
			if ( ! $this->api_failed || empty( $values['mailjet_api_key'] ) || empty( $values['mailjet_secret_key'] ) ) {
				return;
			}

			$php_mailer->isSMTP();

			$php_mailer->Host       = 'in-v3.mailjet.com';
			$php_mailer->Port       = 587;
			$php_mailer->SMTPSecure = 'tls';
			$php_mailer->SMTPAuth   = true;
			$php_mailer->Username   = $values['mailjet_api_key'];
			$php_mailer->Password   = $values['mailjet_secret_key'];

			return;
		}

		if ( empty( $values['smtp_host'] ) || empty( $values['smtp_port'] ) ) {
			return;
		}

		$php_mailer->isSMTP();

		$php_mailer->Host = $values['smtp_host'];
		$php_mailer->Port = $values['smtp_port'];

		// Servers without login (e.g. a local relay) don't need authentication.
		$php_mailer->SMTPAuth = ! empty( $values['smtp_username'] );

		if ( ! empty( $values['smtp_username'] ) ) {
			$php_mailer->Username = $values['smtp_username'];
			$php_mailer->Password = $values['smtp_password'];
		}

		if ( ! empty( $values['smtp_encryption'] ) ) {
			$php_mailer->SMTPSecure = 'ssl' === $values['smtp_encryption'] ? 'ssl' : 'tls';
		}
		// phpcs:enable

	}

	/**
	 * When the SMTP fallback fails too, put the API's error first: it's the one that explains the problem.
	 *
	 * Runs before the email log and the test email read the message.
	 *
	 * @param \WP_Error $wp_error The error.
	 */
	public function add_api_error( $wp_error ) {

		if ( ! $this->api_failed || ! $this->api_error || empty( $wp_error->errors['wp_mail_failed'][0] ) ) {
			return;
		}

		$wp_error->errors['wp_mail_failed'][0] = sprintf(
			/* translators: 1: The Mailjet API's error, 2: The SMTP error. */
			__( '%1$s The fallback through Mailjet\'s SMTP server failed too: %2$s', 'welcome-email-editor' ),
			rtrim( $this->api_error, '.' ) . '.',
			$wp_error->errors['wp_mail_failed'][0]
		);

		$this->api_error = '';

	}

	/**
	 * Remember the error of a failed email (shown after sending a test email).
	 *
	 * @param \WP_Error $wp_error The error.
	 */
	public function wp_mail_failed( $wp_error ) {

		Vars::set( 'wp_mail_failed', $wp_error->get_error_message() );

	}

	/**
	 * Set the sender of the email log.
	 *
	 * @param string $from_email The sender's email address.
	 */
	private function set_sender_for_logging( $from_email ) {

		// Only set when the logs module captured this email.
		if ( class_exists( Logs_Module::class ) && isset( $GLOBALS[ Logs_Module::LOG_GLOBAL_VAR ] ) ) {
			$GLOBALS[ Logs_Module::LOG_GLOBAL_VAR ]['sender'] = $from_email;
		}

	}

	/**
	 * Fire wp_mail_succeeded like wp_mail() does (used by the email logs and other plugins).
	 *
	 * @param string|string[] $to          Email recipients.
	 * @param string          $subject     Email subject.
	 * @param string          $message     Email message.
	 * @param string|string[] $headers     Email headers.
	 * @param string|string[] $attachments Email attachments.
	 */
	private function trigger_mail_success_hook( $to, $subject, $message, $headers, $attachments ) {

		$mail_data = array(
			'to'          => is_array( $to ) ? $to : array( $to ),
			'subject'     => $subject,
			'message'     => $message,
			'headers'     => is_array( $headers ) ? $headers : ( ! empty( $headers ) ? explode( "\n", str_replace( "\r\n", "\n", $headers ) ) : array() ),
			'attachments' => is_array( $attachments ) ? $attachments : array(),
		);

		do_action( 'wp_mail_succeeded', $mail_data );

	}

}
