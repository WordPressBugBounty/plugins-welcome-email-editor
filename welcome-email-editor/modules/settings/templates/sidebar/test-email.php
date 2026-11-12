<?php
/**
 * "Send a test email" sidebar box.
 *
 * @package Welcome_Email_Editor
 */

defined( 'ABSPATH' ) || die( "Can't access directly" );

$weed_admin_email = get_bloginfo( 'admin_email' );
$weed_recipient   = ! empty( $weed_values['test_smtp_recipient_email'] ) ? $weed_values['test_smtp_recipient_email'] : $weed_admin_email;
?>

<div class="heatbox weed-test-email-box">
	<h2><?php esc_html_e( 'Send a Test Email', 'welcome-email-editor' ); ?></h2>
	<div class="heatbox-content">
		<p class="description"><?php esc_html_e( 'Check that everything works: this saves your settings and sends a test email. Nothing arrived? Check the spam folder and your settings.', 'welcome-email-editor' ); ?></p>
		<p>
			<label for="weed_settings--test_smtp_recipient_email" class="screen-reader-text"><?php esc_html_e( 'Send the test email to', 'welcome-email-editor' ); ?></label>
			<input type="email" name="weed_settings[test_smtp_recipient_email]" id="weed_settings--test_smtp_recipient_email" class="large-text" value="<?php echo esc_attr( $weed_recipient ); ?>" placeholder="<?php echo esc_attr( $weed_admin_email ); ?>">
		</p>
		<div class="weed-test-email">
			<button type="button" class="button button-primary button-full weed-test-email-button" data-email-type="<?php echo 'mailjet_api' === $weed_values['mailer_type'] ? 'test_mailjet_api_email' : 'test_smtp_email'; ?>"><?php esc_html_e( 'Send Test Email', 'welcome-email-editor' ); ?></button>
			<span class="heatbox-notice weed-test-email-notice" role="status"></span>
		</div>
	</div>
</div>
