<?php
/**
 * Replace the wp_new_user_notification pluggable function.
 *
 * @package Welcome_Email_Editor
 */

use Weed\Vars;
use Weed\Helpers\Email_Helper;

defined( 'ABSPATH' ) || die( "Can't access directly" );

if ( ! function_exists( 'wp_new_user_notification' ) ) {

	/**
	 * Email login details to a new user and a notification to the admin.
	 *
	 * Based on wp_new_user_notification() in wp-includes/pluggable.php.
	 *
	 * @see https://developer.wordpress.org/reference/functions/wp_new_user_notification/
	 *
	 * @param int    $user_id    User ID.
	 * @param null   $deprecated Not used (argument deprecated).
	 * @param string $notify     Optional. Who gets notified: 'admin' or an empty string (admin only),
	 *                           'user', or 'both' (admin and user). Default empty.
	 */
	function wp_new_user_notification( $user_id, $deprecated = null, $notify = '' ) {

		if ( null !== $deprecated ) {
			_deprecated_argument( __FUNCTION__, '4.3.1' );
		}

		if ( ! in_array( $notify, array( 'user', 'admin', 'both', '' ), true ) ) {
			return;
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return;
		}

		$values       = Vars::get( 'values' );
		$email_helper = new Email_Helper();
		$headers      = $email_helper->get_extra_headers();

		// The blogname option is escaped with esc_html() on the way into the database in sanitize_option().
		// We want to reverse this for the plain text arena of emails.
		$blogname    = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$admin_email = get_option( 'admin_email' );

		if ( 'user' !== $notify ) {
			$switched_locale = switch_to_locale( get_locale() );

			$admin_tags = array(
				'[reset_pass_url]'     => wp_login_url(),
				'[plaintext_password]' => '*****',
				'[user_password]'      => '*****',
			);

			// translators: New user registration notification email subject. %s: Site title.
			$default_admin_subject = __( '[%s] New User Registration', 'welcome-email-editor' );

			$admin_subject = $values['admin_new_user_notif_email_subject'];
			$admin_subject = $admin_subject ? $admin_subject : $default_admin_subject;
			$admin_subject = $email_helper->replace_tags( $admin_subject, $user, $admin_tags, true );
			$admin_subject = apply_filters( 'weed_user_welcome_email_subject', $admin_subject );

			/* translators: %s: Site title. */
			$default_admin_body = sprintf( __( 'New user registration on your site %s:', 'welcome-email-editor' ), $blogname ) . "\r\n\r\n";
			/* translators: %s: User login. */
			$default_admin_body .= sprintf( __( 'Username: %s', 'welcome-email-editor' ), $user->user_login ) . "\r\n\r\n";
			/* translators: %s: User email address. */
			$default_admin_body .= sprintf( __( 'Email: %s', 'welcome-email-editor' ), $user->user_email ) . "\r\n";

			$admin_body = $values['admin_new_user_notif_email_body'];
			$admin_body = $admin_body ? $admin_body : $default_admin_body;
			$admin_body = $email_helper->replace_tags( $admin_body, $user, $admin_tags, true );
			$admin_body = apply_filters( 'weed_user_welcome_email_body', $admin_body );

			$wp_new_user_notification_email_admin = array(
				'to'      => $admin_email,
				'subject' => $admin_subject,
				'message' => $admin_body,
				'headers' => ! empty( $headers ) ? $headers : '',
			);

			/**
			 * Filters the contents of the new user notification email sent to the site admin.
			 *
			 * @param array   $wp_new_user_notification_email_admin The email: to, subject, message, headers.
			 * @param WP_User $user                                 User object for new user.
			 * @param string  $blogname                             The site title.
			 */
			$wp_new_user_notification_email_admin = apply_filters( 'wp_new_user_notification_email_admin', $wp_new_user_notification_email_admin, $user, $blogname );

			$admin_subject = wp_specialchars_decode( str_replace( '%s', $blogname, $wp_new_user_notification_email_admin['subject'] ) );

			// Test emails go to the current user only.
			$testing_recipient = apply_filters( 'weed_test_email_recipient', '' );

			wp_mail(
				( ! empty( $testing_recipient ) ? $testing_recipient : $wp_new_user_notification_email_admin['to'] ),
				$admin_subject,
				$wp_new_user_notification_email_admin['message'],
				$wp_new_user_notification_email_admin['headers']
			);

			if ( empty( $testing_recipient ) ) {
				foreach ( weed_get_custom_recipient_emails( $values['admin_new_user_notif_email_custom_recipients'], $admin_email ) as $custom_recipient_email ) {
					wp_mail(
						$custom_recipient_email,
						$admin_subject,
						$wp_new_user_notification_email_admin['message'],
						$wp_new_user_notification_email_admin['headers']
					);
				}
			}

			if ( $switched_locale ) {
				restore_previous_locale();
			}
		}

		// `$deprecated` was pre-4.3 `$plaintext_pass`. An empty `$plaintext_pass` didn't send a user notification.
		if ( 'admin' === $notify || ( empty( $deprecated ) && empty( $notify ) ) ) {
			return;
		}

		$key = get_password_reset_key( $user );

		if ( is_wp_error( $key ) ) {
			return;
		}

		$switched_locale = switch_to_locale( get_user_locale( $user ) );

		$reset_pass_url = add_query_arg(
			array(
				'action' => 'rp',
				'key'    => $key,
				'login'  => rawurlencode( $user->user_login ),
			),
			wp_login_url()
		);

		$user_tags = array(
			'[reset_pass_url]'     => $reset_pass_url,
			'[reset_pass_link]'    => '<a href="' . esc_url( $reset_pass_url ) . '" target="_blank">' . esc_html__( 'Click to set', 'welcome-email-editor' ) . '</a>',
			'[plaintext_password]' => '*****',
			'[user_password]'      => '*****',
		);

		/* translators: %s: Site title */
		$default_user_subject = __( '[%s] Login Details', 'welcome-email-editor' );

		$user_subject = $values['user_welcome_email_subject'];
		$user_subject = $user_subject ? $user_subject : $default_user_subject;
		$user_subject = $email_helper->replace_tags( $user_subject, $user, $user_tags );
		$user_subject = apply_filters( 'weed_user_welcome_email_subject', $user_subject );

		/* translators: %s: User login. */
		$default_user_body  = sprintf( __( 'Username: %s', 'welcome-email-editor' ), $user->user_login ) . "\r\n\r\n";
		$default_user_body .= __( 'To set your password, visit the following address:', 'welcome-email-editor' ) . "\r\n\r\n";
		$default_user_body .= $reset_pass_url . "\r\n\r\n";
		$default_user_body .= wp_login_url() . "\r\n";

		$user_body = $values['user_welcome_email_body'];
		$user_body = $user_body ? $user_body : $default_user_body;
		$user_body = $email_helper->replace_tags( $user_body, $user, $user_tags );

		$wp_new_user_notification_email = array(
			'to'      => $user->user_email,
			'subject' => $user_subject,
			'message' => $user_body,
			'headers' => ! empty( $headers ) ? $headers : '',
		);

		/**
		 * Filters the contents of the new user notification email sent to the new user.
		 *
		 * @param array   $wp_new_user_notification_email The email: to, subject, message, headers.
		 * @param WP_User $user                           User object for new user.
		 * @param string  $blogname                       The site title.
		 */
		$wp_new_user_notification_email = apply_filters( 'wp_new_user_notification_email', $wp_new_user_notification_email, $user, $blogname );

		wp_mail(
			$wp_new_user_notification_email['to'],
			wp_specialchars_decode( str_replace( '%s', $blogname, $wp_new_user_notification_email['subject'] ) ),
			$wp_new_user_notification_email['message'],
			$wp_new_user_notification_email['headers'],
			weed_get_attachment_path( $values['user_welcome_email_attachment_url'] )
		);

		if ( $switched_locale ) {
			restore_previous_locale();
		}
	}

}

