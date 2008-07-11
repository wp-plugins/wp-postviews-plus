<?php
/*
Plugin Name: WP-PostViews Plus
Plugin URI: http://fantasyworld.idv.tw/programs/wp_postviews_plus/
Description: Enables You To Display How Many Times A Post Had Been Viewed By User Or Bot.
Version: 1.1.8
Author: Richer Yang
Author URI: http://fantasyworld.idv.tw/
*/

/**************************************************
* OLD HEADER
* Plugin Name: WP-PostViews
* Plugin URI: http://www.lesterchan.net/portfolio/programming.php
* Description: Enables You To Display How Many Time A Post Had Been Viewed.
* Version: 1.02
* Author: GaMerZ
* Author URI: http://www.lesterchan.net
**************************************************/

define('ARRAY_CAT','****');
define('IS_WP25', version_compare($wp_version, '2.4', '>=') );

function s2a($s) {
	if( is_array($s) ) {
		return $s;
	} else {
		return explode(ARRAY_CAT,$s);
	}
}
function a2s($a) {
	return implode(ARRAY_CAT,$a);
}

if( is_admin() ) {
	add_action('activate_postviews_plus/postviews_plus.php', 'postviews_plus_add');
	add_action('admin_menu', 'postviews_plus_option');
} else {
	add_filter('the_content', 'process_postviews');
	add_filter('the_excerpt', 'process_postviews');
}

// Function: Calculate Post Views
function process_postviews($content) {
	global $id,$user_ID;
	static $postid = array();
	if( !in_array($id, $postid) ) {
		$postid[] = $id;
		$pv_option = get_settings('PV+_option');
		if( !$user_ID || $pv_option['userlogoin']==1 ) {
			if( is_single() || is_page() || $pv_option['indexviews']==1 ) {
				$useragent = trim($_SERVER['HTTP_USER_AGENT']);
				$bot = false;
				if( strlen($useragent)>5 ) {
					$botAgent = s2a(get_settings('PV+_botagent'));
					foreach($botAgent as $lookfor) {
						if( !empty($lookfor) ) {
							if( stristr($useragent, $lookfor)!==false ) {
								$bot = true;
								break;
							}
						}
					}
				} else {
					$bot = true;
				}
				if( $bot ) {
					$post_bot_views = get_post_custom($id);
					$post_bot_views = intval($post_bot_views['bot_views'][0]);
					if( !update_post_meta($id, 'bot_views', ($post_bot_views+1)) ) {
						add_post_meta($id, 'bot_views', 1, true);
					}
				} else {
					$post_views = get_post_custom($id);
					$post_views = intval($post_views['views'][0]);
					if( !update_post_meta($id, 'views', ($post_views+1)) ) {
						add_post_meta($id, 'views', 1, true);
					}
					if( $pv_option['getuseragent']==1 ) {
						$PV_useragent = s2a(get_settings('PV+_useragent'));
						if( !in_array($useragent, $PV_useragent) ) {
							$PV_useragent[] = $useragent;
							update_option('PV+_useragent', a2s($PV_useragent));
						}
					}
				}
			}
		}
	}
	return $content;
}

// Function: Display The Post Total Views
function the_views($text_views=' Views', $display=true) {
	$post_views = intval(post_custom('views')) + intval(post_custom('bot_views'));
	if( $display ) {
		echo number_format($post_views).$text_views;
	} else {
		return $post_views;
	}
}

// Function: Display The Post User Views
function the_user_views($text_views=' User Views', $display=true) {
	$post_user_views = intval(post_custom('views'));
	if( $display ) {
		echo number_format($post_user_views).$text_views;
	} else {
		return $post_user_views;
	}
}

// Function: Display The Post Bot Views
function the_bot_views($text_views=' Bot Views', $display=true) {
	$post_bot_views = intval(post_custom('bot_views'));
	if( $display ) {
		echo number_format($post_bot_views).$text_views;
	} else {
		return $post_bot_views;
	}
}


