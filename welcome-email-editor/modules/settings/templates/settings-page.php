<?php
/**
 * Settings page.
 *
 * Included from Settings_Module::page_content().
 *
 * @package Welcome_Email_Editor
 */

use Weed\Helpers\Content_Helper;
use Weed\Vars;

defined( 'ABSPATH' ) || die( "Can't access directly" );

$weed_values   = Vars::get( 'values' );
$weed_defaults = Content_Helper::default_settings();

$weed_tabs = array(
	'smtp'          => __( 'SMTP Settings', 'welcome-email-editor' ),
	'email-logging' => __( 'Email Logging', 'welcome-email-editor' ),
	'welcome-email' => __( 'Welcome Email Editor', 'welcome-email-editor' ),
	'misc'          => __( 'Other', 'welcome-email-editor' ),
);

$weed_products = array(
	array(
		'url'         => 'https://wordpress.org/plugins/ultimate-dashboard/',
		'image'       => 'ultimate-dashboard.jpg',
		'title'       => __( 'Ultimate Dashboard', 'welcome-email-editor' ),
		'subheadline' => __( 'Fully customize your WordPress dashboard.', 'welcome-email-editor' ),
		'text'        => __( 'Ultimate Dashboard is the #1 plugin to create a custom WordPress dashboard for you and your clients. It also comes with multisite support which makes it the perfect plugin for your WaaS network.', 'welcome-email-editor' ),
	),
	array(
		'url'         => 'https://wordpress.org/themes/page-builder-framework/',
		'image'       => 'page-builder-framework.jpg',
		'title'       => __( 'Page Builder Framework', 'welcome-email-editor' ),
		'subheadline' => __( 'The only theme you\'ll ever need.', 'welcome-email-editor' ),
		'text'        => __( 'With its minimalistic design the Page Builder Framework theme is the perfect foundation for your next project. Build blazing fast websites with a theme that is easy to use, lightweight & highly customizable.', 'welcome-email-editor' ),
	),
	array(
		'url'         => 'https://wordpress.org/plugins/better-admin-bar/',
		'image'       => 'better-admin-bar.jpg',
		'title'       => __( 'Better Admin Bar', 'welcome-email-editor' ),
		'subheadline' => __( 'Replace the boring WordPress admin bar.', 'welcome-email-editor' ),
		'text'        => __( 'Better Admin Bar is the plugin that makes your clients love WordPress. It replaces the default admin bar with your own navigation panel for the best possible experience when editing & navigating a website.', 'welcome-email-editor' ),
	),
);
?>

