<?php
/**
 * General settings box.
 *
 * @package Welcome_Email_Editor
 */

use Weed\Helpers\Email_Helper;

defined( 'ABSPATH' ) || die( "Can't access directly" );

$weed_default_from_email = ( new Email_Helper() )->get_default_wp_from_email();
?>

<div class="heatbox">
	<h2><?php esc_html_e( 'General Settings', 'welcome-email-editor' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">
				<label for="weed_settings--from_email"><?php esc_html_e( 'From Email Address', 'welcome-email-editor' ); ?></label>
				<p class="description"><?php esc_html_e( 'The email address emails are sent from.', 'welcome-email-editor' ); ?></p>
			</th>
			<td>
				<input type="text" name="weed_settings[from_email]" id="weed_settings--from_email" class="regular-text" value="<?php echo esc_attr( $weed_values['from_email'] ); ?>" placeholder="<?php echo esc_attr( $weed_default_from_email ); ?>">
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="weed_settings--force_from_email"><?php esc_html_e( 'Force From Email', 'welcome-email-editor' ); ?></label>
				<p class="description"><?php esc_html_e( 'Use this address for all emails, even if a plugin sets its own. Recommended for SMTP.', 'welcome-email-editor' ); ?></p>
			</th>
			<td>
				<label class="toggle-switch">
					<input type="checkbox" name="weed_settings[force_from_email]" id="weed_settings--force_from_email" value="1" <?php checked( ! empty( $weed_values['force_from_email'] ) ); ?>>
					<span class="switch-track"><span class="switch-thumb"></span></span>
				</label>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="weed_settings--from_name"><?php esc_html_e( 'From Name', 'welcome-email-editor' ); ?></label>
				<p class="description"><?php esc_html_e( 'The name emails are sent from.', 'welcome-email-editor' ); ?></p>
			</th>
			<td>
				<input type="text" name="weed_settings[from_name]" id="weed_settings--from_name" class="regular-text" value="<?php echo esc_attr( $weed_values['from_name'] ); ?>" placeholder="WordPress">
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="weed_settings--force_from_name"><?php esc_html_e( 'Force From Name', 'welcome-email-editor' ); ?></label>
				<p class="description"><?php esc_html_e( 'Use this name for all emails, even if a plugin sets its own.', 'welcome-email-editor' ); ?></p>
			</th>
			<td>
				<label class="toggle-switch">
					<input type="checkbox" name="weed_settings[force_from_name]" id="weed_settings--force_from_name" value="1" <?php checked( ! empty( $weed_values['force_from_name'] ) ); ?>>
					<span class="switch-track"><span class="switch-thumb"></span></span>
				</label>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="weed_settings--content_type"><?php esc_html_e( 'Mail Content Type', 'welcome-email-editor' ); ?></label>
				<p class="description"><?php esc_html_e( 'Choose HTML if your emails contain HTML.', 'welcome-email-editor' ); ?></p>
			</th>
			<td>
				<select name="weed_settings[content_type]" id="weed_settings--content_type">
					<option value="text" <?php selected( $weed_values['content_type'], 'text' ); ?>><?php esc_html_e( 'Plain Text', 'welcome-email-editor' ); ?></option>
					<option value="html" <?php selected( $weed_values['content_type'], 'html' ); ?>><?php esc_html_e( 'HTML', 'welcome-email-editor' ); ?></option>
				</select>
			</td>
		</tr>
	</table>
</div>
