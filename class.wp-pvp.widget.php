<?php
defined('WP_PVP_VERSION') OR exit('No direct script access allowed');

class WP_PVP_widget {
	private static $initiated = false;

	public static function init() {
		if( !self::$initiated ) {
			self::$initiated = true;

			register_widget('WP_Widget_PostViews_Plus');
		}
	}
}

function is_selected($id, $check) {
	if( in_array($id, $check) ) {
		return ' selected="selected"';
	}
}

class WP_Widget_PostViews_Plus extends WP_Widget {
	public function __construct() {
		$widget_ops = array('description' => __('WP-PostViews plus views statistics', WP_PVP::$textdomain));
		parent::__construct('views-plus', __('Views Stats', WP_PVP::$textdomain), $widget_ops);

		add_action('save_post', array($this, 'flush_widget_cache'));
		add_action('deleted_post', array($this, 'flush_widget_cache'));
		add_action( 'switch_theme', array($this, 'flush_widget_cache') );
	}

	function widget($args, $instance) {
		$cache = wp_cache_get('widget_postviews_plus', 'widget');
		if( !is_array($cache) ) {
			$cache = array();
		}
		if( !isset($args['widget_id']) ) {
			$args['widget_id'] = $this->id;
		}
		if( isset($cache[$args['widget_id']]) ) {
			echo $cache[$args['widget_id']];
			return;
		}

		$title = apply_filters('widget_title', esc_attr($instance['title']));
		$template = $instance['template'];
		$type = esc_attr($instance['type']);
		$mode = esc_attr($instance['mode']);
		$withbot = esc_attr($instance['withbot']);
		$thumbnail_width = intval($instance['thumbnail_width']);
		$thumbnail_height = intval($instance['thumbnail_height']);
		$limit = intval($instance['limit']);
		$chars = intval($instance['chars']);
		$cat_ids = $instance['cat_ids'];
		if( !is_array($cat_ids) ) {
			$cat_ids = explode(',', $car_ids);
		}
		$tag_ids = explode(',', esc_attr($instance['tag_ids']));

		ob_start();
		
		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		echo '<ul>'."\n";
		switch($type) {
			case 'most_viewed':
				get_most_viewed($mode, $limit, $chars, true, $withbot, $template, $thumbnail_width, $thumbnail_height);
				break;
			case 'most_viewed_category':
				get_most_viewed_category($cat_ids, 'post', $limit, $chars, true, $withbot, $template, $thumbnail_width, $thumbnail_height);
				break;
			case 'most_viewed_tag':
				get_most_viewed_tag($tag_ids, 'post', $limit, $chars, true, $withbot, $template, $thumbnail_width, $thumbnail_height);
				break;
		}
		echo '</ul>'."\n";
		echo $args['after_widget'];

		$cache[$args['widget_id']] = ob_get_flush();
		wp_cache_set('widget_postviews_plus', $cache, 'widget');
	}

	function flush_widget_cache() {
		wp_cache_delete('widget_postviews_plus', 'widget');
	}

	function update($new_instance, $old_instance) {
		if( !isset($new_instance['submit']) ) {
			return false;
		}
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['template'] = trim($new_instance['template']);
		$instance['type'] = strip_tags($new_instance['type']);
		if( !in_array($instance['type'], array('most_viewed', 'most_viewed_category', 'most_viewed_tag')) ) {
			$instance['type'] = 'most_viewed';
		}
		$instance['mode'] = strip_tags($new_instance['mode']);
		if( !in_array($instance['mode'], array('both', 'post', 'page')) ) {
			$instance['mode'] = 'both';
		}
		$instance['withbot'] = ($new_instance['withbot'] == 1) ? 1 : 0;
		$instance['limit'] = intval($new_instance['limit']);
		if( $instance['limit'] <= 0 ) {
			$instance['limit'] = 10;
		}
		$instance['chars'] = intval($new_instance['chars']);
		$instance['thumbnail_width'] = intval($new_instance['thumbnail_width']);
		$instance['thumbnail_height'] = intval($new_instance['thumbnail_height']);
		if( $instance['limit'] <= 0 ) {
			$instance['limit'] = 100;
		}
		$instance['cat_ids'] = $new_instance['cat_ids'];
		if( !is_array($instance['cat_ids']) ) {
			$instance['cat_ids'] = array(1);
		}
		$instance['tag_ids'] = strip_tags($new_instance['tag_ids']);
		$this->flush_widget_cache();
		return $instance;
	}

