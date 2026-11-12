<?php
/**
 * Mailer box: SMTP or Mailjet API.
 *
 * @package Welcome_Email_Editor
 */

use Weed\Settings\Settings_Module;

defined( 'ABSPATH' ) || die( "Can't access directly" );

$weed_mailer_type = 'mailjet_api' === $weed_values['mailer_type'] ? 'mailjet_api' : 'smtp';
$weed_mailers     = array(
	'smtp'        => __( 'SMTP', 'welcome-email-editor' ),
	'mailjet_api' => __( 'Mailjet API', 'welcome-email-editor' ),
);
$weed_encryptions = array(
	''    => __( 'None', 'welcome-email-editor' ),
	'ssl' => __( 'SSL', 'welcome-email-editor' ),
	'tls' => __( 'TLS', 'welcome-email-editor' ),
);
?>

<div class="heatbox">
	<h2><?php esc_html_e( 'Mailer', 'welcome-email-editor' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">
				<span id="weed-mailer-label"><?php esc_html_e( 'Send emails with', 'welcome-email-editor' ); ?></span>
				<p class="description"><?php esc_html_e( 'Your own SMTP server, or Mailjet\'s email API.', 'welcome-email-editor' ); ?></p>
			</th>
			<td>
				<div role="radiogroup" aria-labelledby="weed-mailer-label">
					<?php foreach ( $weed_mailers as $weed_mailer => $weed_mailer_label ) : ?>
						<label class="label radio-label">
							<?php echo esc_html( $weed_mailer_label ); ?>
							<input type="radio" name="weed_settings[mailer_type]" value="<?php echo esc_attr( $weed_mailer ); ?>" <?php checked( $weed_mailer_type, $weed_mailer ); ?>>
							<div class="indicator"></div>
						</label>
					<?php endforeach; ?>
				</div>
			</td>
		</tr>

		<!-- SMTP -->
		<tr data-show-when-mailer-type="smtp" <?php echo 'smtp' !== $weed_mailer_type ? 'hidden' : ''; ?>>
			<th scope="row">
				<label for="weed_settings--smtp_host"><?php esc_html_e( 'SMTP Host', 'welcome-email-editor' ); ?></label>
				<p class="description"><?php esc_html_e( 'Leave empty to send emails the WordPress way, without SMTP.', 'welcome-email-editor' ); ?></p>
			</th>
			<td>
				<input type="text" name="weed_settings[smtp_host]" id="weed_settings--smtp_host" class="regular-text" value="<?php echo esc_attr( $weed_values['smtp_host'] ); ?>" placeholder="smtp.example.com">
			</td>
		</tr>
		<tr data-show-when-mailer-type="smtp" <?php echo 'smtp' !== $weed_mailer_type ? 'hidden' : ''; ?>>
			<th scope="row"><span id="weed-encryption-label"><?php esc_html_e( 'SMTP Encryption', 'welcome-email-editor' ); ?></span></th>
			<td>
				<div role="radiogroup" aria-labelledby="weed-encryption-label">
					<?php foreach ( $weed_encryptions as $weed_encryption => $weed_encryption_label ) : ?>
						<label class="label radio-label">
							<?php echo esc_html( $weed_encryption_label ); ?>
							<input type="radio" name="weed_settings[smtp_encryption]" value="<?php echo esc_attr( $weed_encryption ); ?>" <?php checked( $weed_values['smtp_encryption'], $weed_encryption ); ?>>
							<div class="indicator"></div>
						</label>
					<?php endforeach; ?>
				</div>
			</td>
		</tr>
		<tr data-show-when-mailer-type="smtp" <?php echo 'smtp' !== $weed_mailer_type ? 'hidden' : ''; ?>>
			<th scope="row">
				<label for="weed_settings--smtp_port"><?php esc_html_e( 'SMTP Port', 'welcome-email-editor' ); ?></label>
				<p class="description"><?php esc_html_e( 'Usually 25 without encryption, 465 for SSL and 587 for TLS.', 'welcome-email-editor' ); ?></p>
			</th>
			<td>
				<input type="number" name="weed_settings[smtp_port]" id="weed_settings--smtp_port" class="small-text" min="1" max="65535" value="<?php echo esc_attr( $weed_values['smtp_port'] ); ?>" placeholder="25">
			</td>
		</tr>
		<tr data-show-when-mailer-type="smtp" <?php echo 'smtp' !== $weed_mailer_type ? 'hidden' : ''; ?>>
			<th scope="row">
				<label for="weed_settings--smtp_username"><?php esc_html_e( 'SMTP Username', 'welcome-email-editor' ); ?></label>
				<p class="description"><?php esc_html_e( 'Leave empty if your server doesn\'t need a login.', 'welcome-email-editor' ); ?></p>
			</th>
			<td>
				<input type="text" name="weed_settings[smtp_username]" id="weed_settings--smtp_username" class="regular-text" value="<?php echo esc_attr( $weed_values['smtp_username'] ); ?>" autocomplete="off">
			</td>
		</tr>
		<tr data-show-when-mailer-type="smtp" <?php echo 'smtp' !== $weed_mailer_type ? 'hidden' : ''; ?>>
			<th scope="row">
				<label for="weed_settings--smtp_password"><?php esc_html_e( 'SMTP Password', 'welcome-email-editor' ); ?></label>
			</th>
			<td>
				<input type="password" name="weed_settings[smtp_password]" id="weed_settings--smtp_password" class="regular-text" value="<?php echo $weed_values['smtp_password'] ? esc_attr( Settings_Module::SAVED_SECRET ) : ''; ?>" autocomplete="new-password">
			</td>
		</tr>

		<!-- Mailjet API -->
		<tr data-show-when-mailer-type="mailjet_api" <?php echo 'mailjet_api' !== $weed_mailer_type ? 'hidden' : ''; ?>>
			<th scope="row">
				<label for="weed_settings--mailjet_api_key"><?php esc_html_e( 'Mailjet API Key', 'welcome-email-editor' ); ?></label>
				<p class="description">
					<?php
					printf(
						/* translators: %s: Link to Mailjet's API keys page. */
						esc_html__( 'Find your keys in your Mailjet account under %s.', 'welcome-email-editor' ),
						'<a href="https://app.mailjet.com/account/apikeys" target="_blank">' . esc_html__( 'API Key Management', 'welcome-email-editor' ) . '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'welcome-email-editor' ) . '</span></a>'
					);
					?>
				</p>
			</th>
			<td>
				<input type="text" name="weed_settings[mailjet_api_key]" id="weed_settings--mailjet_api_key" class="regular-text" value="<?php echo esc_attr( $weed_values['mailjet_api_key'] ); ?>" autocomplete="off">
			</td>
		</tr>
		<tr data-show-when-mailer-type="mailjet_api" <?php echo 'mailjet_api' !== $weed_mailer_type ? 'hidden' : ''; ?>>
			<th scope="row">
				<label for="weed_settings--mailjet_secret_key"><?php esc_html_e( 'Mailjet Secret Key', 'welcome-email-editor' ); ?></label>
			</th>
			<td>
				<input type="password" name="weed_settings[mailjet_secret_key]" id="weed_settings--mailjet_secret_key" class="regular-text" value="<?php echo $weed_values['mailjet_secret_key'] ? esc_attr( Settings_Module::SAVED_SECRET ) : ''; ?>" autocomplete="new-password">
			</td>
		</tr>
	</table>
</div>
