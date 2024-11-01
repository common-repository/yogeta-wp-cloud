<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

$site = sanitize_url($_SERVER['SERVER_NAME']);
$body = array(
	'domain' => $site
);
wp_remote_post( YCWP_API_SERVER . '/migrate/plugin-removed', [ 'body' => $body ] );