	function form($instance) {
		global $wpdb;
		$instance = wp_parse_args(
			(array) $instance,
			array(
				'title' => __('Views', WP_PVP::$textdomain),
				'template' => WP_PVP::$options['most_viewed_template'],
				'thumbnail_width' => WP_PVP::$options['set_thumbnail_size_w'],
				'thumbnail_height' => WP_PVP::$options['set_thumbnail_size_h'],
				'type' => 'most_viewed',
				'mode' => 'both',
				'limit' => 10,
				'chars' => 100,
				'cat_ids' => '0',
				'tag_ids' => '0',
				'withbot' => '1'
			)
		);
		$title = esc_attr($instance['title']);
		$template = $instance['template'];
		$type = esc_attr($instance['type']);
		$mode = esc_attr($instance['mode']);
		$withbot = esc_attr($instance['withbot']);
		$thumbnail_width = intval($instance['thumbnail_width']);
		$thumbnail_height = intval($instance['thumbnail_height']);
		$limit = intval($instance['limit']);
		$chars = intval($instance['chars']);
		$cat_ids = $instance['cat_ids'];
		if( !is_array($cat_ids) ) {
			$cat_ids = explode(',', $car_ids);
		}
		$tag_ids = esc_attr($instance['tag_ids']);
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', WP_PVP::$textdomain); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('type'); ?>"><?php _e('Statistics Type:', WP_PVP::$textdomain); ?></label><br>
			<select name="<?php echo $this->get_field_name('type'); ?>" id="<?php echo $this->get_field_id('type'); ?>">
				<option value="most_viewed"<?php selected('most_viewed', $type); ?>><?php _e('Most Viewed', WP_PVP::$textdomain); ?></option>
				<option value="most_viewed_category"<?php selected('most_viewed_category', $type); ?>><?php _e('Most Viewed By Category', WP_PVP::$textdomain); ?></option>
				<option value="most_viewed_tag"<?php selected('most_viewed_tag', $type); ?>><?php _e('Most Viewed By Tag', WP_PVP::$textdomain); ?></option>
			</select>
		</p>
		<p id="<?php echo $this->get_field_id('mode'); ?>_p" <?php if( $type != 'most_viewed') { echo('style="display:none;"'); } ?>>
			<label for="<?php echo $this->get_field_id('mode'); ?>"><?php _e('Include Views From:', WP_PVP::$textdomain); ?></label>
			<select name="<?php echo $this->get_field_name('mode'); ?>" id="<?php echo $this->get_field_id('mode'); ?>">
				<option value="both"<?php selected('both', $mode); ?>><?php _e('Posts &amp; Pages', WP_PVP::$textdomain); ?></option>
				<option value="post"<?php selected('post', $mode); ?>><?php _e('Posts Only', WP_PVP::$textdomain); ?></option>
				<option value="page"<?php selected('page', $mode); ?>><?php _e('Pages Only', WP_PVP::$textdomain); ?></option>
			</select>
		</p>
		<p id="<?php echo $this->get_field_id('cat_ids'); ?>_p" <?php if( $type != 'most_viewed_category') { echo('style="display:none;"'); } ?>>
			<label for="<?php echo $this->get_field_id('cat_ids'); ?>"><?php _e('Category IDs:', WP_PVP::$textdomain); ?></label>
			<select name="<?php echo $this->get_field_name('cat_ids'); ?>[]" size="3" multiple="multiple" class="widefat" id="<?php echo $this->get_field_id('cat_ids'); ?>" style="height:auto;" >
				<?php
				$cats = get_categories(array(
					'orderby' => 'id',
					'hide_empty' => 0,
					'taxonomy' => 'category'
				));
				foreach( $cats AS $cat ) {
					echo('<option value="' . $cat->term_id . '"' . is_selected($cat->term_id, $cat_ids) . '>' . esc_html($cat->name) . '</option>');
				}
				?>
		        </select>
			<small><?php _e('Seperate mutiple categories with commas.', WP_PVP::$textdomain); ?></small>
		</p>
		<p id="<?php echo $this->get_field_id('tag_ids'); ?>_p" <?php if( $type != 'most_viewed_tag') { echo('style="display:none;"'); } ?>>
			<label for="<?php echo $this->get_field_id('tag_ids'); ?>"><?php _e('Tag IDs:', WP_PVP::$textdomain); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('tag_ids'); ?>" name="<?php echo $this->get_field_name('tag_ids'); ?>" type="text" value="<?php echo $tag_ids; ?>" />
			<small><?php _e('Seperate mutiple categories with commas.', WP_PVP::$textdomain); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('template'); ?>"><?php _e('Views Template:', WP_PVP::$textdomain); ?></label><br>
			<textarea name="<?php echo $this->get_field_name('template'); ?>" id="<?php echo $this->get_field_id('template'); ?>" class="widefat"><?php echo htmlspecialchars(stripslashes($template)); ?></textarea><br>
			<?php _e('Allowed Variables:', WP_PVP::$textdomain); ?> - %VIEW_COUNT% - %POST_TITLE% - %POST_EXCERPT% - %POST_CONTENT% - %POST_DATE% - %POST_URL% - %POST_THUMBNAIL%
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('No. Of Records To Show:', WP_PVP::$textdomain); ?></label>
			<input name="<?php echo $this->get_field_name('limit'); ?>" type="text" id="<?php echo $this->get_field_id('limit'); ?>" value="<?php echo $limit; ?>" size="4" maxlength="2" /><br>
			<label for="<?php echo $this->get_field_id('chars'); ?>"><?php _e('Maximum Post Title Length (Characters):', WP_PVP::$textdomain); ?></label>
			<input id="<?php echo $this->get_field_id('chars'); ?>" name="<?php echo $this->get_field_name('chars'); ?>" type="text" value="<?php echo $chars; ?>" size="4" />
			<small><?php _e('<strong>0</strong> to disable.', WP_PVP::$textdomain); ?> <?php _e(' Chinese characters to calculate the two words!', WP_PVP::$textdomain); ?></small><br>
			<?php _e('Size of post thumbnail: ', WP_PVP::$textdomain); ?>
			<label for="<?php echo $this->get_field_id('thumbnail_width'); ?>"><?php _e('Width: ', WP_PVP::$textdomain); ?></label>
			<input type="text" id="<?php echo $this->get_field_id('thumbnail_width'); ?>" name="<?php echo $this->get_field_id('thumbnail_width'); ?>" size="3" value="<?php echo $thumbnail_width; ?>" />
			<label for="<?php echo $this->get_field_id('thumbnail_height'); ?>"><?php _e('Height: ', WP_PVP::$textdomain); ?></label>
			<input type="text" id="<?php echo $this->get_field_id('thumbnail_height'); ?>" name="<?php echo $this->get_field_id('thumbnail_height'); ?>" size="3" value="<?php echo $thumbnail_height; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('withbot'); ?>"><?php _e('With BOT Views:', WP_PVP::$textdomain); ?></label>
			<select name="<?php echo $this->get_field_name('withbot'); ?>" id="<?php echo $this->get_field_id('withbot'); ?>">
				<option value="1"<?php selected('1', $withbot); ?>><?php _e('With BOT', WP_PVP::$textdomain); ?></option>
				<option value="0"<?php selected('0', $withbot); ?>><?php _e('Without BOT', WP_PVP::$textdomain); ?></option>
			</select>
		</p>
		<input type="hidden" id="<?php echo $this->get_field_id('submit'); ?>" name="<?php echo $this->get_field_name('submit'); ?>" value="1" />
		<script type="text/javascript">
		jQuery('#<?php echo $this->get_field_id('type'); ?>').change(function(){
			if(jQuery(this).val()=='most_viewed'){jQuery('#<?php echo $this->get_field_id('mode'); ?>_p').show();jQuery('#<?php echo $this->get_field_id('cat_ids'); ?>_p').hide();jQuery('#<?php echo $this->get_field_id('tag_ids'); ?>_p').hide();}
			if(jQuery(this).val()=='most_viewed_category'){jQuery('#<?php echo $this->get_field_id('mode'); ?>_p').hide();jQuery('#<?php echo $this->get_field_id('cat_ids'); ?>_p').show();jQuery('#<?php echo $this->get_field_id('tag_ids'); ?>_p').hide();}
			if(jQuery(this).val()=='most_viewed_tag'){jQuery('#<?php echo $this->get_field_id('mode'); ?>_p').hide();jQuery('#<?php echo $this->get_field_id('cat_ids'); ?>_p').hide();jQuery('#<?php echo $this->get_field_id('tag_ids'); ?>_p').show();}
		});
		</script>
		<?php
	}
}
