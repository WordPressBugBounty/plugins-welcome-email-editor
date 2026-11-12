<?php
/**
 * "Other" box.
 *
 * @package Welcome_Email_Editor
 */

defined( 'ABSPATH' ) || die( "Can't access directly" );
?>

<div class="heatbox">
	<h2><?php esc_html_e( 'Other', 'welcome-email-editor' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">
				<label for="weed_settings--remove_on_uninstall"><?php esc_html_e( 'Remove on Uninstall', 'welcome-email-editor' ); ?></label>
				<p class="description"><?php esc_html_e( 'Delete all settings and email logs when the plugin is deleted.', 'welcome-email-editor' ); ?></p>
			</th>
			<td>
				<label class="toggle-switch">
					<input type="checkbox" name="weed_settings[remove_on_uninstall]" id="weed_settings--remove_on_uninstall" value="1" <?php checked( ! empty( $weed_values['remove_on_uninstall'] ) ); ?>>
					<span class="switch-track"><span class="switch-thumb"></span></span>
				</label>
			</td>
		</tr>
	</table>
</div>