<div class="wrap heatbox-wrap weed-settings-page">

	<div class="heatbox-header heatbox-has-tab-nav heatbox-margin-bottom">

		<div class="heatbox-container heatbox-container-center">

			<div class="logo-container">

				<div>
					<h1 class="title">
						<?php esc_html_e( 'Swift SMTP', 'welcome-email-editor' ); ?>
						<span class="version"><?php echo esc_html( WEED_PLUGIN_VERSION ); ?></span>
					</h1>
					<p class="subtitle"><?php esc_html_e( 'A free & simple SMTP plugin for WordPress.', 'welcome-email-editor' ); ?></p>
				</div>

				<div>
					<img src="<?php echo esc_url( WEED_PLUGIN_URL . '/assets/img/swift-smtp-logo.png' ); ?>" alt="">
				</div>

			</div>

			<nav aria-label="<?php esc_attr_e( 'Settings sections', 'welcome-email-editor' ); ?>">
				<ul class="heatbox-tab-nav">
					<?php foreach ( $weed_tabs as $weed_tab => $weed_tab_label ) : ?>
						<li class="heatbox-tab-nav-item<?php echo 'smtp' === $weed_tab ? ' active' : ''; ?>" data-panel="<?php echo esc_attr( $weed_tab ); ?>">
							<a href="#<?php echo esc_attr( $weed_tab ); ?>"><?php echo esc_html( $weed_tab_label ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>

		</div>

	</div>

	<form method="post" action="options.php" class="weed-settings-form" novalidate>

		<?php settings_fields( 'weed-settings-group' ); ?>

		<div class="heatbox-container heatbox-container-center heatbox-column-container">

			<div class="heatbox-main">

				<!-- WordPress places admin notices after this marker -->
				<hr class="wp-header-end">

				<?php
				settings_errors( 'weed_settings' );

				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only shows a notice.
				if ( isset( $_GET['settings-updated'] ) ) {
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'welcome-email-editor' ) . '</p></div>';
				}

				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only shows a notice.
				if ( isset( $_GET['weed-reset'] ) ) {
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings reset.', 'welcome-email-editor' ) . '</p></div>';
				}
				?>

				<div class="heatbox-admin-panel" data-panel="smtp" style="display: block;">
					<?php
					require __DIR__ . '/boxes/general.php';
					require __DIR__ . '/boxes/mailer.php';
					?>
				</div>

				<div class="heatbox-admin-panel" data-panel="email-logging">
					<?php require __DIR__ . '/boxes/logging.php'; ?>
				</div>

				<div class="heatbox-admin-panel" data-panel="welcome-email">
					<?php
					require __DIR__ . '/boxes/user-welcome-email.php';
					require __DIR__ . '/boxes/admin-new-user-notif-email.php';
					require __DIR__ . '/boxes/reset-password-email.php';
					?>
				</div>

				<div class="heatbox-admin-panel" data-panel="misc">
					<?php require __DIR__ . '/boxes/misc.php'; ?>
				</div>

				<div class="heatbox-form-footer">
					<div>
						<button type="submit" class="button button-primary button-larger"><?php esc_html_e( 'Save Changes', 'welcome-email-editor' ); ?></button>
					</div>
					<div>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=weed_settings&action=weed_reset_settings' ), 'weed_reset_settings' ) ); ?>" class="button button-larger is-destructive weed-reset-settings-button"><?php esc_html_e( 'Reset Settings', 'welcome-email-editor' ); ?></a>
					</div>
				</div>

			</div>

			<div class="heatbox-sidebar">

				<div class="heatbox-admin-panel" data-panel="smtp" style="display: block;">
					<?php require __DIR__ . '/sidebar/test-email.php'; ?>
				</div>

				<div class="heatbox-admin-panel" data-panel="welcome-email">
					<?php require __DIR__ . '/sidebar/template-tags.php'; ?>
				</div>

				<?php require __DIR__ . '/sidebar/review.php'; ?>

			</div>

		</div>

	</form>

	<div class="heatbox-container heatbox-container-wide heatbox-container-center heatbox-products">

		<div class="heatbox-divider"></div>

		<h2><?php esc_html_e( 'Check out our other free WordPress products!', 'welcome-email-editor' ); ?></h2>

		<ul class="products">
			<?php foreach ( $weed_products as $weed_product ) : ?>
				<li class="heatbox">
					<a href="<?php echo esc_url( $weed_product['url'] ); ?>" target="_blank" aria-hidden="true" tabindex="-1">
						<img src="<?php echo esc_url( WEED_PLUGIN_URL . '/assets/img/' . $weed_product['image'] ); ?>" alt="">
					</a>
					<div class="heatbox-content">
						<h3><?php echo esc_html( $weed_product['title'] ); ?></h3>
						<p class="subheadline"><?php echo esc_html( $weed_product['subheadline'] ); ?></p>
						<p><?php echo esc_html( $weed_product['text'] ); ?></p>
						<a href="<?php echo esc_url( $weed_product['url'] ); ?>" target="_blank" class="button"><?php esc_html_e( 'Learn more', 'welcome-email-editor' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'welcome-email-editor' ); ?></span></a>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>

		<p class="credit"><?php esc_html_e( 'Made with ❤ in Torsby, Sweden', 'welcome-email-editor' ); ?></p>

	</div>

</div>
