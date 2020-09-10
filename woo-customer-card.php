<?php
/*
Plugin Name: Woo Customer Card
Plugin URI: https://dits.md
description: Simple personal discount via card number
Version: 0.1
Author: themkvz
Author URI: https://dits.md
License: MIT
Text-domain: woo-customer-card
Domain Path: /languages
*/

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Define default constant
 */
if ( ! defined( 'WCC_URL' ) ) {
	define( 'WCC_URL', plugins_url( '/', __FILE__ ) );
}

if ( ! defined( 'WCC_PATH' ) ) {
	define( 'WCC_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'WCC_PREFIX' ) ) {
	define( 'WCC_PREFIX', 'wcc' );
}

if ( ! defined( 'WCC_DOMAIN' ) ) {
	define( 'WCC_DOMAIN', 'woo-customer-card' );
}

if ( ! defined( 'WCC_VERSION' ) ) {
	define( 'WCC_VERSION', '0.1' );
}

if ( ! function_exists( 'dependency_woo_customer_card' ) ) {
  /**
   * Dependency plugin
   * @return bool
   */
  function dependency_woo_customer_card() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) && current_user_can( 'activate_plugins' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			$error_message = '<p>' . esc_html__( 'This plugin requires ', WCC_DOMAIN ) . '<a href="' . esc_url( 'https://wordpress.org/plugins/woocommerce/' ) . '">WooCommerce</a>' . esc_html__( ' plugin to be active.', WCC_DOMAIN ) . '</p>';
			die( $error_message );
		}

		return true;
	}
}

if ( ! function_exists( 'activation_woo_customer_card' ) ) {
  /**
   * Activation plugin
   */
  function activation_woo_customer_card() {
		if ( dependency_woo_customer_card() ) {
			flush_rewrite_rules();
		}
	}
}

if ( ! function_exists( 'deactivation_woo_customer_card' ) ) {
  /**
   * Deactivation plugin
   */
  function deactivation_woo_customer_card() {
		flush_rewrite_rules();
	}
}

if ( ! function_exists( 'uninstall_woo_customer_card' ) ) {
  /**
   * Uninstall plugin
   */
  function uninstall_woo_customer_card() {
		flush_rewrite_rules();
	}
}

if ( ! function_exists( 'run_woo_customer_card' ) ) {
  /**
   * Create new object and run plugin
   */
  function run_woo_customer_card() {
		load_plugin_textdomain( WCC_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		require_once WCC_PATH . 'includes/woo-customer-card.class.php';

		if ( class_exists( 'WOO_CUSTOMER_CARD' ) ) {
			$callback = new WOO_CUSTOMER_CARD();
		}
	}
}

register_activation_hook( __FILE__, 'activation_woo_customer_card' );
register_deactivation_hook( __FILE__, 'deactivation_woo_customer_card' );
register_uninstall_hook( __FILE__, 'uninstall_woo_customer_card' );
add_action( 'plugins_loaded', 'run_woo_customer_card', 20 );
