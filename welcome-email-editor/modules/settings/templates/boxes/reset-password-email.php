<?php
/**
 * Reset password email box.
 *
 * @package Welcome_Email_Editor
 */

defined( 'ABSPATH' ) || die( "Can't access directly" );
?>

<div class="heatbox">
	<h2><?php esc_html_e( 'Reset Password Email', 'welcome-email-editor' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">
				<label for="weed_settings--reset_password_email_subject"><?php esc_html_e( 'Email Subject', 'welcome-email-editor' ); ?></label>
			</th>
			<td>
				<input type="text" name="weed_settings[reset_password_email_subject]" id="weed_settings--reset_password_email_subject" class="large-text" value="<?php echo esc_attr( $weed_values['reset_password_email_subject'] ); ?>" placeholder="<?php echo esc_attr( $weed_defaults['reset_password_email_subject'] ); ?>">
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="weed_settings--reset_password_email_body"><?php esc_html_e( 'Email Body', 'welcome-email-editor' ); ?></label>
			</th>
			<td>
				<textarea name="weed_settings[reset_password_email_body]" id="weed_settings--reset_password_email_body" class="large-text" rows="8" placeholder="<?php echo esc_attr( $weed_defaults['reset_password_email_body'] ); ?>"><?php echo esc_textarea( $weed_values['reset_password_email_body'] ); ?></textarea>
			</td>
		</tr>
		<?php
		$weed_test_type = 'reset_password_email';
		require __DIR__ . '/test-row.php';
		?>
	</table>
</div>
