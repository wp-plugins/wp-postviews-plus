<?php
/*
Plugin Name: WP-PostViews Plus
Plugin URI: http://fantasyworld.idv.tw/programs/wp_postviews_plus/
Description: Enables You To Display How Many Times A Post Had Been Viewed By User Or Bot.
Version: 1.1.14
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

define('ARRAY_CAT','**');

// Load WP-Config File If This File Is Called Directly
if( !function_exists('add_action') ) {
	$wp_root = '../../..';
	if( file_exists($wp_root . '/wp-load.php') ) {
		require_once($wp_root . '/wp-load.php');
	} else {
		require_once($wp_root . '/wp-config.php');
	}
}

// Function: Increment Post Views of AJAX
increment_views();
function increment_views() {
	if( isset($_GET['todowppvc']) ) {
	  $pv_option = get_option('PV+_option');
		$bot = wp_pp_check_bot($pv_option['getuseragent']) ? 'bot' : 'user';
		$type = htmlspecialchars($_GET['type']);
		$id = intval($_GET['id']);
		if( $id>0 && defined('WP_CACHE') && WP_CACHE) {
			$count_data = array($bot, $type, $id);
			postviews_increment_views($count_data);
			echo("\r\n");
		}
		exit();
	}
}

// Function: Calculate Post Views(real do)
function postviews_increment_views($count_data) {
	global $wpdb;
	switch( $count_data[1] ) {
		case 'post':
			$post_bot_views = intval(get_post_meta($count_data[2], 'bot_views', true));
			$post_views = intval(get_post_meta($count_data[2], 'views', true));
			if( $count_data[0]=='bot' ) {
				if( !update_post_meta($count_data[2], 'bot_views', ($post_bot_views+1)) ) {
					add_post_meta($count_data[2], 'bot_views', 1, true);
				}
				if( defined('WP_CACHE') && WP_CACHE ) {
				  echo('if(document.getElementById("wppvp_ptv")){document.getElementById("wppvp_ptv").innerHTML="' . ($post_bot_views+$post_views+1) . '";}');
				  echo('if(document.getElementById("wppvp_ptb")){document.getElementById("wppvp_ptb").innerHTML="' . ($post_bot_views+1) . '";}');
				}
			} else {
				if( !update_post_meta($count_data[2], 'views', ($post_views+1)) ) {
					add_post_meta($count_data[2], 'views', 1, true);
				}
				if( defined('WP_CACHE') && WP_CACHE ) {
				  echo('if(document.getElementById("wppvp_ptv")){document.getElementById("wppvp_ptv").innerHTML="' . ($post_bot_views+$post_views+1) . '";}');
				  echo('if(document.getElementById("wppvp_ptu")){document.getElementById("wppvp_ptu").innerHTML="' . ($post_views+1) . '";}');
				}
			}
			break;
		case 'index':
		case 'cat':
		case 'tag':
			$add_name = $count_data[1] . '_' .  $count_data[2];
			$views = get_option('PV+_views');
			if( isset($views[$count_data[0]][$add_name]) ) {
				$views[$count_data[0]][$add_name] += 1;
			} else {
				$views[$count_data[0]][$add_name] = 1;
			}
			update_option('PV+_views', $views);
			break;
	}
	if( defined('WP_CACHE') && WP_CACHE ) {
	  $views = get_option('PV+_views');
		$total_views = $wpdb->get_var('SELECT SUM(IFNULL(CAST(meta_value AS UNSIGNED), 0)) FROM '.$wpdb->postmeta.' WHERE meta_key = "views" OR meta_key = "bot_views"');
		echo('if(document.getElementById("wppvp_tv1")){document.getElementById("wppvp_tv1").innerHTML="' . ($total_views+array_sum($views['user'])+array_sum($views['bot'])) . '";}');
		echo('if(document.getElementById("wppvp_tv2")){document.getElementById("wppvp_tv2").innerHTML="' . ($total_views) . '";}');
		$total_views = $wpdb->get_var('SELECT SUM(IFNULL(CAST(meta_value AS UNSIGNED), 0)) FROM '.$wpdb->postmeta.' WHERE meta_key = "views"');
		echo('if(document.getElementById("wppvp_tv3")){document.getElementById("wppvp_tv3").innerHTML="' . ($total_views+array_sum($views['user'])) . '";}');
		echo('if(document.getElementById("wppvp_tv4")){document.getElementById("wppvp_tv4").innerHTML="' . ($total_views) . '";}');
	}
}

// Function: WP-PostViews Plus Option Menu
add_action('admin_menu', 'postviews_plus_option');
function postviews_plus_option() {
	if (function_exists('add_options_page')) {
		// Loading language file...
		$currentLocale = get_locale();
		if( !empty($currentLocale) ) {
			$moFile = dirname(__FILE__).'/postviews_plus-'.$currentLocale.'.mo';
			if( @file_exists($moFile) && is_readable($moFile) ) {
				load_textdomain('postviews_plus', $moFile);
			}
		}
		add_options_page('WP-PostViews Plus', 'PostViews+', 'manage_options', 'wp-postviews-plus/postviews_plus_admin.php');
	}
}

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

function wp_pp_check_bot($add) {
	$useragent = strtolower(trim($_SERVER['HTTP_USER_AGENT']));
	$bot = false;
	if ( preg_match('/^((mozilla)|(opera))/', $useragent) ) {
		$botAgent = s2a(get_option('PV+_botagent'));
		if( function_exists('preg_match') ) {
			$regex = '/(' . implode($botAgent, ')|(') . ')/';
			$bot = preg_match($regex, $useragent);
		}
	} else {
		$bot = true;
	}
	if( !$bot && $add==1 ) {
		$PV_useragent = s2a(get_option('PV+_useragent'));
		if( !in_array($useragent, $PV_useragent) ) {
			$PV_useragent[] = $useragent;
			update_option('PV+_useragent', a2s($PV_useragent));
		}
	}
	return $bot;
}

function wp_pp_snippet_chars($text, $length = 0) {
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

add_action('wp_head', 'process_postviews');
// Function: Calculate Post Views(Pre do)
function process_postviews() {
	global $post;
	static $postid = array();
	if(!wp_is_post_revision($post)) {
		$pv_option = get_option('PV+_option');
		if( !is_user_logged_in() || $pv_option['userlogoin']==1 ) {
			if( is_single() || is_page() ) {
				if( !in_array($post->ID, $postid) ) {
					$postid[] = $post->ID;
					if( wp_pp_check_bot($pv_option['getuseragent']) ) {
						$count_data = array('bot', 'post', $post->ID);
					} else {
						$count_data = array('user', 'post', $post->ID);
					}
				}
			} elseif( is_home() ) {
				if( wp_pp_check_bot($pv_option['getuseragent']) ) {
					$count_data = array('bot', 'index', 1);
				} else {
					$count_data = array('user', 'index', 1);
				}
			} elseif( is_category() ) {
				if( wp_pp_check_bot($pv_option['getuseragent']) ) {
					$count_data = array('bot', 'cat', intval(get_query_var('cat')));
				} else {
					$count_data = array('user', 'cat', intval(get_query_var('cat')));
				}
			} elseif( is_tag() ) {
				if( wp_pp_check_bot($pv_option['getuseragent']) ) {
					$count_data = array('bot', 'tag', intval(get_query_var('tag_id')));
				} else {
					$count_data = array('user', 'tag', intval(get_query_var('tag_id')));
				}
			} else {
				if( wp_pp_check_bot($pv_option['getuseragent']) ) {
					$count_data = array('bot', $_SERVER['REQUEST_URI'], 1);
				} else {
					$count_data = array('user',$_SERVER['REQUEST_URI'] , 1);
				}
			}
			if( !empty($count_data) ) {
				if(defined('WP_CACHE') && WP_CACHE) {
					echo("\n" . '<!-- Start Of Script Generated By WP-PostViews 1.50 -->' . "\n");
					wp_print_scripts('jquery');
					echo('<script type="text/javascript">' . "\n");
					echo('/* <![CDATA[ */' . "\n");
					echo("jQuery.ajax({type:'GET',url:'" . plugins_url('wp-postviews-plus/postviews_plus.php') . "',data:'todowppvc=&type=" . $count_data[1] . "&id=" . $count_data[2] . "',cache:false,dataType:'script'});");
					echo('/* ]]> */' . "\n");
					echo('</script>' . "\n");
					echo('<!-- End Of Script Generated By WP-PostViews 1.50 -->' . "\n");
				} else {
					postviews_increment_views($count_data);
				}
			}
		}
	}
}

