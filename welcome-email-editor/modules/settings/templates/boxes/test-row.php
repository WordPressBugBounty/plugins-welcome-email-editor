<?php
/**
 * "Send a test email" row of the welcome & reset password email boxes.
 *
 * Expects $weed_test_type: the email type sent by the button.
 *
 * @package Welcome_Email_Editor
 */

defined( 'ABSPATH' ) || die( "Can't access directly" );
?>

<tr>
	<th scope="row">
		<?php esc_html_e( 'Test Email', 'welcome-email-editor' ); ?>
		<p class="description"><?php esc_html_e( 'Saves your changes and sends this email to you.', 'welcome-email-editor' ); ?></p>
	</th>
	<td>
		<div class="weed-test-email">
			<button type="button" class="button weed-test-email-button" data-email-type="<?php echo esc_attr( $weed_test_type ); ?>"><?php esc_html_e( 'Send Test Email', 'welcome-email-editor' ); ?></button>
			<span class="heatbox-notice weed-test-email-notice" role="status"></span>
		</div>
	</td>
</tr>
