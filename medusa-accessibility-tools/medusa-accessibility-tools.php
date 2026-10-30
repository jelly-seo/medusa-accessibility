<?php
/*
Plugin Name: Medusa Accessibility Tools
Plugin URI: https://jellyseo.com/accessibility
Description: A floating accessibility button: larger text, a dyslexic friendly font, inverted colours and colour blind modes. Free from Jelly.
Version: 1.0.0
Author: Jelly
Author URI: https://jellyseo.com/
Requires at least: 6.3
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: medusa-accessibility-tools
Update URI: https://github.com/jelly-seo/medusa-accessibility
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MEDUSA_A11Y_VERSION', '1.0.0' );
define( 'MEDUSA_A11Y_FILE', __FILE__ );
define( 'MEDUSA_A11Y_DIR', plugin_dir_path( __FILE__ ) );
define( 'MEDUSA_A11Y_URL', plugin_dir_url( __FILE__ ) );
define( 'MEDUSA_A11Y_REPO', 'jelly-seo/medusa-accessibility' );
define( 'MEDUSA_A11Y_LEARN', 'https://jellyseo.com/accessibility' );

require MEDUSA_A11Y_DIR . 'includes/settings.php';
require MEDUSA_A11Y_DIR . 'includes/front.php';
require MEDUSA_A11Y_DIR . 'includes/updater.php';
