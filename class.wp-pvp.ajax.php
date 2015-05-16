<?php
defined('WP_PVP_VERSION') OR exit('No direct script access allowed');

class WP_PVP_ajax {
	private static $initiated = false;

	public static function init() {
		if( !self::$initiated ) {
			self::$initiated = true;

			add_action('wp_ajax_wp_pvp_count', array('WP_PVP_ajax', 'wp_pvp_count'));
			add_action('wp_ajax_nopriv_wp_pvp_count', array('WP_PVP_ajax', 'wp_pvp_count'));
		}
	}

	public static function wp_pvp_count() {
		global $wpdb;

		$post_id = (int) $_GET['post_id'];
		WP_PVP::add_views($post_id);

		$json = array();
		$data = $wpdb->get_row('SELECT * FROM ' . $wpdb->postviews_plus . ' WHERE count_id = "' . esc_attr($_GET['count_id']) . '"');
		if( $data ) {
			if( !empty($data->tv) ) {
				$views = $wpdb->get_results('SELECT pmu.post_id, IFNULL(pmu.meta_value, 0) AS user_views, IFNULL(pmb.meta_value, 0) AS bot_views FROM ' . $wpdb->postmeta . ' AS pmu'
					. ' LEFT JOIN ' . $wpdb->postmeta . ' AS pmb ON pmb.post_id = pmu.post_id AND pmb.meta_key = "' . WP_PVP::$post_meta_botviews . '"'
					. ' WHERE pmu.meta_key ="' . WP_PVP::$post_meta_views . '" AND pmu.post_id IN (' . $data->tv . ')');
				foreach( $views AS $view ) {
					$json['wppvp_tv_' . $view->post_id] = number_format_i18n($view->user_views + $view->bot_views);
					$json['wppvp_tuv_' . $view->post_id] = number_format_i18n($view->user_views);
					$json['wppvp_tbv_' . $view->post_id] = number_format_i18n($view->bot_views);
				}
			}
			if( !empty($data->gt) ) {
				$gts = explode(',', $data->gt);
				foreach( $gts AS $gt ) {
					$with_bot = (bool) substr($gt, 0, 1);
					switch( substr($gt, 1, 1) ) {
						case 1:
							$type = 'category';
							break;
						case 2:
							$type = 'post_tag';
							break;
						default:
							$type = '';
							break;
					}
					$term_id = explode('-', substr($gt, 2));
					if( count($term_id) == 1 ) {
						$term_id = $term_id[0];
					}
					$json['wppvp_gt_' . $gt] = number_format_i18n(get_totalviews_term($term_id, false, $with_bot, $type));
				}
			}
		}

		wp_send_json($json);
	}
}