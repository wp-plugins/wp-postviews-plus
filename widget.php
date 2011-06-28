<?php
class WP_Widget_PostViews_Plus extends WP_Widget {
	function WP_Widget_PostViews_Plus() {
		$widget_ops = array('description' => __('WP-PostViews plus views statistics', 'wp-postviews-plus'));
		$this->WP_Widget('views-plus', __('Views Stats', 'wp-postviews-plus'), $widget_ops);
	}
	function widget($args, $instance) {
		extract($args);
		$title = apply_filters('widget_title', esc_attr($instance['title']));
		$type = esc_attr($instance['type']);
		$mode = esc_attr($instance['mode']);
		$withbot = esc_attr($instance['withbot']);
		$limit = intval($instance['limit']);
		$chars = intval($instance['chars']);
		$cat_ids = explode(',', esc_attr($instance['cat_ids']));
		$tag_ids = explode(',', esc_attr($instance['tag_ids']));
		echo $before_widget.$before_title.$title.$after_title;
		echo '<ul>'."\n";
		switch($type) {
			case 'most_viewed':
				get_most_viewed($mode, $limit, $chars, true, $withbot);
				break;
			case 'most_viewed_category':
				get_most_viewed_category($cat_ids, $mode, $limit, $chars, true, $withbot);
				break;
			case 'most_viewed_tag':
				get_most_viewed_tag($tag_ids, $mode, $limit, $chars, true, $withbot);
				break;
		}
		echo '</ul>'."\n";
		echo $after_widget;
	}
	function update($new_instance, $old_instance) {
		if( !isset($new_instance['submit']) ) {
			return false;
		}
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['type'] = strip_tags($new_instance['type']);
		$instance['mode'] = strip_tags($new_instance['mode']);
		$instance['withbot'] = strip_tags($new_instance['withbot']);
		$instance['limit'] = intval($new_instance['limit']);
		$instance['chars'] = intval($new_instance['chars']);
		$instance['cat_ids'] = strip_tags($new_instance['cat_ids']);
		$instance['tag_ids'] = strip_tags($new_instance['tag_ids']);
		return $instance;
	}
	function form($instance) {
		global $wpdb;
		$instance = wp_parse_args((array) $instance, array('title' => __('Views', 'wp-postviews-plus'), 'type' => 'most_viewed', 'mode' => 'both', 'limit' => 10, 'chars' => 200, 'cat_ids' => '0', 'tag_ids' => '0', 'withbot' => '1'));
		$title = esc_attr($instance['title']);
		$type = esc_attr($instance['type']);
		$mode = esc_attr($instance['mode']);
		$withbot = esc_attr($instance['withbot']);
		$limit = intval($instance['limit']);
		$chars = intval($instance['chars']);
		$cat_ids = esc_attr($instance['cat_ids']);
		$tag_ids = esc_attr($instance['tag_ids']);
?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'wp-postviews-plus'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('type'); ?>"><?php _e('Statistics Type:', 'wp-postviews-plus'); ?>
				<select name="<?php echo $this->get_field_name('type'); ?>" id="<?php echo $this->get_field_id('type'); ?>" class="widefat">
					<option value="most_viewed"<?php selected('most_viewed', $type); ?>><?php _e('Most Viewed', 'wp-postviews-plus'); ?></option>
					<option value="most_viewed_category"<?php selected('most_viewed_category', $type); ?>><?php _e('Most Viewed By Category', 'wp-postviews-plus'); ?></option>
					<option value="most_viewed_tag"<?php selected('most_viewed_tag', $type); ?>><?php _e('Most Viewed By Tag', 'wp-postviews-plus'); ?></option>
				</select>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('mode'); ?>"><?php _e('Include Views From:', 'wp-postviews-plus'); ?>
				<select name="<?php echo $this->get_field_name('mode'); ?>" id="<?php echo $this->get_field_id('mode'); ?>" class="widefat">
					<option value="both"<?php selected('both', $mode); ?>><?php _e('Posts &amp; Pages', 'wp-postviews-plus'); ?></option>
					<option value="post"<?php selected('post', $mode); ?>><?php _e('Posts Only', 'wp-postviews-plus'); ?></option>
					<option value="page"<?php selected('page', $mode); ?>><?php _e('Pages Only', 'wp-postviews-plus'); ?></option>
				</select>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('No. Of Records To Show:', 'wp-postviews-plus'); ?> <input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="text" value="<?php echo $limit; ?>" /></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('chars'); ?>"><?php _e('Maximum Post Title Length (Characters):', 'wp-postviews-plus'); ?> <input class="widefat" id="<?php echo $this->get_field_id('chars'); ?>" name="<?php echo $this->get_field_name('chars'); ?>" type="text" value="<?php echo $chars; ?>" /></label><br />
			<small><?php _e('<strong>0</strong> to disable.', 'wp-postviews-plus'); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('withbot'); ?>"><?php _e('With BOT Views:', 'wp-postviews-plus'); ?>
				<select name="<?php echo $this->get_field_name('withbot'); ?>" id="<?php echo $this->get_field_id('withbot'); ?>" class="widefat">
					<option value="1"<?php selected('1', $withbot); ?>><?php _e('With BOT', 'wp-postviews-plus'); ?></option>
					<option value="0"<?php selected('0', $withbot); ?>><?php _e('Without BOT', 'wp-postviews-plus'); ?></option>
				</select>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('cat_ids'); ?>"><?php _e('Category IDs:', 'wp-postviews-plus'); ?> <span style="color: red;">*</span> <input class="widefat" id="<?php echo $this->get_field_id('cat_ids'); ?>" name="<?php echo $this->get_field_name('cat_ids'); ?>" type="text" value="<?php echo $cat_ids; ?>" /></label><br />
			<small><?php _e('Seperate mutiple categories with commas.', 'wp-postviews-plus'); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('tag_ids'); ?>"><?php _e('Tag IDs:', 'wp-postviews-plus'); ?> <span style="color: red;">*</span> <input class="widefat" id="<?php echo $this->get_field_id('tag_ids'); ?>" name="<?php echo $this->get_field_name('tag_ids'); ?>" type="text" value="<?php echo $tag_ids; ?>" /></label><br />
			<small><?php _e('Seperate mutiple categories with commas.', 'wp-postviews-plus'); ?></small>
		</p>
		<p style="color: red;">
			<small><?php _e('* If you are not using any category or tag statistics, you can ignore it.', 'wp-postviews-plus'); ?></small>
		</p>
		<input type="hidden" id="<?php echo $this->get_field_id('submit'); ?>" name="<?php echo $this->get_field_name('submit'); ?>" value="1" />
<?php
	}
}
function pp_widget_views_init() {
	register_widget('WP_Widget_PostViews_Plus');
}
add_action('widgets_init', 'pp_widget_views_init');
?>