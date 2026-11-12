<?php
/**
 * Mailjet API sender.
 *
 * @package Welcome_Email_Editor
 */

namespace Weed\Mailjet_Api;

use Weed\Helpers\Email_Helper;
use Weed\Vars;

defined( 'ABSPATH' ) || die( "Can't access directly" );

/**
 * Sends emails through the Mailjet API.
 */
class Mailjet_Api_Sender {

	/**
	 * The class instance.
	 *
	 * @var self
	 */
	public static $instance;

	/**
	 * Mailjet API endpoint.
	 *
	 * @var string
	 */
	const API_ENDPOINT = 'https://api.mailjet.com/v3.1/send';

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
	 * Send an email through the Mailjet API.
	 *
	 * @param string|array $to          Array or comma-separated list of email addresses.
	 * @param string       $subject     Email subject.
	 * @param string       $message     Message contents.
	 * @param string|array $headers     Optional. Additional headers.
	 * @param string|array $attachments Optional. Paths to files to attach.
	 *
	 * @return bool Whether the email was sent.
	 */
	public function send_email( $to, $subject, $message, $headers = '', $attachments = array() ) {

		$values = Vars::get( 'values' );

		if ( empty( $values['mailjet_api_key'] ) || empty( $values['mailjet_secret_key'] ) ) {
			$this->set_error( __( 'Mailjet API: the API key or secret key is missing.', 'welcome-email-editor' ) );
			return false;
		}

		$recipients = $this->parse_email_list( $to );

		if ( empty( $recipients ) ) {
			$this->set_error( __( 'Mailjet API: no valid recipient.', 'welcome-email-editor' ) );
			return false;
		}

		$parsed_headers = $this->prepare_headers( $headers );

		// Same as wp_mail(): the Content-Type header, then the wp_mail_content_type filter (which also applies the plugin's setting).
		$content_type = apply_filters( 'wp_mail_content_type', ! empty( $parsed_headers['content_type'] ) ? $parsed_headers['content_type'] : 'text/plain' );
		$content_type = false !== stripos( $content_type, 'text/html' ) ? 'html' : 'text';

		$email = array(
			'From'    => $this->get_from( $headers ),
			'To'      => $recipients,
			'Subject' => $subject,
		);

		if ( 'html' === $content_type ) {
			$email['HTMLPart'] = $message;
			$email['TextPart'] = wp_strip_all_tags( $message );
		} else {
			$email['TextPart'] = $message;
		}

		if ( ! empty( $parsed_headers['reply_to'] ) ) {
			$email['ReplyTo'] = $parsed_headers['reply_to'];
		}

		if ( ! empty( $parsed_headers['cc'] ) ) {
			$email['Cc'] = $parsed_headers['cc'];
		}

		if ( ! empty( $parsed_headers['bcc'] ) ) {
			$email['Bcc'] = $parsed_headers['bcc'];
		}

		if ( ! empty( $attachments ) ) {
			$processed_attachments = $this->prepare_attachments( $attachments, $message, $content_type );

			if ( ! empty( $processed_attachments['regular'] ) ) {
				$email['Attachments'] = $processed_attachments['regular'];
			}

			if ( ! empty( $processed_attachments['inline'] ) ) {
				$email['InlinedAttachments'] = $processed_attachments['inline'];
			}
		}

		$response = $this->make_api_request( array( 'Messages' => array( $email ) ), $values['mailjet_api_key'], $values['mailjet_secret_key'] );

		return $this->handle_api_response( $response );

	}

	/**
	 * Get the sender, the same way wp_mail() does: the From header or WordPress' default, then the wp_mail_from filters.
	 *
	 * The filters also apply the plugin's "From" settings.
	 *
	 * @param string|array $headers Email headers.
	 *
	 * @return array The sender: 'Email' and 'Name'.
	 */
	public function get_from( $headers = '' ) {

		$from_email = '';
		$from_name  = '';

		foreach ( $this->header_lines( $headers ) as $header ) {
			if ( 0 !== stripos( $header, 'From:' ) ) {
				continue;
			}

			$from_value = trim( substr( $header, 5 ) );

			if ( preg_match( '/(.*)<(.+)>/', $from_value, $matches ) ) {
				$from_name  = trim( trim( $matches[1] ), '"' );
				$from_email = trim( $matches[2] );
			} else {
				$from_email = $from_value;
			}
		}

		if ( empty( $from_email ) ) {
			$from_email = ( new Email_Helper() )->get_default_wp_from_email();
		}

		if ( empty( $from_name ) ) {
			$from_name = 'WordPress';
		}

		return array(
			'Email' => apply_filters( 'wp_mail_from', $from_email ),
			'Name'  => apply_filters( 'wp_mail_from_name', $from_name ),
		);

	}

