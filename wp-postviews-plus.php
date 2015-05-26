<?php
/*
Plugin Name: WP-PostViews Plus
Plugin URI: http://wwpteach.com/wp-postviews-plus
Description: Enables You To Display How Many Times A Post Had Been Viewed By User Or Bot.
Version: 2.0.1
Author: Richer Yang
Author URI: http://fantasyworld.idv.tw/
Text Domain: wp-postviews-plus
Domain Path: /languages
*/

function_exists('plugin_dir_url') OR exit('No direct script access allowed');

define('WP_PVP_VERSION', '2.0.1');
define('WP_PVP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_PVP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_PVP_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once(WP_PVP_PLUGIN_DIR . 'class.wp-pvp.php');
require_once(WP_PVP_PLUGIN_DIR . 'class.wp-pvp.widget.php');

register_activation_hook( __FILE__, array('WP_PVP', 'plugin_activation'));
register_deactivation_hook( __FILE__, array('WP_PVP', 'plugin_deactivation'));

if( is_admin() ) {
	load_plugin_textdomain(WP_PVP::$textdomain, false, dirname(WP_PVP_PLUGIN_BASENAME) . '/languages');
}
add_action('init', array('WP_PVP', 'init'));
add_action('widgets_init', array('WP_PVP_widget', 'init'));

function the_views($text_views = null, $display = true, $always = false) {
	if( $always || WP_PVP::should_views_display() ) {
		global $post;

		$post_views = intval(get_post_meta($post->ID, WP_PVP::$post_meta_views, true)) + intval(get_post_meta($post->ID, WP_PVP::$post_meta_botviews, true));
		if( $display ) {
			echo(wp_pvp_count_replace(WP_PVP::$options['template'], 'both', $post->ID, $post_views));
		} else {
			return $post_views;
		}
	}
	return false;
}

function the_user_views($text_views = null, $display = true, $always = false) {
	if( $always || WP_PVP::should_views_display() ) {
		global $post;

		$post_views = intval(get_post_meta($post->ID, WP_PVP::$post_meta_views, true));
		if( $display ) {
			echo(wp_pvp_count_replace(WP_PVP::$options['user_template'], 'user', $post->ID, $post_views));
		} else {
			return $post_views;
		}
	}
	return false;
}

function the_bot_views($text_views = null, $display = true, $always = false) {
	if( $always || WP_PVP::should_views_display() ) {
		global $post;

		$post_views = (int) get_post_meta($post->ID, WP_PVP::$post_meta_botviews, true);
		if( $display ) {
			echo(wp_pvp_count_replace(WP_PVP::$options['bot_template'], 'bot', $post->ID, $post_views));
		} else {
			return $post_views;
		}
	}
	return false;
}

function wp_pvp_count_replace($template, $type, $post_ID, $post_views) {
	if( defined('WP_CACHE') && WP_CACHE ) {
		WP_PVP::add_cache_stats('tv', $post_ID);
		switch( $type ) {
			case 'user':
				$template = str_replace('%VIEW_COUNT%', '<span class="wppvp_tuv_' . $post_ID . '">%VIEW_COUNT%</span>', $template);
				break;
			case 'bot':
				$template = str_replace('%VIEW_COUNT%', '<span class="wppvp_tbv_' . $post_ID . '">%VIEW_COUNT%</span>', $template);
				break;
			case 'both':
			default:
				$template = str_replace('%VIEW_COUNT%', '<span class="wppvp_tv_' . $post_ID . '">%VIEW_COUNT%</span>', $template);
				break;
		}
		$template = str_replace('%VIEW_COUNT%', '', $template);
	} else {
		$template = str_replace('%VIEW_COUNT%', number_format_i18n($post_views), $template);
	}
	return $template;
}

function get_totalviews_term($term_id = 1, $display = true, $with_bot = true, $type = '') {
	global $wpdb;
	$where = '';
	$inner_join = '';
	if( $term_id != 0 ) {
		if( is_array($term_id) ) {
			$term_id = array_map('intval', $term_id);
			$where = 'tt.term_id IN (' . implode(',', $term_id) . ') AND ';
		} else {
			$where = 'tt.term_id=' . intval($term_id) . ' AND ';
		}
		$inner_join = 'INNER JOIN ' . $wpdb->term_relationships . ' AS tr ON pm.post_id = tr.object_id'
			. ' INNER JOIN ' . $wpdb->term_taxonomy . ' AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = "' . $type . '"';
	}

	$add_word = get_totalviews_stats_word($term_id, $with_bot, $type);
	if( $with_bot ) {
		$total_views = $wpdb->get_var('SELECT SUM(IFNULL(CAST(pm.meta_value AS UNSIGNED), 0)) FROM ' . $wpdb->postmeta . ' AS pm ' . $inner_join
			. ' WHERE ' . $where . ' (pm.meta_key = "' . WP_PVP::$post_meta_views . '" OR pm.meta_key = "' . WP_PVP::$post_meta_botviews . '")');
		$template = str_replace('%VIEW_COUNT%', '<span id="wppvp_gt_' . $add_word . '">%VIEW_COUNT%</span>', WP_PVP::$options['template']);
	} else {
		$total_views = $wpdb->get_var('SELECT SUM(IFNULL(CAST(pm.meta_value AS UNSIGNED), 0)) FROM ' . $wpdb->postmeta . ' AS pm ' . $inner_join
			. ' WHERE ' . $where . ' pm.meta_key = "' . WP_PVP::$post_meta_views . '"');
		$template = str_replace('%VIEW_COUNT%', '<span id="wppvp_gt_' . $add_word . '">%VIEW_COUNT%</span>', WP_PVP::$options['user_template']);
	}
	$total_views = intval($total_views);
	if( $display ) {
		if( defined('WP_CACHE') && WP_CACHE ) {
			WP_PVP::add_cache_stats('gt', $term_id, $with_bot, $type);
			$template = str_replace('%VIEW_COUNT%', '', $template);
		} else {
			$template = str_replace('%VIEW_COUNT%', number_format_i18n($total_views), $template);
		}
		echo($template);
	} else {
		return $total_views;
	}
}

function get_totalviews($display = true, $with_bot = true) {
	return get_totalviews_term(0, $display, $with_bot, '');
}

function get_totalviews_category($category_id = 1, $display = true, $with_bot = true) {
	return get_totalviews_term($category_id, $display, $with_bot, 'category');
}

function get_totalviews_tag($tag_id = 1, $display = true, $with_bot = true) {
	return get_totalviews_term($tag_id, $display, $with_bot, 'post_tag');
}

function pp_snippet_text($text, $length = 0) {
	if( defined('MB_OVERLOAD_STRING') ) {
		$text = @html_entity_decode($text, ENT_QUOTES, get_option('blog_charset'));
		return htmlentities(mb_strimwidth($text, 0, $length, '...'), ENT_COMPAT, get_option('blog_charset'));
	} else {
		$text = @html_entity_decode($text, ENT_QUOTES, get_option('blog_charset'));
	 	if( strlen($text) > $length ) {
			return htmlentities(substr($text, 0, $length), ENT_COMPAT, get_option('blog_charset')) . '...';
	 	} else {
			return htmlentities($text, ENT_COMPAT, get_option('blog_charset'));
	 	}
	}
}

function get_totalviews_stats_word($id, $with_bot, $type) {
	$add_word = $with_bot ? 1 : 0;
	switch( $type ) {
		case 'category':
			$add_word .= 1;
			break;
		case 'post_tag':
			$add_word .= 2;
			break;
		default:
			$add_word .= 0;
			break;
	}
	if( is_array($id) ) {
		$add_word .= implode('-', $id);
	} else {
		$add_word .= $id;
	}
	return $add_word;
}

function views_post_excerpt($post_excerpt, $post_content, $post_password, $chars = 200) {
	if( !empty($post_password) ) {
		if(!isset($_COOKIE['wp-postpass_'.COOKIEHASH]) || $_COOKIE['wp-postpass_'.COOKIEHASH] != $post_password) {
			return __('There is no excerpt because this is a protected post.', 'wp-postviews-plus');
		}
	}
	if( empty($post_excerpt) ) {
		return pp_snippet_text(strip_tags($post_content), $chars);
	} else {
		return $post_excerpt;
	}
}

function wp_pvp_template_replace($template, $post, $chars, $thumbnail_width, $thumbnail_height, $with_bot) {
	$post_views = intval($post->views);
	$post_title = isset($post->post_title) ? $post->post_title : '';
	if( $chars > 0 ) {
		$post_title = pp_snippet_text($post_title, $chars);
	}
	if( current_theme_supports('post-thumbnails') ) {
		if( post_type_supports($post->post_type, 'thumbnail') ) {
			$thumbnail = get_the_post_thumbnail($post->ID, 'post-thumbnail');
			if( $thumbnail_width > 0 ) {
				$thumbnail = preg_replace('@ width="[0-9]*" @i', ' ', $thumbnail);
				$thumbnail = '<img width="' . $thumbnail_width . substr($thumbnail, 0, -4);
			}
			if( $thumbnail_height > 0 ) {
				$thumbnail = preg_replace('@ height="[0-9]*" @i', ' ', $thumbnail);
				$thumbnail = '<img $thumbnail_height="' . $thumbnail_height . substr($thumbnail, 0, -4);
			}
		}
	} else {
		$thumbnail = '';
	}
	$post_excerpt = views_post_excerpt($post->post_excerpt, $post->post_content, $post->post_password, $chars);
	$temp = stripslashes($template);
	$temp = wp_pvp_count_replace($temp, ($with_bot ? 'both' : 'user'), $post->ID, $post_views);
	$temp = str_replace("%POST_TITLE%", $post_title, $temp);
	$temp = str_replace("%POST_EXCERPT%", $post_excerpt, $temp);
	$temp = str_replace("%POST_CONTENT%", $post->post_content, $temp);
	$temp = str_replace("%POST_DATE%", mysql2date(get_option('date_format'), $post->post_date), $temp);
	$temp = str_replace("%POST_URL%", get_permalink($post->ID), $temp);
	$temp = str_replace("%POST_THUMBNAIL%", $thumbnail, $temp);
	return $temp;
}

function get_timespan_most_viewed_term($term_id = 1, $mode = null, $limit = 10, $chars = 0, $display = true, $with_bot = true, $days = 7, $type = '', $template, $thumbnail_width = -1, $thumbnail_height = -1) {
	global $wpdb;
	$output = '';

	if( $with_bot ) {
		$left_join = ' LEFT JOIN ' . $wpdb->postmeta . ' AS pm1 ON pm1.post_id=p.ID AND pm1.meta_key="views" LEFT JOIN ' . $wpdb->postmeta . ' AS pm2 ON pm2.post_id=p.ID AND pm2.meta_key="bot_views"';
		$views = '(IFNULL(CAST(pm1.meta_value AS UNSIGNED), 0) + IFNULL(CAST(pm2.meta_value AS UNSIGNED), 0))';
	} else {
		$left_join = ' LEFT JOIN ' . $wpdb->postmeta . ' AS pm1 ON pm1.post_id=p.ID AND pm1.meta_key="views"';
		$views = 'IFNULL(CAST(pm1.meta_value AS UNSIGNED), 0)';
	}
	$inner_join = '';
	if( is_array($term_id) ) {
		$inner_join = ' INNER JOIN ' . $wpdb->term_relationships . ' AS tr ON p.ID=tr.object_id INNER JOIN ' . $wpdb->term_taxonomy . ' AS tt ON tr.term_taxonomy_id=tt.term_taxonomy_id AND tt.taxonomy="' . $type . '"';
		$inner_join .= ' AND tt.term_id IN (' . implode(',', $term_id) . ')';
	} elseif ( $term_id > 0 ) {
		$inner_join = ' INNER JOIN ' . $wpdb->term_relationships . ' AS tr ON p.ID=tr.object_id INNER JOIN ' . $wpdb->term_taxonomy . ' AS tt ON tr.term_taxonomy_id=tt.term_taxonomy_id AND tt.taxonomy="' . $type . '"';
		$inner_join .= ' AND tt.term_id=' . intval($term_id);
	}
	if( $mode == 'post' ) {
		$where = 'p.post_type = "post"';
	} elseif( $mode == 'page' ) {
		$where = 'p.post_type = "page"';
	} else {
		$where = '(p.post_type = "post" OR p.post_type = "page")';
	}
	if( $days > 0 ) {
		$limit_date = time() - ($days * 86400);
		$limit_date = gmdate('Y-m-d', $limit_date);
		$where .= ' AND (left(p.post_date, 10) > "' . $limit_date . '" OR left(p.post_modified, 10) > "' . $limit_date . '")';
	}

	$most_viewed = $wpdb->get_results('SELECT DISTINCT p.ID, p.post_title, p.post_excerpt, p.post_content, post_password, p.post_date, ' . $views . ' AS views'
		. ' FROM ' . $wpdb->posts . ' AS p ' . $left_join . $inner_join
		. ' WHERE p.post_date<"' . current_time('mysql') . '" AND ' . $where . '  AND p.post_status="publish" AND p.post_password=""'
		. ' ORDER BY views DESC LIMIT ' . $limit);

	if( $most_viewed ) {
		if( empty($template) ) {
			$template = WP_PVP::$options['most_viewed_template'];
		}
		foreach( $most_viewed as $post ) {
			$output .= wp_pvp_template_replace($template, $post, $chars, $thumbnail_width, $thumbnail_height, $with_bot) . "\n";
		}
	} else {
		$output = '<li>' . __('N/A', 'wp-postviews-plus') . '</li>' . "\n";
	}
	if( $display ) {
		echo $output;
	} else {
		return $output;
	}
}

function get_timespan_most_viewed($mode = '', $limit = 10, $chars = 0, $display = true, $with_bot = true, $days = 7, $template = '', $thumbnail_width = -1, $thumbnail_height = -1) {
	return get_timespan_most_viewed_term(0, $mode, $limit, $chars, $display, $with_bot, $days, '', $template, $thumbnail_width, $thumbnail_height);
}

function get_most_viewed($mode = '', $limit = 10, $chars = 0, $display = true, $with_bot = true, $template = '', $thumbnail_width = -1, $thumbnail_height = -1) {
	return get_timespan_most_viewed_term(0, $mode, $limit, $chars, $display, $with_bot, 0, '', $template, $thumbnail_width, $thumbnail_height);
}

function get_timespan_most_viewed_category($category_id = 1, $mode = null, $limit = 10, $chars = 0, $display = true, $with_bot = true, $days = 7, $template = '', $thumbnail_width = -1, $thumbnail_height = -1) {
	return get_timespan_most_viewed_term($category_id, 'post', $limit, $chars, $display, $with_bot, $days, 'category', $template, $thumbnail_width, $thumbnail_height);
}

function get_most_viewed_category($category_id = 1, $mode = null, $limit = 10, $chars = 0, $display = true, $with_bot = true, $template = '', $thumbnail_width = -1, $thumbnail_height = -1) {
	return get_timespan_most_viewed_term($category_id, 'post', $limit, $chars, $display, $with_bot, 0, 'category', $template, $thumbnail_width, $thumbnail_height);
}

function get_timespan_most_viewed_tag($tag_id = 1, $mode = null, $limit = 10, $chars = 0, $display = true, $with_bot = true, $days = 7, $template = '', $thumbnail_width = -1, $thumbnail_height = -1) {
	return get_timespan_most_viewed_term($tag_id, 'post', $limit, $chars, $display, $with_bot, $days, 'post_tag', $template, $thumbnail_width, $thumbnail_height);
}

function get_most_viewed_tag($tag_id = 1, $mode = null, $limit = 10, $chars = 0, $display = true, $with_bot = true, $template = '', $thumbnail_width = -1, $thumbnail_height = -1) {
	return get_timespan_most_viewed_term($tag_id, 'post', $limit, $chars, $display, $with_bot, 0, 'post_tag', $template, $thumbnail_width, $thumbnail_height);
}