// Function: Display Most Viewed Page/Post
if( !function_exists('get_most_viewed') ) {
	function get_most_viewed($mode='', $limit=10, $chars=0, $display=true, $with_bot=true) {
		global $wpdb, $post;
		$output_format = get_settings('PV+_option');
		if( $mode=='post' ) {
			$where = 'p.post_type = "post"';
		} elseif( $mode=='page' ) {
			$where = 'p.post_type = "page"';
		} else {
			$where = '(p.post_type = "post" OR p.post_type = "page")';
		}
		if( $with_bot ) {
			$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, (CAST(pm1.meta_value AS UNSIGNED) + CAST(pm2.meta_value AS UNSIGNED)) AS views FROM '.$wpdb->posts.' AS p LEFT JOIN '.$wpdb->postmeta.' AS pm1 ON pm1.post_id = p.ID AND pm1.meta_key = "views" LEFT JOIN '.$wpdb->postmeta.' AS pm2 ON pm2.post_id = p.ID AND pm2.meta_key = "bot_views" WHERE p.post_date < "'.current_time('mysql').'" AND p.post_status = "publish" AND '.$where.' AND p.post_password = "" ORDER BY views DESC LIMIT '.$limit);
			$output_format = $output_format['mostviewsbot'];
		} else {
			$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, CAST(pm.meta_value AS UNSIGNED) AS views FROM '.$wpdb->posts.' AS p LEFT JOIN '.$wpdb->postmeta.' AS pm ON pm.post_id = p.ID AND pm.meta_key = "views" WHERE p.post_date < "'.current_time('mysql').'" AND p.post_status = "publish" AND '.$where.' AND p.post_password = "" ORDER BY views DESC LIMIT '.$limit);
			$output_format = $output_format['mostviewsnobot'];
		}
		if( $most_views ) {
			$output = '';
			foreach($most_views as $most_view) {
				$post_title = $most_view->post_title;
				$post_views = number_format(intval($most_view->views));
				$link = '<a href="'.get_permalink($most_view->ID).'">'.snippet_chars($post_title, $chars).'</a>';
				$output .= '<li>'.sprintf($output_format,$post_views,$link).'</li>'."\n";
			}
		} else {
			$output = '<li>'.__('N/A', 'wp-postviews_plus').'</li>';
		}
		if( $display ) {
			echo $output;
		} else {
			return $output;
		}
	}
}

### Function: Display Most Viewed Page/Post By Category ID
if(!function_exists('get_most_viewed_category')) {
	function get_most_viewed_category($category_id=1, $mode='', $limit=10, $chars=0, $display=true, $with_bot=true) {
		global $wpdb, $post;
		$output_format = get_settings('PV+_option');
		if( $mode=='post' ) {
			$where = 'p.post_type = "post"';
		} elseif( $mode=='page' ) {
			$where = 'p.post_type = "page"';
		} else {
			$where = '(p.post_type = "post" OR p.post_type = "page")';
		}
		if( $category_id=='auto' ) {
			$category_sql = 'tr.term_taxonomy_id IN (';
			$category = get_the_category($post->ID);
			foreach( $category AS $cate )	{
				$category_sql .= $cate->term_taxonomy_id.',';
			}
			$category_sql = substr($category_sql, 0, -1);
			$category_sql .= ')';
		} else {
			if( is_array($category_id) ) {
				$term_id = 'term_id IN ('.join(',', $category_id).')';
			} else {
				$term_id = 'term_id = '.$category_id;
			}
			$ttid = $wpdb->get_col("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE $term_id");
			$category_sql = 'tr.term_taxonomy_id IN ('.join(',', $ttid).')';
		}
		if( $with_bot ) {
			$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, (CAST(pm1.meta_value AS UNSIGNED) + CAST(pm2.meta_value AS UNSIGNED)) AS views FROM '.$wpdb->posts.' AS p LEFT JOIN '.$wpdb->postmeta.' AS pm1 ON pm1.post_id = p.ID AND pm1.meta_key = "views" LEFT JOIN '.$wpdb->postmeta.' AS pm2 ON pm2.post_id = p.ID AND pm2.meta_key = "bot_views" LEFT JOIN '.$wpdb->term_relationships.' AS tr ON tr.object_id = p.ID WHERE p.post_date < "'.current_time('mysql').'" AND p.post_status = "publish" AND '.$category_sql.' AND '.$where.' AND p.post_password = "" ORDER BY views DESC LIMIT '.$limit);
			$output_format = $output_format['mostviewsbot'];
		} else {
			$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, CAST(pm.meta_value AS UNSIGNED) AS views FROM '.$wpdb->posts.' AS p LEFT JOIN '.$wpdb->postmeta.' AS pm ON pm.post_id = p.ID AND pm.meta_key = "views" LEFT JOIN '.$wpdb->term_relationships.' AS tr ON tr.object_id = p.ID WHERE p.post_date < "'.current_time('mysql').'" AND p.post_status = "publish" AND '.$category_sql.' AND '.$where.' AND p.post_password = "" ORDER BY views DESC LIMIT '.$limit);
			$output_format = $output_format['mostviewsnobot'];
		}
		if( $most_views ) {
			$output = '';
			foreach ($most_views as $most_view) {
				$post_title = $most_view->post_title;
				$post_views = number_format(intval($most_view->views));
				$link = '<a href="'.get_permalink($most_view->ID).'">'.snippet_chars($post_title, $chars).'</a>';
				$output .= '<li>'.sprintf($output_format,$post_views,$link).'</li>'."\n";
			}
		} else {
			$output = '<li>'.__('N/A', 'wp-postviews_plus').'</li>'."\n";
		}
		if($display) {
			echo $output;
		} else {
			return $output;
		}
	}
}


