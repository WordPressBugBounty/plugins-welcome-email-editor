<?php
/**
 * SMTP module.
 *
 * @package Welcome_Email_Editor
 */

namespace Weed\Smtp;

defined( 'ABSPATH' ) || die( "Can't access directly" );

/**
 * Sends all emails through the SMTP server or the Mailjet API set on the settings page.
 */
class Smtp_Module {

	/**
	 * Set up the module.
	 */
	public function setup() {

		require_once __DIR__ . '/class-smtp-output.php';

		( new Smtp_Output() )->setup();

	}

}
