<?php
/**
 * Plugin Name: TSN Modules - Telugu Samiti of Nebraska
 * Plugin URI: https://telugusamiti.org
 * Description: Comprehensive membership, event ticketing, and donation management system for Telugu Samiti of Nebraska
 * Version: 1.0.0
 * Author: Telugu Samiti of Nebraska
 * Author URI: https://telugusamiti.org
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: tsn-modules
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Plugin version
 */
define('TSN_MODULES_VERSION', '1.0.0');

/**
 * Plugin directory path
 */
define('TSN_MODULES_PATH', plugin_dir_path(__FILE__));

/**
 * Plugin directory URL
 */
define('TSN_MODULES_URL', plugin_dir_url(__FILE__));

/**
 * Plugin basename
 */
define('TSN_MODULES_BASENAME', plugin_basename(__FILE__));

/**
 * Activation hook
 */
function activate_tsn_modules() {
    require_once TSN_MODULES_PATH . 'includes/class-tsn-activator.php';
    TSN_Activator::activate();
}

/**
 * Deactivation hook
 */
function deactivate_tsn_modules() {
    require_once TSN_MODULES_PATH . 'includes/class-tsn-deactivator.php';
    TSN_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_tsn_modules');
register_deactivation_hook(__FILE__, 'deactivate_tsn_modules');

/**
 * Core plugin class
 */
require TSN_MODULES_PATH . 'includes/class-tsn-core.php';

/**
 * Begin execution
 */
function run_tsn_modules() {
    global $tsn_core;
    $tsn_core = new TSN_Core();
    $tsn_core->run();
}
run_tsn_modules();