// Function: Display Most Viewed Page/Post In Last Days
// Added by Paolo Tagliaferri (http://www.vortexmind.net - webmaster@vortexmind.net)
function get_timespan_most_viewed($mode='', $limit=10, $days=7, $display=true, $with_bot=true, $chars=0) {
	global $wpdb, $post;
	$limit_date = current_time('timestamp') - ($days*86400);
	$limit_date = date('Y-m-d H:i:s', $limit_date);
	$output_format = get_settings('PV+_option');
	if( $mode=='post' ) {
		$where = 'p.post_type = "post"';
	} elseif( $mode=='page' ) {
		$where = 'p.post_type = "page"';
	} else {
		$where = '(p.post_type = "post" OR p.post_type = "page")';
	}
	if( $with_bot ) {
		$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, (CAST(pm1.meta_value AS UNSIGNED) + CAST(pm2.meta_value AS UNSIGNED)) AS views FROM '.$wpdb->posts.' AS p LEFT JOIN '.$wpdb->postmeta.' AS pm1 ON pm1.post_id = p.ID AND pm1.meta_key = "views" LEFT JOIN '.$wpdb->postmeta.' AS pm2 ON pm2.post_id = p.ID AND pm2.meta_key = "bot_views" WHERE p.post_date < "'.current_time('mysql').'" AND p.post_date > "'.$limit_date.'" AND p.post_status = "publish" AND '.$where.' AND p.post_password = "" ORDER BY views DESC LIMIT '.$limit);
		$output_format = $output_format['mostviewsbot'];
	} else {
		$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, CAST(pm.meta_value AS UNSIGNED) AS views FROM '.$wpdb->posts.' AS p LEFT JOIN '.$wpdb->postmeta.' AS pm ON pm.post_id = p.ID AND pm.meta_key = "views" WHERE p.post_date < "'.current_time('mysql').'" AND p.post_date > "'.$limit_date.'" AND p.post_status = "publish" AND '.$where.' AND p.post_password = "" ORDER BY views DESC LIMIT '.$limit);
		$output_format = $output_format['mostviewsnobot'];
	}
	if( $most_views ) {
		$output = '';
		foreach($most_views as $most_view) {
			$post_title = $most_view->post_title;
			$post_views = number_format(intval($most_view->views));
			$link = '<a href="'.get_permalink($most_view->ID).'">'.snippet_chars($post_title, $chars).'</a>';
			$output .= '<li>'.sprintf($output_format,$post_views,$link).'</li>'."\n";
		}
	} else {
		$output = '<li>'.__('N/A', 'postviews_plus').'</li>'."\n";
	}
	if( $display ) {
		echo $output;
	} else {
		return $output;
	}
}

