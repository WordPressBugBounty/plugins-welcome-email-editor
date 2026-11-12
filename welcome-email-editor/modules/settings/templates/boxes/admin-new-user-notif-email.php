<?php
/**
 * New user notification email (for admins) box.
 *
 * @package Welcome_Email_Editor
 */

defined( 'ABSPATH' ) || die( "Can't access directly" );
?>

<div class="heatbox">
	<h2><?php esc_html_e( 'New User Notification Email (for Admins)', 'welcome-email-editor' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">
				<label for="weed_settings--admin_new_user_notif_email_subject"><?php esc_html_e( 'Email Subject', 'welcome-email-editor' ); ?></label>
			</th>
			<td>
				<input type="text" name="weed_settings[admin_new_user_notif_email_subject]" id="weed_settings--admin_new_user_notif_email_subject" class="large-text" value="<?php echo esc_attr( $weed_values['admin_new_user_notif_email_subject'] ); ?>" placeholder="<?php echo esc_attr( $weed_defaults['admin_new_user_notif_email_subject'] ); ?>">
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="weed_settings--admin_new_user_notif_email_body"><?php esc_html_e( 'Email Body', 'welcome-email-editor' ); ?></label>
			</th>
			<td>
				<textarea name="weed_settings[admin_new_user_notif_email_body]" id="weed_settings--admin_new_user_notif_email_body" class="large-text" rows="8" placeholder="<?php echo esc_attr( $weed_defaults['admin_new_user_notif_email_body'] ); ?>"><?php echo esc_textarea( $weed_values['admin_new_user_notif_email_body'] ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="weed_settings--admin_new_user_notif_email_custom_recipients"><?php esc_html_e( 'Additional Recipients', 'welcome-email-editor' ); ?></label>
				<p class="description">
					<?php
					/* translators: %s: Admin email address. */
					echo esc_html( sprintf( __( 'Comma-separated user IDs, e.g. 1, 2, 3. The site admin (%s) always gets this email.', 'welcome-email-editor' ), get_option( 'admin_email' ) ) );
					?>
				</p>
			</th>
			<td>
				<input type="text" name="weed_settings[admin_new_user_notif_email_custom_recipients]" id="weed_settings--admin_new_user_notif_email_custom_recipients" class="regular-text" value="<?php echo esc_attr( $weed_values['admin_new_user_notif_email_custom_recipients'] ); ?>">
			</td>
		</tr>
		<?php
		$weed_test_type = 'admin_new_user_notif_email';
		require __DIR__ . '/test-row.php';
		?>
	</table>
</div>
