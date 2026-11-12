<?php
/**
 * "Leave a review" sidebar box.
 *
 * @package Welcome_Email_Editor
 */

defined( 'ABSPATH' ) || die( "Can't access directly" );
?>

<div class="heatbox">
	<h2><?php esc_html_e( 'Leave a review', 'welcome-email-editor' ); ?></h2>
	<div class="heatbox-content">
		<p class="description"><?php esc_html_e( 'Do you enjoy Swift SMTP? Leave a review in the WordPress directory and help spread the word!', 'welcome-email-editor' ); ?></p>
		<a href="https://wordpress.org/support/plugin/welcome-email-editor/reviews/#new-post" target="_blank" class="button"><?php esc_html_e( 'Sure, you deserve it!', 'welcome-email-editor' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'welcome-email-editor' ); ?></span></a>
	</div>
</div>
