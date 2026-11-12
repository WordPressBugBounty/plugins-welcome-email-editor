<?php
/**
 * Plugin Name: Swift SMTP (formerly Welcome Email Editor)
 * Plugin URI: https://mapsteps.com
 * Description: Swift SMTP is a free & simple SMTP plugin for WordPress. Send emails through your own SMTP server or Mailjet, log them and customize the WordPress welcome & reset password emails.
 * Version: 7.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: David Vongries
 * Author URI: https://mapsteps.com
 * Text Domain: welcome-email-editor
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Welcome_Email_Editor
 */

defined( 'ABSPATH' ) || die( "Can't access directly" );

// Plugin constants.
define( 'WEED_PLUGIN_DIR', rtrim( plugin_dir_path( __FILE__ ), '/' ) );
define( 'WEED_PLUGIN_URL', rtrim( plugin_dir_url( __FILE__ ), '/' ) );
define( 'WEED_PLUGIN_VERSION', '7.0' );
define( 'WEED_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once __DIR__ . '/inc/class-vars.php';
require_once __DIR__ . '/inc/helpers/class-content-helper.php';
require_once __DIR__ . '/inc/helpers/class-email-helper.php';
require_once __DIR__ . '/inc/class-backwards-compatibility.php';
require_once __DIR__ . '/inc/wp-new-user-notification.php';
require_once __DIR__ . '/inc/class-setup.php';

Weed\Backwards_Compatibility::init();
Weed\Setup::init();

register_deactivation_hook( __FILE__, array( 'Weed\Setup', 'deactivate' ) );
