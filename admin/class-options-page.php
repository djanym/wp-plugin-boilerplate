<?php
defined( 'ABSPATH' ) or die();

if( ! class_exists('BreakingNewsOptionsPage') ){

	/**
	 * Class BreakingNewsOptionsPage
	 * @desc Class is responsible for a settings page in the admin to configure some options for the plugin
	 */
	class BreakingNewsOptionsPage {

		public function __construct(){
			add_action('admin_menu', [ $this, 'options_page_init' ]);
			add_action('admin_init', [ $this, 'setup_fields' ]);
			add_action('admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
			add_action('admin_footer', [$this, 'js_code'], 10);
		}

		/**
		 * Adds JS & CSS required files via Wordpress API
		 */
		public function enqueue_scripts(){
			// Add the color picker JS & CSS files
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
		}

		/**
		 * Adds plugin option page & menu item in the backend
		 */
		public function options_page_init(){
			add_options_page(__('Breaking News options', 'breaking-news'), __('Breaking News', 'breaking-news'), 'manage_options', 'breakingnews', [ $this, 'options_page_content']);
		}

		/**
		 * Outputs plugin option page. Callback for add_options_page function
		 */
		public function options_page_content(){ ?>
			<div class="wrap">
				<h1><?php esc_html_e('Breaking News options', 'breaking-news'); ?></h1>
				<form method="POST" action="options.php">
					<?php
					settings_fields('breakingnews');
					do_settings_sections('breakingnews');
					submit_button();
					?>
				</form>
			</div>
			<div class="wrap"> <!-- current breaking news post section -->
				<h1><?php esc_html_e('Current Breaking News post', 'breaking-news'); ?></h1>
				<?php
				$breaking_post = get_breaking_post();
				if( $breaking_post ) :
					$breaking_news_custom_title = get_post_meta($breaking_post->ID, 'breaking_news_custom_title', true);
					$breaking_news_expire_active = (int)get_post_meta($breaking_post->ID, 'breaking_news_expire_active', true) ? 1 : 0;
					$breaking_news_expire_date = get_post_meta($breaking_post->ID, 'breaking_news_expire_date', true);
					?>
					<table class="form-table">
						<tbody>
						<tr>
							<th scope="row"><?php _e('Post title'); ?></th>
							<td><?php echo esc_html( get_the_title($breaking_post) ); ?>
								<a href="<?php echo esc_url( get_edit_post_link($breaking_post) ); ?>"><?php _e('Edit post', 'breaking-news'); ?></a>
							</td>
						</tr>

						<?php if( $breaking_news_custom_title ) : ?>
						<tr>
							<th scope="row"><?php _e('Custom post title to display'); ?></th>
							<td><?php echo esc_html( $breaking_news_custom_title ); ?></td>
						</tr>
						<?php endif; ?>

						<?php if( $breaking_news_expire_active ) : ?>
						<tr>
							<th scope="row"><?php _e('Expiration date'); ?></th>
							<td><?php echo esc_html( $breaking_news_expire_date ); ?></td>
						</tr>
						<?php endif; ?>

						</tbody>
					</table>
				<?php else : ?>
					<p><?php esc_html_e('There is no active breaking news post', 'breaking-news'); ?></p>
				<?php endif; ?>
			</div>  <!-- end of current breaking news post section --><?php
		}

		/**
		 * Adds plugin option fields via Wordpress Settings API. Called from init hook.
		 */
		public function setup_fields(){
			add_settings_section('breakingnews_general', '', array(), 'breakingnews');

			$fields = array(
				array(
					'label'   => esc_html__('Breaking News area title', 'breaking-news'),
					'id'      => 'breakingnews_area_title',
					'type'    => 'text',
					'section' => 'breakingnews_general',
					'desc'    => esc_html__('e.g. "Breaking news"', 'breaking-news'),
				),
				array(
					'label'   => esc_html__('Title text color', 'breaking-news'),
					'id'      => 'breakingnews_text_color',
					'type'    => 'color',
					'section' => 'breakingnews_general',
				),
				array(
					'label'   => esc_html__('Text background color', 'breaking-news'),
					'id'      => 'breakingnews_bg_color',
					'type'    => 'color',
					'section' => 'breakingnews_general',
				),
				array(
					'label'   => esc_html__('Automatically insert breaking news container', 'breaking-news'),
					'id'      => 'breakingnews_autoinsert',
					'type'    => 'checkbox',
					'section' => 'breakingnews_general',
					'desc'    => esc_html__('If there is a <header> tag in the theme template, then it will be automatically inserted at the bottom of this tag. If it\'s not working or you want it in another place then you can uncheck this option and manually insert this code into the template file: <?php echo do_shortcode(\'[breaking_news]\'); ?>', 'breaking-news'),
				)
			);

			foreach($fields as $field){
				register_setting('breakingnews', $field['id']);
				add_settings_field($field['id'], $field['label'], [$this, 'field_callback'], 'breakingnews', $field['section'], $field);
			}
		}

		/**
		 * Outputs field form html depending on field's type. Callback for add_settings_field function.
		 * @param array $field 		Array values are set in setup_fields() function
		 */
		public function field_callback($field){
			$value = get_option($field['id']);
			switch($field['type']){
				case 'color':
					printf('<input name="%1$s" id="%1$s" type="text" value="%2$s" class="colorpicker-field" />',
						$field['id'],
						sanitize_hex_color($value)
					);
					break;
				case 'checkbox':
					printf('<input name="%1$s" id="%1$s" type="checkbox" value="1" %3$s />',
						$field['id'],
						sanitize_hex_color($value),
					 	checked($value, true, false)
					);
					break;
				default:
					printf('<input name="%1$s" id="%1$s" type="%2$s" value="%3$s" />',
						$field['id'],
						$field['type'],
						esc_attr($value)
					);
			}
			if( array_key_exists('desc', $field ) && $field['desc'] ){
				printf('<p class="description">%s </p>', $field['desc']);
			}
		}

		/**
		 * Javascript code to insert in the footer of the page. Called via admin_footer hook.
		 */
		public function js_code(){
			?>
			<script type="text/javascript">
				jQuery(document).ready(function(){
					// Assign color picker to input field
					jQuery('.colorpicker-field').wpColorPicker();
				});
			</script>
			<?php
		}

		/**
		 * Adds default options value on plugin activation
		 */
		public static function activate_plugin(){
			add_option('breakingnews_area_title', __('Breaking news', 'breaking-news'));
			add_option('breakingnews_text_color', '#F0F0F0');
			add_option('breakingnews_bg_color', '#333333');
			add_option('breakingnews_autoinsert', '1');
		}

	}

	new BreakingNewsOptionsPage();
}