/**
 * Get the email addresses of the "Additional Recipients" (a comma-separated list of user IDs).
 *
 * @param string $custom_recipients Comma-separated user IDs.
 * @param string $admin_email       The admin email, left out so it doesn't get the email twice.
 *
 * @return string[]
 */
function weed_get_custom_recipient_emails( $custom_recipients, $admin_email ) {

	$emails = array();

	foreach ( explode( ',', (string) $custom_recipients ) as $custom_recipient ) {
		$custom_recipient = trim( $custom_recipient );

		if ( ! is_numeric( $custom_recipient ) ) {
			continue;
		}

		$recipient_user = get_userdata( absint( $custom_recipient ) );

		if ( $recipient_user && $recipient_user->user_email !== $admin_email && ! in_array( $recipient_user->user_email, $emails, true ) ) {
			$emails[] = $recipient_user->user_email;
		}
	}

	return $emails;

}

/**
 * Turn the attachment URL of the welcome email into a file path.
 *
 * @param string $attachment_url The attachment URL.
 *
 * @return string The file path, or an empty string if the file doesn't exist on this site.
 */
function weed_get_attachment_path( $attachment_url ) {

	$attachment_url = trim( (string) $attachment_url );

	if ( empty( $attachment_url ) ) {
		return '';
	}

	// Ignore the protocol, so http and https URLs both work.
	$url_path = preg_replace( '#^https?:#i', '', $attachment_url );

	$locations = array(
		preg_replace( '#^https?:#i', '', content_url() ) => WP_CONTENT_DIR,
		preg_replace( '#^https?:#i', '', site_url() )    => untrailingslashit( ABSPATH ),
	);

	foreach ( $locations as $base_url => $base_dir ) {
		$base_url = trailingslashit( $base_url );

		if ( 0 !== stripos( $url_path, $base_url ) ) {
			continue;
		}

		$file = realpath( $base_dir . '/' . rawurldecode( substr( $url_path, strlen( $base_url ) ) ) );
		$root = realpath( $base_dir );

		// Only attach existing files inside the site's folder.
		if ( $file && $root && 0 === strpos( $file, $root . DIRECTORY_SEPARATOR ) && is_file( $file ) ) {
			return $file;
		}
	}

	return '';

}
