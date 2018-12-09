<?php
defined('ABSPATH') or die();

if( ! class_exists('BreakingNewsCron') ){

	/**
	 * Class BreakingNewsCron
	 * @desc Class is responsible for background actions which are assigned via Wordpress Cron API
	 */
	class BreakingNewsCron {

		public function __construct(){

			add_filter('cron_schedules', [$this, 'add_cron_interval'] );
			add_action('breakingnews_disactivate_expired_cron_hook', [$this, 'deactivate_expired'] );

			// Re-schedule cron event if not exists
			if( ! wp_next_scheduled('breakingnews_disactivate_expired_cron_hook') ){
				wp_schedule_event(time(), 'five_seconds', 'breakingnews_disactivate_expired_cron_hook');
			}
		}

		/**
		 * Adds custom cron intervals.
		 *
		 * @param array $schedules 		See Wordpress cron_schedules filter documentation
		 *
		 * @return mixed
		 */
		function add_cron_interval($schedules){
			$schedules['five_seconds'] = array(
				'interval' => 5,
				'display'  => esc_html__('Every Five Seconds', 'breaking-news'),
			);
			return $schedules;
		}

		/**
		 * Checks expired breaking news posts and deactivates them
		 */
		function deactivate_expired(){
			global $wpdb;
			$posts = new WP_Query([
				'fields'     => 'ids',
				'meta_query' => [
					// Search for active breaking news post with expired date
					'relation' => 'AND',
					[
						'key'   => 'breaking_news_active',
						'value' => '1',
					],
					[
						'key'   => 'breaking_news_expire_active',
						'value' => '1',
					],
					[
						'key'    => 'breaking_news_expire_date',
						'type'    => 'DATETIME',
						'value'   => current_time('mysql'),
						'compare' => '<'
					]
				]
			]);

			// Deactivates found posts
			if( $posts && $posts->posts ) foreach( $posts->posts as $post_id ){
				$wpdb->update($wpdb->postmeta,
					['meta_value' => '0'],
					[
						'post_id'  => (int)$post_id,
						'meta_key' => 'breaking_news_active'
					]);
			}
		}

		/**
		 * Removes cron event schedule on plugin deactivation
		 */
		public static function deactivate_plugin(){
			$timestamp = wp_next_scheduled( 'breakingnews_disactivate_expired_cron_hook' );
			wp_unschedule_event( $timestamp, 'breakingnews_disactivate_expired_cron_hook' );
		}
	}

	new BreakingNewsCron();
}