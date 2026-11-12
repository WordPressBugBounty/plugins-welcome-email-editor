<?php
/**
 * Mailjet API module.
 *
 * @package Welcome_Email_Editor
 */

namespace Weed\Mailjet_Api;

defined( 'ABSPATH' ) || die( "Can't access directly" );

/**
 * Loads the Mailjet API sender (used by the SMTP module when Mailjet is the selected mailer).
 */
class Mailjet_Api_Module {

	/**
	 * Set up the module.
	 */
	public function setup() {

		require_once __DIR__ . '/class-mailjet-api-sender.php';

	}

}
