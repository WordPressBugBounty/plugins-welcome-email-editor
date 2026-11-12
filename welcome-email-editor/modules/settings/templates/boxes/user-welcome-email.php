<?php
/**
 * Welcome email (for users) box.
 *
 * @package Welcome_Email_Editor
 */

defined( 'ABSPATH' ) || die( "Can't access directly" );
?>

<div class="heatbox">
	<h2><?php esc_html_e( 'Welcome Email (for Users)', 'welcome-email-editor' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">
				<label for="weed_settings--user_welcome_email_subject"><?php esc_html_e( 'Email Subject', 'welcome-email-editor' ); ?></label>
			</th>
			<td>
				<input type="text" name="weed_settings[user_welcome_email_subject]" id="weed_settings--user_welcome_email_subject" class="large-text" value="<?php echo esc_attr( $weed_values['user_welcome_email_subject'] ); ?>" placeholder="<?php echo esc_attr( $weed_defaults['user_welcome_email_subject'] ); ?>">
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="weed_settings--user_welcome_email_body"><?php esc_html_e( 'Email Body', 'welcome-email-editor' ); ?></label>
			</th>
			<td>
				<textarea name="weed_settings[user_welcome_email_body]" id="weed_settings--user_welcome_email_body" class="large-text" rows="8" placeholder="<?php echo esc_attr( $weed_defaults['user_welcome_email_body'] ); ?>"><?php echo esc_textarea( $weed_values['user_welcome_email_body'] ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="weed_settings--user_welcome_email_attachment_url"><?php esc_html_e( 'Email Attachment URL', 'welcome-email-editor' ); ?></label>
				<p class="description"><?php esc_html_e( 'The URL of a file on this site, e.g. from the media library.', 'welcome-email-editor' ); ?></p>
			</th>
			<td>
				<input type="url" name="weed_settings[user_welcome_email_attachment_url]" id="weed_settings--user_welcome_email_attachment_url" class="large-text" value="<?php echo esc_attr( $weed_values['user_welcome_email_attachment_url'] ); ?>">
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="weed_settings--user_welcome_email_reply_to_email"><?php esc_html_e( '"Reply-To" Email', 'welcome-email-editor' ); ?></label>
				<p class="description"><?php esc_html_e( 'Where replies go. Also used for the admin notification email below.', 'welcome-email-editor' ); ?></p>
			</th>
			<td>
				<input type="text" name="weed_settings[user_welcome_email_reply_to_email]" id="weed_settings--user_welcome_email_reply_to_email" class="regular-text" value="<?php echo esc_attr( $weed_values['user_welcome_email_reply_to_email'] ); ?>">
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="weed_settings--user_welcome_email_reply_to_name"><?php esc_html_e( '"Reply-To" Name', 'welcome-email-editor' ); ?></label>
			</th>
			<td>
				<input type="text" name="weed_settings[user_welcome_email_reply_to_name]" id="weed_settings--user_welcome_email_reply_to_name" class="regular-text" value="<?php echo esc_attr( $weed_values['user_welcome_email_reply_to_name'] ); ?>">
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="weed_settings--user_welcome_email_additional_headers"><?php esc_html_e( 'Additional Email Headers', 'welcome-email-editor' ); ?></label>
				<p class="description"><?php esc_html_e( 'One header per line, e.g. "Bcc: name@example.com". Also added to the admin notification email below.', 'welcome-email-editor' ); ?></p>
			</th>
			<td>
				<textarea name="weed_settings[user_welcome_email_additional_headers]" id="weed_settings--user_welcome_email_additional_headers" class="large-text" rows="4"><?php echo esc_textarea( $weed_values['user_welcome_email_additional_headers'] ); ?></textarea>
			</td>
		</tr>
		<?php
		$weed_test_type = 'user_welcome_email';
		require __DIR__ . '/test-row.php';
		?>
	</table>
</div>