// Function: Display The Post Total Views
function the_views($text_views=' Views', $display=true) {
	global $post;
	$post_views = intval(get_post_meta($post->ID, 'views', true)) + intval(get_post_meta($post->ID, 'bot_views', true));
	if( $display ) {
		echo('<span id="wppvp_ptv">' . number_format($post_views) . '</span>' . $text_views);
		return ;
	} else {
		return $post_views;
	}
}

// Function: Display The Post User Views
function the_user_views($text_views=' User Views', $display=true) {
	global $post;
	$post_user_views = intval(get_post_meta($post->ID, 'views', true));
	if( $display ) {
		echo('<span id="wppvp_ptu">' . number_format($post_user_views) . '</span>' . $text_views);
		return ;
	} else {
		return $post_user_views;
	}
}

// Function: Display The Post Bot Views
function the_bot_views($text_views=' Bot Views', $display=true) {
	global $post;
	$post_bot_views = intval(get_post_meta($post->ID, 'bot_views', true));
	if( $display ) {
		echo('<span id="wppvp_ptb">' . number_format($post_bot_views) . '</span>' . $text_views);
		return ;
	} else {
		return $post_bot_views;
	}
}

// Function: Display Most Viewed Page/Post
function get_most_viewed($mode='', $limit=10, $chars=0, $display=true, $with_bot=true) {
	global $wpdb;
	$output_format = get_option('PV+_option');
	if( $mode=='post' ) {
		$where = 'p.post_type = "post"';
	} elseif( $mode=='page' ) {
		$where = 'p.post_type = "page"';
	} else {
		$where = '(p.post_type = "post" OR p.post_type = "page")';
	}
	if( $with_bot ) {
		$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, (IFNULL(CAST(pm1.meta_value AS UNSIGNED), 0) + IFNULL(CAST(pm2.meta_value AS UNSIGNED), 0)) AS views FROM '.$wpdb->posts.' AS p LEFT JOIN '.$wpdb->postmeta.' AS pm1 ON pm1.post_id = p.ID AND pm1.meta_key = "views" LEFT JOIN '.$wpdb->postmeta.' AS pm2 ON pm2.post_id = p.ID AND pm2.meta_key = "bot_views" WHERE p.post_date < "'.current_time('mysql').'" AND p.post_status = "publish" AND '.$where.' AND p.post_password = "" ORDER BY views DESC LIMIT '.$limit);
		$output_format = $output_format['mostviewsbot'];
	} else {
		$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, IFNULL(CAST(pm.meta_value AS UNSIGNED), 0) AS views FROM '.$wpdb->posts.' AS p LEFT JOIN '.$wpdb->postmeta.' AS pm ON pm.post_id = p.ID AND pm.meta_key = "views" WHERE p.post_date < "'.current_time('mysql').'" AND p.post_status = "publish" AND '.$where.' AND p.post_password = "" ORDER BY views DESC LIMIT '.$limit);
		$output_format = $output_format['mostviewsnobot'];
	}
	if( $most_views ) {
		$output = '';
		foreach($most_views as $most_view) {
			$post_title = $most_view->post_title;
			$post_views = number_format(intval($most_view->views));
			$post_link = '<a href="'.get_permalink($most_view->ID).'">'.wp_pp_snippet_chars($post_title, $chars).'</a>';
			$post_date = get_the_time(get_option('date_format'), $most_view->ID);
			$output .= '<li>'.sprintf($output_format, $post_views, $post_link, $post_date).'</li>'."\n";
		}
	} else {
		$output = '<li>'.__('N/A', 'wp-postviews_plus').'</li>';
	}
	if( $display ) {
		echo($output);
		return ;
	} else {
		return $output;
	}
}

