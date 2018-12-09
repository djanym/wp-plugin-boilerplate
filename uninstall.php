<?php

// if uninstall.php is not called by WordPress, die
if( ! defined('WP_UNINSTALL_PLUGIN') ){
	die;
}

// Delete options
delete_option('breakingnews_area_title');
delete_option('breakingnews_text_color');
delete_option('breakingnews_bg_color');
delete_option('breakingnews_autoinsert');

global $wpdb;
// Delete custom post fields from postmeta table
$wpdb->query("DELETE FROM `{$wpdb->postmeta}` WHERE meta_key = 'breaking_news_active' 
				OR meta_key = 'breaking_news_custom_title' OR meta_key = 'breaking_news_expire_active' 
				OR meta_key = 'breaking_news_expire_date' ");