	/**
	 * Split email headers into lines.
	 *
	 * @param string|array $headers Email headers.
	 *
	 * @return string[]
	 */
	private function header_lines( $headers ) {

		if ( empty( $headers ) ) {
			return array();
		}

		if ( ! is_array( $headers ) ) {
			$headers = explode( "\n", str_replace( "\r\n", "\n", (string) $headers ) );
		}

		return array_filter( array_map( 'trim', $headers ) );

	}

	/**
	 * Parse the Reply-To, Content-Type, Cc and Bcc headers.
	 *
	 * @param string|array $headers Email headers.
	 *
	 * @return array Parsed headers with 'reply_to', 'content_type', 'cc' and 'bcc' keys.
	 */
	private function prepare_headers( $headers ) {

		$parsed = array(
			'reply_to'     => null,
			'content_type' => null,
			'cc'           => array(),
			'bcc'          => array(),
		);

		foreach ( $this->header_lines( $headers ) as $header ) {
			if ( 0 === stripos( $header, 'Reply-To:' ) ) {
				$reply_to = $this->parse_email_list( trim( substr( $header, 9 ) ) );

				if ( ! empty( $reply_to ) ) {
					$parsed['reply_to'] = $reply_to[0];
				}
			} elseif ( 0 === stripos( $header, 'Content-Type:' ) ) {
				// Keep only the type, e.g. "text/html" of "text/html; charset=UTF-8".
				$parsed['content_type'] = trim( strtok( trim( substr( $header, 13 ) ), ';' ) );
			} elseif ( 0 === stripos( $header, 'Cc:' ) ) {
				$parsed['cc'] = array_merge( $parsed['cc'], $this->parse_email_list( trim( substr( $header, 3 ) ) ) );
			} elseif ( 0 === stripos( $header, 'Bcc:' ) ) {
				$parsed['bcc'] = array_merge( $parsed['bcc'], $this->parse_email_list( trim( substr( $header, 4 ) ) ) );
			}
		}

		return $parsed;

	}

	/**
	 * Parse a list of email addresses.
	 *
	 * Handles "email@example.com", "Name <email@example.com>", comma-separated lists and arrays.
	 *
	 * @param string|array $email_list Comma-separated string or array of email addresses.
	 *
	 * @return array Recipients with an 'Email' and optionally a 'Name' key.
	 */
	private function parse_email_list( $email_list = '' ) {

		$recipients = array();

		if ( empty( $email_list ) ) {
			return $recipients;
		}

		if ( is_string( $email_list ) ) {
			$email_list = explode( ',', $email_list );
		}

		if ( ! is_array( $email_list ) ) {
			return $recipients;
		}

		foreach ( $email_list as $email ) {
			if ( ! is_string( $email ) ) {
				continue;
			}

			$email = trim( $email );
			$name  = '';

			if ( preg_match( '/(.*)<(.+)>/', $email, $matches ) ) {
				$name  = trim( trim( $matches[1] ), '"' );
				$email = trim( $matches[2] );
			}

			if ( ! is_email( $email ) ) {
				continue;
			}

			$recipient = array( 'Email' => $email );

			if ( ! empty( $name ) ) {
				$recipient['Name'] = $name;
			}

			$recipients[] = $recipient;
		}

		return $recipients;

	}

	/**
	 * Prepare the attachments for the Mailjet API.
	 *
	 * @param string|array $attachments  File paths to attach.
	 * @param string       $message      Email message content.
	 * @param string       $content_type Content type (html or text).
	 *
	 * @return array Array with 'regular' and 'inline' attachments.
	 */
	private function prepare_attachments( $attachments, $message, $content_type ) {

		$prepared = array(
			'regular' => array(),
			'inline'  => array(),
		);

		if ( ! is_array( $attachments ) ) {
			$attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );
		}

		// Images referenced as "cid:" in an HTML email are sent inline.
		if ( 'html' === $content_type ) {
			$extracted          = $this->extract_inline_attachments( $message, $attachments );
			$prepared['inline'] = $extracted['inline'];
			$attachments        = $extracted['regular'];
		}

		$total_size     = 0;
		$max_total_size = 14 * 1024 * 1024; // 14 MB, a buffer below Mailjet's 15 MB limit.

		foreach ( $attachments as $attachment ) {
			$attachment = trim( $attachment );

			if ( empty( $attachment ) ) {
				continue;
			}

			if ( ! is_file( $attachment ) || ! is_readable( $attachment ) ) {
				$this->log_error( 'Mailjet API: Attachment file not found or not readable: ' . $attachment );
				continue;
			}

			$file_size = filesize( $attachment );

			if ( ( $total_size + $file_size ) > $max_total_size ) {
				$this->log_error( 'Mailjet API: Attachment size limit exceeded (14MB), skipping: ' . $attachment );
				continue;
			}

			$file_content = file_get_contents( $attachment );

			if ( false === $file_content ) {
				$this->log_error( 'Mailjet API: Failed to read attachment: ' . $attachment );
				continue;
			}

			$prepared['regular'][] = array(
				'ContentType'   => $this->get_mime_type( $attachment ),
				'Filename'      => basename( $attachment ),
				'Base64Content' => base64_encode( $file_content ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Required by the Mailjet API.
			);

			$total_size += $file_size;
		}

		return $prepared;

	}

