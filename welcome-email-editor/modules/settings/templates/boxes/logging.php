<?php
/**
 * Email logging box.
 *
 * @package Welcome_Email_Editor
 */

defined( 'ABSPATH' ) || die( "Can't access directly" );
?>

<div class="heatbox">
	<h2><?php esc_html_e( 'Email Logging', 'welcome-email-editor' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">
				<label for="weed_settings--enable_email_logging"><?php esc_html_e( 'Enable Email Logging', 'welcome-email-editor' ); ?></label>
				<p class="description"><?php esc_html_e( 'Keep a record of every email your site sends, with its status.', 'welcome-email-editor' ); ?></p>
			</th>
			<td>
				<label class="toggle-switch">
					<input type="checkbox" name="weed_settings[enable_email_logging]" id="weed_settings--enable_email_logging" value="1" <?php checked( ! empty( $weed_values['enable_email_logging'] ) ); ?>>
					<span class="switch-track"><span class="switch-thumb"></span></span>
				</label>
			</td>
		</tr>
		<tr class="weed-logs-retention<?php echo empty( $weed_values['enable_email_logging'] ) ? ' row-disabled' : ''; ?>">
			<th scope="row">
				<label for="weed_settings--email_logs_retention_days"><?php esc_html_e( 'Keep Logs For', 'welcome-email-editor' ); ?></label>
				<p class="description"><?php esc_html_e( 'Older logs are deleted automatically. Enter 0 to keep them forever.', 'welcome-email-editor' ); ?></p>
			</th>
			<td>
				<input type="number" name="weed_settings[email_logs_retention_days]" id="weed_settings--email_logs_retention_days" class="small-text" min="0" max="3650" value="<?php echo esc_attr( $weed_values['email_logs_retention_days'] ); ?>" <?php echo empty( $weed_values['enable_email_logging'] ) ? 'readonly' : ''; ?>>
				<span class="heatbox-unit"><?php esc_html_e( 'days', 'welcome-email-editor' ); ?></span>
			</td>
		</tr>
	</table>
</div>