// Function: Display Most Viewed Page/Post By Category ID
function get_most_viewed_category($category_id=1, $mode='', $limit=10, $chars=0, $display=true, $with_bot=true) {
	global $wpdb, $post;
	$output_format = get_option('PV+_option');
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
		$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, (IFNULL(CAST(pm1.meta_value AS UNSIGNED), 0) + IFNULL(CAST(pm2.meta_value AS UNSIGNED), 0)) AS views FROM '.$wpdb->posts.' AS p LEFT JOIN '.$wpdb->postmeta.' AS pm1 ON pm1.post_id = p.ID AND pm1.meta_key = "views" LEFT JOIN '.$wpdb->postmeta.' AS pm2 ON pm2.post_id = p.ID AND pm2.meta_key = "bot_views" LEFT JOIN '.$wpdb->term_relationships.' AS tr ON tr.object_id = p.ID WHERE p.post_date < "'.current_time('mysql').'" AND p.post_status = "publish" AND '.$category_sql.' AND '.$where.' AND p.post_password = "" ORDER BY views DESC LIMIT '.$limit);
		$output_format = $output_format['mostviewsbot'];
	} else {
		$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, IFNULL(CAST(pm.meta_value AS UNSIGNED), 0) AS views FROM '.$wpdb->posts.' AS p LEFT JOIN '.$wpdb->postmeta.' AS pm ON pm.post_id = p.ID AND pm.meta_key = "views" LEFT JOIN '.$wpdb->term_relationships.' AS tr ON tr.object_id = p.ID WHERE p.post_date < "'.current_time('mysql').'" AND p.post_status = "publish" AND '.$category_sql.' AND '.$where.' AND p.post_password = "" ORDER BY views DESC LIMIT '.$limit);
		$output_format = $output_format['mostviewsnobot'];
	}
	if( $most_views ) {
		$output = '';
		foreach ($most_views as $most_view) {
			$post_title = $most_view->post_title;
			$post_views = number_format(intval($most_view->views));
			$post_link = '<a href="'.get_permalink($most_view->ID).'">'.wp_pp_snippet_chars($post_title, $chars).'</a>';
			$post_date = get_the_time(get_option('date_format'), $most_view->ID);
			$output .= '<li>'.sprintf($output_format, $post_views, $post_link, $post_date).'</li>'."\n";
		}
	} else {
		$output = '<li>'.__('N/A', 'wp-postviews_plus').'</li>'."\n";
	}
	if($display) {
		echo($output);
		return ;
	} else {
		return $output;
	}
}


