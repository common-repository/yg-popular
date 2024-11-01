<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "postview");
$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_key='%s' OR meta_key='%s' OR meta_key='%s'",'views_total','views_last_x_day','views_last_y_day'));
delete_option('pop-u-lar_version');
delete_option('yg_pop_dur_options');