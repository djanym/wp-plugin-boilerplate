<?php
defined( 'ABSPATH' ) or die();

if( ! class_exists('BreakingNewsMetabox') ){

	/**
	 * Class BreakingNewsMetabox
	 * @desc Class is responsible for breaking news metabox section on the post page in the admin
	 */
	class BreakingNewsMetabox {
		public function __construct(){
			add_action('admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
			add_action('add_meta_boxes', [ $this, 'post_metabox_init'] );
			add_action('save_post', [$this, 'save_post_metabox'], 10);
			add_action('admin_footer', [$this, 'js_code'], 10);
			add_action('admin_notices', [ $this, 'error_notice' ] );
		}

		/**
		 * Adds JS & CSS required files via Wordpress API
		 */
		public function enqueue_scripts(){
			wp_enqueue_script('jquery-ui-datepicker');

			// Add Jquery UI CSS for date-picker
			wp_register_style( 'jquery-ui', 'http://code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css' );
			wp_enqueue_style( 'jquery-ui' );

			// Include CSS & JS for time picker jquery addon
			wp_enqueue_style('datetime-picker', BN_URL . 'admin/css/jquery-ui-timepicker-addon.css');
			wp_enqueue_script('datetim-picker', BN_URL . 'admin/js/jquery-ui-timepicker-addon.js', ['jquery'], false, true);
		}

		/**
		 * Initializes breaking news post metabox via Wordpress API using add_meta_box() function
		 */
		public function post_metabox_init(){
			add_meta_box('breaking_new_metabox', esc_html__('Breaking News Options', 'breaking-news'), [ $this, 'post_metabox_html'], 'post', 'normal', 'high');
		}

		/**
		 * Displays post breaking news options metabox html. Callback for add_meta_box function
		 *
		 * @param $post
		 */
		public function post_metabox_html($post){
			$breaking_news_active = (int)get_post_meta($post->ID, 'breaking_news_active', true) ? 1 : 0;
			$breaking_news_custom_title = get_post_meta($post->ID, 'breaking_news_custom_title', true);
			$breaking_news_expire_active = (int)get_post_meta($post->ID, 'breaking_news_expire_active', true) ? 1 : 0;
			$breaking_news_expire_date = get_post_meta($post->ID, 'breaking_news_expire_date', true);

			// Checks time value & converts to right format for date-time picker.
			if( ! strtotime($breaking_news_expire_date) ){
				$breaking_news_expire_date = '';
			}
			else{
				$breaking_news_expire_date = date("Y-m-d H:i", strtotime($breaking_news_expire_date) );
			}

			wp_nonce_field( 'breaking_news_data', 'breaking_news_nonce' );
			?>
			<div id="post-formats-select">
				<fieldset>
					<p>
						<label for="breaking_news_active"><?php esc_html_e('Make this post breaking news:', 'breaking-news'); ?></label>&nbsp;&nbsp;
						<input type="checkbox" name="breaking_news_active" value="1" id="breaking_news_active" <?php checked($breaking_news_active, true); ?> />
					</p>
					<p>
						<label for="breaking_news_custom_title"><?php esc_html_e('Custom title (leave empty to use default post title):', 'breaking-news'); ?></label>
						<input type="text" value="<?php echo esc_attr($breaking_news_custom_title) ?>" name="breaking_news_custom_title" id="breaking_news_custom_title" />
					</p>
					<p class="meta-options">
						<label for="breaking_news_expire_active"><?php esc_html_e('Set expiration date:', 'breaking-news'); ?></label>&nbsp;&nbsp;
						<input type="checkbox" name="breaking_news_expire_active" value="1" id="breaking_news_expire_active" <?php checked($breaking_news_expire_active, true); ?> />
						<input type="text" name="breaking_news_expire_date" value="<?php echo esc_attr($breaking_news_expire_date) ?>" id="breaking_news_expire_date"
							   placeholder="<?php esc_attr_e('Click to choose date', 'breaking-news'); ?>"
							   class="datetime-picker" id="breaking_news_expire_date" style="<?php echo $breaking_news_expire_active ? '' : 'display: none;'; ?>" />
					</p>
				</fieldset>
			</div>
			<?php
		}

		/**
		 * Saves metabox form data. Called via save_post hook.
		 *
		 * @param $post_id
		 */
		public function save_post_metabox($post_id){
			// Check if user has permissions to save data, correct form nonce, is not autosave, is not a post revision
			if ( ! current_user_can( 'edit_post', $post_id ) || ! wp_verify_nonce( $_POST['breaking_news_nonce'], 'breaking_news_data' ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
				return;
			}

			$user_id = get_current_user_id();
			$breaking_news_custom_title = $_POST[ 'breaking_news_custom_title' ];
			$breaking_news_active = (int)$_POST[ 'breaking_news_active' ] ? 1 : 0;
			$breaking_news_expire_active = (int)$_POST[ 'breaking_news_expire_active' ] ? 1 : 0;
			$breaking_news_expire_date = date("Y-m-d H:i:s", strtotime($_POST[ 'breaking_news_expire_date' ]));

			// If post is active then we should deactivate all other posts.
			global $wpdb;
			$wpdb->update( $wpdb->postmeta,
				array('meta_value' => '0'), // fields to update
				array('meta_key' => 'breaking_news_active') // WHERE
			);

			update_post_meta( $post_id, 'breaking_news_active', $breaking_news_active );
			update_post_meta( $post_id, 'breaking_news_custom_title', $breaking_news_custom_title );

			// If expiration is true than checks if expire date is not passed, otherwise adds error message
			if( $breaking_news_expire_active && strtotime($breaking_news_expire_date) < current_time('timestamp') ) {
				$error_msg = __('Breaking news expiration date field is incorrect. Please choose a date.', 'breaking-news');
				// Save temporary error message to display it after page reloading
				set_transient("breakingnews_errors_{$post_id}_{$user_id}", $error_msg, 45);
				// Force disable expiration due to error
				update_post_meta( $post_id, 'breaking_news_expire_active', 0 );
			}
			else{
				update_post_meta( $post_id, 'breaking_news_expire_active', $breaking_news_expire_active );
			}

			update_post_meta( $post_id, 'breaking_news_expire_date', $breaking_news_expire_date );
		}

		/**
		 * Javascript code to insert in the footer of the page. Called via admin_footer hook.
		 */
		public function js_code(){
			?>
			<script type="text/javascript">
				jQuery(document).ready(function(){
					// Assigns date/time picker to input field
					jQuery('.datetime-picker').datetimepicker({
						dateFormat: 'yy-mm-dd',
						timeFormat: 'HH:mm',
						oneLine: true,
						timeText: '<?php echo esc_html_x('Time', 'Time picker','breaking-news'); ?>',
						hourText: '<?php echo esc_html_x('Hours', 'Time picker', 'breaking-news'); ?>',
						minuteText: '<?php echo esc_html_x('Minutes', 'Time picker', 'breaking-news'); ?>',
						secondText: '<?php echo esc_html_x('Seconds', 'Time picker', 'breaking-news'); ?>',
						currentText: '<?php echo esc_html_x('Now', 'Time picker', 'breaking-news'); ?>',
						closeText: '<?php echo esc_html_x('Close', 'Time picker', 'breaking-news'); ?>'
					});

					// Dynamically hide/show expiration date field
					jQuery('#breaking_news_expire_active').change(function(){
						if( jQuery('#breaking_news_expire_active').is(":checked") ){
							jQuery('#breaking_news_expire_date').show();
						}
						else{
							jQuery('#breaking_news_expire_date').hide();
						}
					});
				});
			</script>
			<?php
		}

		/**
		 * Display error messages if exists
		 */
		function error_notice() {
			$post_id = (int)filter_input(INPUT_GET, 'post');
			$user_id = get_current_user_id();
			$error_msg = false;

			if( $post_id )
				$error_msg = get_transient( "breakingnews_errors_{$post_id}_{$user_id}");

			if ( $error_msg ) : ?>
				<div class="error">
					<p><?php echo esc_html($error_msg); ?></p>
				</div>
				<?php
				delete_transient("breakingnews_errors_{$post_id}_{$user_id}");
			endif;
		}
	}

	new BreakingNewsMetabox();
}