// Function: Display Most Viewed Page/Post In Last Days
// Added by Paolo Tagliaferri (http://www.vortexmind.net - webmaster@vortexmind.net)
function get_timespan_most_viewed($mode='', $limit=10, $days=7, $display=true, $with_bot=true, $chars=0) {
	global $wpdb;
	$limit_date = current_time('timestamp') - ($days*86400);
	$limit_date = date('Y-m-d H:i:s', $limit_date);
	$output_format = get_option('PV+_option');
	if( $mode=='post' ) {
		$where = 'p.post_type = "post"';
	} elseif( $mode=='page' ) {
		$where = 'p.post_type = "page"';
	} else {
		$where = '(p.post_type = "post" OR p.post_type = "page")';
	}
	if( $with_bot ) {
		$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, (IFNULL(CAST(pm1.meta_value AS UNSIGNED), 0) + IFNULL(CAST(pm2.meta_value AS UNSIGNED), 0)) AS views FROM '.$wpdb->posts.' AS p LEFT JOIN '.$wpdb->postmeta.' AS pm1 ON pm1.post_id = p.ID AND pm1.meta_key = "views" LEFT JOIN '.$wpdb->postmeta.' AS pm2 ON pm2.post_id = p.ID AND pm2.meta_key = "bot_views" WHERE p.post_date < "'.current_time('mysql').'" AND p.post_date > "'.$limit_date.'" AND p.post_status = "publish" AND '.$where.' AND p.post_password = "" ORDER BY views DESC LIMIT '.$limit);
		$output_format = $output_format['mostviewsbot'];
	} else {
		$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, IFNULL(CAST(pm.meta_value AS UNSIGNED), 0) AS views FROM '.$wpdb->posts.' AS p LEFT JOIN '.$wpdb->postmeta.' AS pm ON pm.post_id = p.ID AND pm.meta_key = "views" WHERE p.post_date < "'.current_time('mysql').'" AND p.post_date > "'.$limit_date.'" AND p.post_status = "publish" AND '.$where.' AND p.post_password = "" ORDER BY views DESC LIMIT '.$limit);
		$output_format = $output_format['mostviewsnobot'];
	}
	if( $most_views ) {
		$output = '';
		foreach($most_views as $most_view) {
			$post_title = $most_view->post_title;
			$post_views = number_format(intval($most_view->views));
			$post_link = '<a href="'.get_permalink($most_view->ID).'">'.wp_pp_snippet_chars($post_title, $chars).'</a>';
			$post_date = get_the_time(get_option('date_format'), $most_view->ID);
			$output .= '<li>'.sprintf($output_format, $post_views, $link, $post_date).'</li>'."\n";
		}
	} else {
		$output = '<li>'.__('N/A', 'postviews_plus').'</li>'."\n";
	}
	if( $display ) {
		echo($output);
		return ;
	} else {
		return $output;
	}
}

