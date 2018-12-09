<?php
defined( 'ABSPATH' ) or die();

if( ! class_exists('BreakingNewsFrontend') ){

	/**
	 * Returns current active breaking news post if exists. Wrapper for BreakingNewsFrontend class get_breaking_post function.
	 */
	function get_breaking_post(){
		return BreakingNewsFrontend::get_breaking_post();
	}

	/**
	 * Class BreakingNewsFrontend
	 * @desc Class is responsible for displaying breaking news post.
	 */
	class BreakingNewsFrontend {

		public function __construct(){
			if( is_admin() )
				return false;

			add_action('wp_footer', [$this, 'css_code'], 10);
			add_action('wp_footer', [$this, 'js_code'], 10);
			add_shortcode('breaking_news', [$this, 'breakingnews_shortcode'] );
		}

		/**
		 * Returns current active breaking news post.
		 *
		 * @return WP_Post|bool
		 */
		public static function get_breaking_post(){
			$posts = get_posts([
				'meta_query' => [
					'relation' => 'OR',
					[	// Search for active breaking news post without expiration date
						 'relation' => 'AND',
						 [
							 'key'     => 'breaking_news_active',
							 'value'   => '1',
						 ],
						 [
							 'key'     => 'breaking_news_expire_active',
							 'value'   => '0'
						 ],
					],
					[	// Search for active breaking news post with not expired date
						 'relation' => 'AND',
						 [
							 'key'     => 'breaking_news_active',
							 'value'   => '1',
						 ],
						 [
							 'key'     => 'breaking_news_expire_active',
							 'value'   => '1',
						 ],
						 [
							 'key'    => 'breaking_news_expire_date',
							 'type'    => 'DATETIME',
							 'value'   => current_time('mysql'),
							 'compare' => '>'
						 ]
					],
				],
			]);
			// If something was found then return only the first post
			return $posts ? $posts[0] : false;
		}

		/**
		 * Returns breaking news post HTML
		 *
		 * @return bool|string
		 */
		public function generate_post_box_html(){
			$post = $this->get_breaking_post();
			if( ! $post )
				return false;

			// Get custom post title
			$post_title = get_post_meta( $post->ID, 'breaking_news_custom_title', true );
			// If custom post title not set then use defau;lt post title
			if( ! $post_title )
				$post_title = get_the_title($post);

			// Get custom title, text color, bg color (if were set in admin)
			$area_title = get_option('breakingnews_area_title');
			$text_color = sanitize_hex_color( get_option('breakingnews_text_color') );
			$bg_color = sanitize_hex_color( get_option('breakingnews_bg_color') );

			if( $bg_color ){
				$bg_color_html = 'style="background-color: '.esc_attr($bg_color).'"';
			}
			else{
				$bg_color_html = '';
			}
			if( $text_color ){
				$bg_text_color_html = 'style="color: '.esc_attr($text_color).'"';
			}
			else{
				$bg_text_color_html = '';
			}

			$out = '<div id="breaking_news" class="breaking_news_container" '.$bg_color_html.' >';
			$out .= '<div id="breaking_news_title" class="breaking_news_title" '.$bg_text_color_html.'>';
			$out .= esc_html($area_title).': ';
			$out .= '<a href="'.esc_url(get_permalink( $post )).'" id="breaking_news_title_link" '.$bg_text_color_html.' class="breaking_news_title_link">'.esc_html($post_title).'</a>';
			$out .= '</div>';
			$out .= '</div>';

			return $out;
		}

		/**
		 * Returns [breaking_news] shortcode content.
		 *
		 * @return bool|string
		 */
		function breakingnews_shortcode(){
			$post = $this->get_breaking_post();
			if( ! $post )
				return false;

			// If the same post page then no need to display it twice
			if( is_single($post) )
				return false;

			return $this->generate_post_box_html();
		}

		/**
		 * Javascript code to insert in the footer of the page. Called via wp_footer hook.
		 */
		public function js_code(){
			$post = $this->get_breaking_post();
			if( ! $post )
				return false;

			// If the same post page then no need to display it twice
			if( is_single($post) )
				return false;

			if( get_option('breakingnews_autoinsert') ) :
			?>
			<script type="text/javascript">
				jQuery(document).ready(function($){
					// If already inserted via shortcode then skip autoinsert feature.
					if( $('#breaking_news').length )
						return;
					var target = $('header:first');
					if( ! target.length )
						return false;
					var container = $('<?php echo $this->generate_post_box_html(); ?>');
					target.append(container);
				});
			</script>
			<?php
			endif;
		}

		/**
		 * CSS code to insert in the footer of the page. Some basic styles for displaying breaking news div. Called via wp_footer hook.
		 * Generated HTML uses these classes:
		 * 		.breaking_news_container - for the parent container
		 * 		.breaking_news_title - for area title
		 * 		.breaking_news_title_link - for <a> tag
		 */
		public function css_code(){
			?>
			<style type="text/css">
				.breaking_news_container{
					margin: 0;
					padding: 10px;
					display: inline-block;
					width: 100%;
					clear: both;
					font-size: 22px;
					text-align: center;
				}
			</style>
			<?php
		}

	}

	new BreakingNewsFrontend();
}
