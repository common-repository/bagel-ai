<?php

/**
 * @link              https://bagel.ai
 * @since             1.0.0
 * @package           Bagel
 *
 * @wordpress-plugin
 * Plugin Name:       Bagel.ai
 * Description:       Bagel.ai is a <strong>AI content generator</strong> that helps you write better content faster. Built on technologies like chat GPT3 and Open AI, it can generate blogs, outlines, summarize long text, expand sentences, and even help you generate ideas on what and how to write.
 * Version:           1.0.82
 * Author:            Bagel.ai
 * Author URI:        https://bagel.ai/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bagel.ai
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'BAGELAI_BAGEL_VERSION', '1.0.82' );
define( 'BAGELAI_HOST_URL', 'https://bagel.ai' );
define( 'BAGELAI_APP_HOST_URL', 'https://app.bagel.ai' );
define( 'BAGELAI_API_HOST_URL', 'https://api.bagel.ai' );
define( 'BAGELAI_API_BASE_URL', BAGELAI_API_HOST_URL . '/v1/' );
define( 'BAGELAI_API_COMPLETIONS_URL', BAGELAI_API_BASE_URL . 'completions' );
define( 'BAGELAI_API_VALIDATE_TOKEN_URL', BAGELAI_API_BASE_URL . 'auth/token' );
define( 'BAGELAI_API_SUBSCRIPTIONS_URL', BAGELAI_API_BASE_URL . 'subscriptions' );

define( 'BAGELAI_SUBSCRIPTION_PLANS_URL', BAGELAI_HOST_URL . '/pricing' );
define( 'BAGELAI_SIGNUP_URL', BAGELAI_APP_HOST_URL . '/signup' );
define( 'BAGELAI_LOGO_URL', BAGELAI_APP_HOST_URL . '/img/logo-black.png' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-bagel-activator.php
 */
function activate_bagel() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-bagel-activator.php';
	Bagel_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-bagel-deactivator.php
 */
function deactivate_bagel() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-bagel-deactivator.php';
	Bagel_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_bagel' );
register_deactivation_hook( __FILE__, 'deactivate_bagel' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-bagel.php';

add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), array( 'Bagel_AI_Admin', 'admin_plugin_settings_link' ) );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_bagel_ai() {

	$plugin = new Bagel_AI();
	$plugin->run();

}
run_bagel_ai();
