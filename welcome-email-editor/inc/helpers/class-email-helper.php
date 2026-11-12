<?php
/**
 * Email helper.
 *
 * @package Welcome_Email_Editor
 */

namespace Weed\Helpers;

use Weed\Vars;

defined( 'ABSPATH' ) || die( "Can't access directly" );

/**
 * Email helpers.
 */
class Email_Helper {
	/**
	 * Get extra email headers.
	 *
	 * This will be used to set the headers parameter in `wp_mail()`.
	 *
	 * Included as extra headers:
	 * - Reply-To (name & email).
	 * - Additional / custom headers.
	 *
	 * @return array List of Header string.
	 */
	public function get_extra_headers() {

		$values = Vars::get( 'values' );

		/**
		 * The blogname option is escaped with esc_html() on the way into the database in sanitize_option().
		 * We want to reverse this for the plain text arena of emails.
		 *
		 * This comment was taken from wp-includes/pluggable.php inside `wp_new_user_notification` function.
		 */
		$blogname    = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$admin_email = get_option( 'admin_email' );

		$headers  = array();
		$reply_to = '';

		if ( ! empty( $values['user_welcome_email_reply_to_email'] ) ) {
			$reply_to = 'Reply-To:';

			// Only check for reply to name, if reply to email exists.
			if ( ! empty( $values['user_welcome_email_reply_to_name'] ) ) {
				$reply_to .= ' ' . $values['user_welcome_email_reply_to_name'];
			}

			$reply_to .= ' <' . $values['user_welcome_email_reply_to_email'] . '>';
		}

		if ( ! empty( $reply_to ) ) {
			$headers[] = $reply_to;
		}

		$custom_headers = $values['user_welcome_email_additional_headers'];
		$custom_headers = trim( $custom_headers );
		$custom_headers = str_ireplace( "\r\n", "\n", $custom_headers );
		$custom_headers = explode( "\n", $custom_headers );

		$placeholders = array(
			'[site_url]',
			'[blog_name]',
			'[admin_email]',
		);

		$replacements = array(
			network_site_url(),
			$blogname,
			$admin_email,
		);

		foreach ( $custom_headers as $custom_header ) {
			$custom_header = trim( $custom_header );

			if ( ! empty( $custom_header ) ) {
				$custom_header = str_ireplace( $placeholders, $replacements, $custom_header );
				$headers[]     = $custom_header;
			}
		}

		return $headers;

	}

	/**
	 * Get WordPress default wp_mail's "from email".
	 *
	 * @return string
	 */
	public function get_default_wp_from_email() {
		$sitename   = wp_parse_url( network_home_url(), PHP_URL_HOST );
		$from_email = 'wordpress@';

		if ( null !== $sitename ) {
			if ( 0 === strpos( $sitename, 'www.' ) ) {
				$sitename = substr( $sitename, 4 );
			}

			$from_email .= $sitename;
		}

		return $from_email;
	}

	/**
	 * Replace the template tags in an email subject or body.
	 *
	 * All tags of the "Template Tags" list work in every email.
	 *
	 * @param string   $content    The subject or body.
	 * @param \WP_User $user       The user the email is about.
	 * @param array    $extra_tags Tags of this email, e.g. its [reset_pass_url].
	 * @param bool     $admin_tags Whether the admin-only tags ([bp_custom_fields], [post_data]) are replaced.
	 *
	 * @return string
	 */
	public function replace_tags( $content, $user, $extra_tags = array(), $admin_tags = false ) {

		$content_helper = new Content_Helper();

		$tags = array_merge(
			array(
				// The blogname option is escaped with esc_html() on the way into the database.
				'[blog_name]'   => wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
				'[site_url]'    => network_site_url(),
				'[login_url]'   => wp_login_url(),
				'[admin_email]' => get_option( 'admin_email' ),
				'[first_name]'  => $user->first_name,
				'[last_name]'   => $user->last_name,
				'[user_email]'  => $user->user_email,
				'[user_login]'  => $user->user_login,
				'[user_id]'     => $user->ID,
				'[date]'        => date_i18n( get_option( 'date_format' ) ),
				'[time]'        => date_i18n( get_option( 'time_format' ) ),
				'[user_ip]'     => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			),
			$extra_tags
		);

		$content = $content_helper->replace_content( $tags, $content );
		$content = $content_helper->replace_conditional_placeholders( $content );

		// Data tags: only build the output when the tag is used.
		if ( false !== stripos( $content, '[custom_fields]' ) ) {
			$content = str_ireplace( '[custom_fields]', $content_helper->format_data_for_email( $content_helper->get_user_custom_fields( $user->ID ) ), $content );
		}

		if ( ! $admin_tags ) {
			return $content;
		}

		if ( false !== stripos( $content, '[bp_custom_fields]' ) ) {
			$content = str_ireplace( '[bp_custom_fields]', defined( 'BP_PLUGIN_URL' ) ? $content_helper->format_data_for_email( $content_helper->get_bp_user_custom_fields( $user->ID ) ) : '', $content );
		}

		if ( false !== stripos( $content, '[post_data]' ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only debugging output.
			$content = str_ireplace( '[post_data]', $content_helper->format_data_for_email( wp_unslash( $_REQUEST ) ), $content );
		}

		return $content;

	}
}