// Function: Get TimeSpan Most Viewed By Category
function get_timespan_most_viewed_cat($category_id=1, $mode='', $limit=10, $days=7, $display=true, $with_bot=true, $chars=0) {
	global $wpdb, $post;	
	$limit_date = current_time('timestamp') - ($days*86400);
	$limit_date = date('Y-m-d H:i:s', $limit_date);
	$output_format = get_option('PV+_option');
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
		$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, (IFNULL(CAST(pm1.meta_value AS UNSIGNED), 0) + IFNULL(CAST(pm2.meta_value AS UNSIGNED), 0)) AS views FROM '.$wpdb->posts.' AS p LEFT JOIN '.$wpdb->postmeta.' AS pm1 ON pm1.post_id = p.ID AND pm1.meta_key = "views" LEFT JOIN '.$wpdb->postmeta.' AS pm2 ON pm2.post_id = p.ID AND pm2.meta_key = "bot_views" LEFT JOIN '.$wpdb->term_relationships.' AS tr ON tr.object_id = p.ID WHERE p.post_date < "'.current_time('mysql').'" AND p.post_date > "'.$limit_date.'" AND p.post_status = "publish" AND '.$category_sql.' AND '.$where.' AND p.post_password = "" ORDER BY views DESC LIMIT '.$limit);
		$output_format = $output_format['mostviewsbot'];
	} else {
		$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, IFNULL(CAST(pm.meta_value AS UNSIGNED), 0) AS views FROM '.$wpdb->posts.' AS p LEFT JOIN '.$wpdb->postmeta.' AS pm ON pm.post_id = p.ID AND pm.meta_key = "views" LEFT JOIN '.$wpdb->term_relationships.' AS tr ON tr.object_id = p.ID WHERE p.post_date < "'.current_time('mysql').'" AND p.post_date > "'.$limit_date.'" AND p.post_status = "publish" AND '.$category_sql.' AND '.$where.' AND p.post_password = "" ORDER BY views DESC LIMIT '.$limit);
		$output_format = $output_format['mostviewsnobot'];
	}
	if( $most_views ) {
		$output = '';
		foreach ($most_views as $most_view) {
			$post_title = $most_view->post_title;
			$post_views = number_format(intval($most_view->views));
			$post_link = '<a href="'.get_permalink($most_view->ID).'">'.wp_pp_snippet_chars($post_title, $chars).'</a>';
			$post_date = get_the_time(get_option('date_format'), $most_view->ID);
			$output .= '<li>'.sprintf($output_format, $post_views, $post_link, $post_date).'</li>'."\n";
		}
	} else {
		$output = '<li>'.__('N/A', 'postviews_plus').'</li>'."\n";
	}
	if( $display ) {
		echo($output);
		return ;
	} else {
		return $output;
	}
}

// Function: Display Total Views
function get_totalviews($display=true, $with_bot=true, $with_post=true) {
	global $wpdb;
	$views = get_option('PV+_views');
	if( $with_bot ) {
		$total_views = $wpdb->get_var('SELECT SUM(IFNULL(CAST(meta_value AS UNSIGNED), 0)) FROM '.$wpdb->postmeta.' WHERE meta_key = "views" OR meta_key = "bot_views"');
		if( $with_post ) {
			$total_views += array_sum($views['user']) + array_sum($views['bot']);
		  $type = 1;
		} else {
		  $type = 2;
		}
	} else {
		$total_views = $wpdb->get_var('SELECT SUM(IFNULL(CAST(meta_value AS UNSIGNED), 0)) FROM '.$wpdb->postmeta.' WHERE meta_key = "views"');
		if( $with_post ) {
			$total_views += array_sum($views['user']);
			$type = 3;
		} else {
		  $type = 4;
		}
	}
 if( $display ) {
		echo('<span id="wppvp_tv' . $type . '">' . number_format($total_views) . '</span>');
		return ;
	} else {
		return $total_views;
	}
}

// Function: Post Views Options
add_action('activate_wp-postviews/wp-postviews.php', 'postviews_plus_add');
function postviews_plus_add() {
	global $wpdb;
	$botAgent = array(
		'bot',
		'spider',
		'slurp');
	$pv_option = array(
		'getuseragent'=>0,
		'userlogoin'=>0,
		'reportbot'=>1,
		'mostviewsbot'=>'%3$s : %2$s - %1$s '.__('Views', 'postviews_plus'),
		'mostviewsnobot'=>'%3$s : %2$s - %1$s '.__('User Views', 'postviews_plus'));
	$views = array(
		'user'=>array(),
		'bot'=>array());
	add_option('PV+_botagent', a2s($botAgent));
	add_option('PV+_option', $pv_option);
	add_option('PV+_useragent', '');
	add_option('PV+_views', $views);
}
?>