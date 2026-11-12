<?php
/**
 * Template tags sidebar boxes.
 *
 * @package Welcome_Email_Editor
 */

defined( 'ABSPATH' ) || die( "Can't access directly" );

$weed_tags = array( '[site_url]', '[login_url]', '[reset_pass_url]', '[user_email]', '[user_login]', '[user_id]', '[first_name]', '[last_name]', '[blog_name]', '[admin_email]', '[custom_fields]', '[date]', '[time]', '[user_ip]', '[not_logged_in][/not_logged_in]', '[logged_in][/logged_in]' );
?>

<div class="heatbox tags-heatbox">
	<h2>
		<?php esc_html_e( 'Template Tags', 'welcome-email-editor' ); ?>
		<span class="action-status" role="status"><?php esc_html_e( 'Copied!', 'welcome-email-editor' ); ?></span>
	</h2>
	<div class="heatbox-content">
		<p class="description"><?php esc_html_e( 'Use these tags in the subjects and bodies to show information about the user and your site. Click a tag to copy it.', 'welcome-email-editor' ); ?></p>
		<div class="tags-wrapper">
			<?php foreach ( $weed_tags as $weed_tag ) : ?>
				<code tabindex="0" role="button"><?php echo esc_html( $weed_tag ); ?></code>
			<?php endforeach; ?>
		</div>
	</div>
</div>

<?php if ( defined( 'BP_PLUGIN_URL' ) ) : ?>
	<div class="heatbox tags-heatbox">
		<h2>
			<?php esc_html_e( 'BuddyPress Template Tags', 'welcome-email-editor' ); ?>
			<span class="action-status" role="status"><?php esc_html_e( 'Copied!', 'welcome-email-editor' ); ?></span>
		</h2>
		<div class="heatbox-content">
			<p class="description"><?php esc_html_e( 'Shows the BuddyPress profile fields in the admin notification email.', 'welcome-email-editor' ); ?></p>
			<div class="tags-wrapper">
				<code tabindex="0" role="button">[bp_custom_fields]</code>
			</div>
		</div>
	</div>
<?php endif; ?>

<div class="heatbox tags-heatbox">
	<h2>
		<?php esc_html_e( 'Debugging', 'welcome-email-editor' ); ?>
		<span class="action-status" role="status"><?php esc_html_e( 'Copied!', 'welcome-email-editor' ); ?></span>
	</h2>
	<div class="heatbox-content">
		<p class="description"><?php esc_html_e( 'Shows the submitted form data in the admin notification email. Passwords are hidden.', 'welcome-email-editor' ); ?></p>
		<div class="tags-wrapper">
			<code tabindex="0" role="button">[post_data]</code>
		</div>
	</div>
</div>
