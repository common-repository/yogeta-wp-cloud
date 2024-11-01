<?php
/*
Plugin Name: Yogeta WP Cloud
Contributors: yogeta
Tags: Yogeta, WP, wordpress, hosting, migration, cloud
Description: Migrate Your site to Yogeta servers
Version: 1.0
Author: yogeta
Author URI: https://yogeta.com
License: GPLv2 or later
Tested up to: 6.1
Requires PHP: 5.4
Stable tag: 1.0
Requires at least: 4.7
*/


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once( 'functions.php' );
function ycwp_styles_and_scripts() {
	wp_register_style( 'custom_css', plugin_dir_url( __FILE__ ) . '/styles/custom.css', array(), time() );
	wp_register_style( 'reset_css', plugin_dir_url( __FILE__ ) . '/styles/reset.css', array(), time() );
	wp_register_style( 'inner_css', plugin_dir_url( __FILE__ ) . '/styles/inner.css', array(), time() );
	wp_enqueue_style( 'custom_css' );
	wp_enqueue_style( 'reset_css' );
	wp_enqueue_style( 'inner_css' );
	wp_register_script( 'ycwp_ajax_migrate', plugins_url( 'scripts/js/jquery.ajax.js', __FILE__ ), array(), time(), true );
	wp_enqueue_script( 'ycwp_ajax_migrate' );
	wp_localize_script(
		'ycwp_ajax_migrate',
		'ajax_object',
		array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) )
	);
}

function ycwp_login_failed( $username ) {
	$user = get_user_by( 'login', $username );

}

function ycwp_init_ymigrate() {
	require_once( 'includes/y-migrate-admin.php' );
}


function ycwp_admin_menu() {
	add_menu_page( 'Yogeta Wp Cloud', 'Yogeta Wp Cloud', 'manage_options', 'yogeta-cloud', 'ycwp_init_ymigrate' );
}

function ycwp_get_ip() {
	foreach (
		array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
			'SERVER_ADDR'
		) as $key
	) {
		if ( array_key_exists( $key, $_SERVER ) === true ) {
			foreach ( array_map( 'trim', explode( ',', sanitize_text_field($_SERVER[ $key ]) ) ) as $ip ) {
				return $ip;
			}
		}
	}
}

function ycwp_on_yogeta_cloud_activate() {
	global $wpdb;
	$site = sanitize_url($_SERVER['SERVER_NAME']);
	ycwp_update_site_status( $site, true );
}

function ycwp_on_yogeta_cloud_deactivate() {
	global $wpdb;
	$site = sanitize_url($_SERVER['SERVER_NAME']);
	ycwp_update_site_status( $site, false );
}
register_activation_hook( __FILE__, 'ycwp_on_yogeta_cloud_activate' );
register_deactivation_hook( __FILE__, 'ycwp_on_yogeta_cloud_deactivate' );
add_action( 'admin_enqueue_scripts', 'ycwp_styles_and_scripts' );
add_action( 'admin_menu', 'ycwp_admin_menu' );