	/**
	 * Split the attachments into inline ones (referenced as "cid:" in the HTML) and regular ones.
	 *
	 * @param string $html_message HTML message content.
	 * @param array  $attachments  Attachment file paths.
	 *
	 * @return array Array with 'inline' and 'regular' keys.
	 */
	private function extract_inline_attachments( $html_message, $attachments ) {

		$result = array(
			'inline'  => array(),
			'regular' => array(),
		);

		preg_match_all( '/src=["\']cid:([^"\']+)["\']/', $html_message, $matches );

		if ( empty( $matches[1] ) ) {
			$result['regular'] = $attachments;
			return $result;
		}

		foreach ( $attachments as $attachment ) {
			$attachment = trim( $attachment );

			if ( empty( $attachment ) || ! is_file( $attachment ) ) {
				continue;
			}

			$filename = basename( $attachment );
			$cid      = pathinfo( $filename, PATHINFO_FILENAME );

			// Match by file name with or without extension.
			if ( ! in_array( $cid, $matches[1], true ) && ! in_array( $filename, $matches[1], true ) ) {
				$result['regular'][] = $attachment;
				continue;
			}

			$file_content = file_get_contents( $attachment );

			if ( false === $file_content ) {
				$this->log_error( 'Mailjet API: Failed to read inline attachment: ' . $attachment );
				continue;
			}

			$result['inline'][] = array(
				'ContentType'   => $this->get_mime_type( $attachment ),
				'Filename'      => $filename,
				'ContentID'     => $cid,
				'Base64Content' => base64_encode( $file_content ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Required by the Mailjet API.
			);
		}

		return $result;

	}

	/**
	 * Get the MIME type of a file.
	 *
	 * @param string $file_path Path to the file.
	 *
	 * @return string
	 */
	private function get_mime_type( $file_path ) {

		$file_info = wp_check_filetype( $file_path );

		if ( ! empty( $file_info['type'] ) ) {
			return $file_info['type'];
		}

		if ( function_exists( 'mime_content_type' ) ) {
			$mime = mime_content_type( $file_path );

			if ( $mime ) {
				return $mime;
			}
		}

		return 'application/octet-stream';

	}

	/**
	 * Send the request to Mailjet.
	 *
	 * @param array  $payload    Request payload.
	 * @param string $api_key    Mailjet API key.
	 * @param string $secret_key Mailjet secret key.
	 *
	 * @return array|\WP_Error
	 */
	private function make_api_request( $payload, $api_key, $secret_key ) {

		return wp_remote_post(
			self::API_ENDPOINT,
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Basic ' . base64_encode( $api_key . ':' . $secret_key ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- HTTP basic auth.
				),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 30,
			)
		);

	}

	/**
	 * Check Mailjet's response.
	 *
	 * @param array|\WP_Error $response API response.
	 *
	 * @return bool Whether the email was sent.
	 */
	private function handle_api_response( $response ) {

		if ( is_wp_error( $response ) ) {
			$this->set_error( 'Mailjet API: ' . $response->get_error_message() );
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$body_data     = json_decode( $response_body, true );

		if ( 200 === $response_code && isset( $body_data['Messages'][0]['Status'] ) && 'success' === $body_data['Messages'][0]['Status'] ) {
			return true;
		}

		// Mailjet explains what's wrong in ErrorMessage.
		$error = isset( $body_data['Messages'][0]['Errors'][0]['ErrorMessage'] ) ? $body_data['Messages'][0]['Errors'][0]['ErrorMessage'] : '';
		$error = ! $error && isset( $body_data['ErrorMessage'] ) ? $body_data['ErrorMessage'] : $error;
		$error = $error ? $error : 'HTTP ' . $response_code;

		$this->set_error( 'Mailjet API: ' . $error );
		$this->log_error( 'Mailjet API response: ' . $response_body );

		return false;

	}

	/**
	 * Remember an error, so a test email can show it.
	 *
	 * @param string $message The error message.
	 */
	private function set_error( $message ) {

		Vars::set( 'wp_mail_failed', $message );
		$this->log_error( $message );

	}

	/**
	 * Write an error to the PHP error log.
	 *
	 * @param string $message The error message.
	 */
	private function log_error( $message ) {

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Helps when the email logs are off.
		error_log( $message );

	}

}