### Function: Get TimeSpan Most Viewed By Category
function get_timespan_most_viewed_cat($category_id=1, $mode='', $limit=10, $days=7, $display=true, $with_bot=true, $chars=0) {
	global $wpdb, $post;	
	$limit_date = current_time('timestamp') - ($days*86400);
	$limit_date = date('Y-m-d H:i:s', $limit_date);
	$output_format = get_settings('PV+_option');
	if( $mode=='post' ) {
		$where = 'p.post_type = "post"';
	} elseif( $mode=='page' ) {
		$where = 'p.post_type = "page"';
	} else {
		$where = '(p.post_type = "post" OR p.post_type = "page")';
	}
	if( $category_id=='auto' ) {
		$category_sql = 'tr.term_taxonomy_id IN (';
		$category = get_the_category($post->ID);
		foreach( $category AS $cate )	{
			$category_sql .= $cate->term_taxonomy_id.',';
		}
		$category_sql = substr($category_sql, 0, -1);
		$category_sql .= ')';
	} else {
		if( is_array($category_id) ) {
			$term_id = 'term_id IN ('.join(',', $category_id).')';
		} else {
			$term_id = 'term_id = '.$category_id;
		}
		$ttid = $wpdb->get_col("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE $term_id");
		$category_sql = 'tr.term_taxonomy_id IN ('.join(',', $ttid).')';
	}
	if( $with_bot ) {
		$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, (CAST(pm1.meta_value AS UNSIGNED) + CAST(pm2.meta_value AS UNSIGNED)) AS views FROM '.$wpdb->posts.' AS p LEFT JOIN '.$wpdb->postmeta.' AS pm1 ON pm1.post_id = p.ID AND pm1.meta_key = "views" LEFT JOIN '.$wpdb->postmeta.' AS pm2 ON pm2.post_id = p.ID AND pm2.meta_key = "bot_views" LEFT JOIN '.$wpdb->term_relationships.' AS tr ON tr.object_id = p.ID WHERE p.post_date < "'.current_time('mysql').'" AND p.post_date > "'.$limit_date.'" AND p.post_status = "publish" AND '.$category_sql.' AND '.$where.' AND p.post_password = "" ORDER BY views DESC LIMIT '.$limit);
		$output_format = $output_format['mostviewsbot'];
	} else {
		$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, CAST(pm.meta_value AS UNSIGNED) AS views FROM '.$wpdb->posts.' AS p LEFT JOIN '.$wpdb->postmeta.' AS pm ON pm.post_id = p.ID AND pm.meta_key = "views" LEFT JOIN '.$wpdb->term_relationships.' AS tr ON tr.object_id = p.ID WHERE p.post_date < "'.current_time('mysql').'" AND p.post_date > "'.$limit_date.'" AND p.post_status = "publish" AND '.$category_sql.' AND '.$where.' AND p.post_password = "" ORDER BY views DESC LIMIT '.$limit);
		$output_format = $output_format['mostviewsnobot'];
	}
	if( $most_views ) {
		$output = '';
		foreach ($most_views as $most_view) {
			$post_title = $most_view->post_title;
			$post_views = number_format(intval($most_view->views));
			$link = '<a href="'.get_permalink($most_view->ID).'">'.snippet_chars($post_title, $chars).'</a>';
			$output .= '<li>'.sprintf($output_format,$post_views,$link).'</li>'."\n";
		}
	} else {
		$output = '<li>'.__('N/A', 'postviews_plus').'</li>'."\n";
	}
	if( $display ) {
		echo $output;
	} else {
		return $output;
	}
}

// Function: Display Total Views
if( !function_exists('get_totalviews') ) {
	function get_totalviews($display=true, $with_bot=true) {
		global $wpdb;
		if( $with_bot ) {
			$total_views = $wpdb->get_var('SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM '.$wpdb->postmeta.' WHERE meta_key = "views" OR meta_key = "bot_views"');
		} else {
			$total_views = $wpdb->get_var('SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM '.$wpdb->postmeta.' WHERE meta_key = "views"');
		}
		if( $display ) {
			echo number_format($total_views);
		} else {
			return $total_views;
		}
	}
}

// Function: Snippet Characters
if( !function_exists('snippet_chars') ) {
	function snippet_chars($text, $length = 0) {
		if( function_exists('mb_internal_encoding') ) {
			mb_internal_encoding(get_bloginfo('charset'));
			if( $length == 0 ) {
				return $text;
			} else if( mb_strlen($text) > $length ) {
				return mb_substr($text,0,$length).' ...';
			} else {
				return $text;
			}
		} else {
			if( $length == 0 ) {
				return $text;
			} else if( strlen($text) > $length ) {
				return substr($text,0,$length).' ...';
			} else {
				return $text;
			}
		}
	}
}

// Function: Add Option Value
function postviews_plus_add() {
	global $wpdb;
	$botAgent = array('bot','spider','validator','google');
	$pv_option = array('getuseragent'=>0, 'userlogoin'=>0, 'indexviews'=>0, 'reportbot'=>1, 'mostviewsbot'=>'%2$s - %1$s '.__('Views', 'postviews_plus'), 'mostviewsnobot'=>'%2$s - %1$s '.__('User Views', 'postviews_plus'));
	add_option('PV+_botagent', a2s($botAgent), 'WP-PostViews Plus bot useragant');
	add_option('PV+_option', $pv_option, 'WP-PostViews Plus Option');
	add_option('PV+_useragent', '', 'WP-PostViews Plus get user useragant');
}
function postviews_plus_option() {
	if( function_exists('add_options_page') ) {
		// Loading language file...
		$currentLocale = get_locale();
		if( !empty($currentLocale) ) {
			$moFile = dirname(__FILE__).'/postviews_plus-'.$currentLocale.'.mo';
			if( @file_exists($moFile) && is_readable($moFile) ) {
				load_textdomain('postviews_plus', $moFile);
			}
		}
		include dirname (__FILE__).'/postviews_plus_admin.php';
		if( IS_WP25 ) {
			add_options_page('WP-PostViews Plus', 'PostViews+', 'manage_options', dirname(__FILE__), 'postviews_plus_option_page_25');
		} else {
			add_options_page('WP-PostViews Plus', 'PostViews+', 'manage_options', dirname(__FILE__), 'postviews_plus_option_page');
		}
	}
}
?>