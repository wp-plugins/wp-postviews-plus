<?php
/*
Plugin Name: WP-PostViews Plus
Plugin URI: http://fantasyworld.idv.tw/programs/wp_postviews_plus/
Description: Enables You To Display How Many Times A Post Had Been Viewed By User Or Bot.
Version: 1.1.23
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

// Load WP-Config File If This File Is Called Directly
if( !function_exists('add_action') ) {
	$wp_root = './../../..';
	if( file_exists($wp_root . '/wp-load.php') ) {
		require_once($wp_root . '/wp-load.php');
	} else {
		require_once($wp_root . '/wp-config.php');
	}
}

define('ARRAY_CAT','**');

function s2a($arr_string) {
	if( is_array($arr_string) ) {
		return $arr_string;
	} else {
		if( strlen($arr_string)>0 ) {
			if( strpos($arr_string, ARRAY_CAT) ) {
				return explode(ARRAY_CAT, $arr_string);
			} else {
				return unserialize(htmlspecialchars_decode($arr_string));
			}
		} else {
			return array();
		}
	}
}
function a2s($arr) {
	if( is_array($arr) ) {
		return htmlspecialchars(serialize($arr));
	} else {
		return $arr;
	}
}

class wppvp{
	var $botAgent;
	var $pv_option;
	var $views;

	var $count_id;
	var $count_type;
	var $count_bot;
	var $did_id;
	var $cache_stats;

	function wppvp () {
		$this->botAgent = array(
			'now' => s2a(get_option('PV+_botagent')),
			'def' => array(
				'bot',
				'spider',
				'slurp'));
		$this->pv_option = array(
			'now' => get_option('PV+_option'),
			'def' => array(
				'getuseragent' => 0,
				'userlogoin' => 0,
				'reportbot' => 1,
				'mostviewsbot' => '%3$s : %2$s - %1$s ' . __('Views', 'postviews_plus'),
				'mostviewsnobot' => '%3$s : %2$s - %1$s ' . __('User Views', 'postviews_plus'),
				'iptime' => 120));
		$this->views = array(
			'now' => get_option('PV+_views'),
			'def' => array(
				'user' => array('indet_1' => 0),
				'bot' => array('indet_1' => 0)));
		$this->did_id = array();
		$this->cache_stats = true;

		if( isset($_GET['todowppvp']) && defined('WP_CACHE') && WP_CACHE ) {
			$this->add_count_data();
			if( $_GET['todowppvp']==='add' ) {
				$this->increment_views();
			}
			$this->change_views();
			echo("\r\n");
		}
	}
	function is_bot($do_add=true) {
		$useragent = strtolower(trim($_SERVER['HTTP_USER_AGENT']));
		$bot = false;
		if ( preg_match('/((mozilla\/)|(opera\/))/', $useragent) ) {
			if( count($this->botAgent['now'])>0 ) {
				$regex = '/(' . str_replace('/', '\/', implode($this->botAgent['now'], ')|(')) . ')/';
				$bot = preg_match($regex, $useragent);
			}
		} else {
			$bot = true;
		}
		if( !$do_add && $bot && $this->pv_option['now']['getuseragent']==1 ) {
			$PV_useragent = s2a(get_option('PV+_useragent'));
			if( !in_array($useragent, $PV_useragent) ) {
				$PV_useragent[] = $useragent;
				update_option('PV+_useragent', a2s($PV_useragent));
			}
		}
		return $bot;
	}
	function add_count_data() {
		if( isset($_GET['todowppvp']) && defined('WP_CACHE') && WP_CACHE ) {
			$this->count_bot = $this->is_bot() ? 'bot' : 'user';
			$this->count_type = htmlspecialchars(trim($_GET['type']));
			$this->count_id = htmlspecialchars(trim($_GET['id']));
		} else {
			$this->count_bot = $this->is_bot(false) ? 'bot' : 'user';
			if( is_single() || is_page() ) {
				global $post;
				$this->count_type = 'post';
				$this->count_id = $post->ID;
			} elseif( is_home() ) {
				$this->count_type = 'index';
				$this->count_id = intval(get_query_var('paged'));
				if( $this->count_id==0 ) {
					$this->count_id = 1;
				}
			} elseif( is_category() ) {
				$this->count_type = 'cat';
				$this->count_id = intval(get_query_var('paged'));
				if( $this->count_id==0 ) {
					$this->count_id = 1;
				}
				$this->count_id = intval(get_query_var('cat')) . '_' . $this->count_id;
			} elseif( is_tag() ) {
				$this->count_type = 'tag';
				$this->count_id = intval(get_query_var('paged'));
				if( $this->count_id==0 ) {
					$this->count_id = 1;
				}
				$this->count_id = intval(get_query_var('tag_id')) . '_' . $this->count_id;
			} else {
				$this->count_type = $_SERVER['REQUEST_URI'];
				$this->count_id = 1;
			}
		}
	}
	function increment_views() {
		if( !in_array($this->count_id, $this->did_id) ) {
			global $wpdb;
			$time = time();
			$this->did_id[] = $this->count_id;
			$ip = preg_replace('/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR']);
			$iptime = $this->pv_option['now']['iptime'] * 60;
			$sql = 'SELECT look_ip, look_ip_time FROM ' . $wpdb->prefix . 'postviewsplus WHERE count_type="' . $this->count_type . '" AND count_id="' . $this->count_id . '"';
			$look_data = $wpdb->get_row($sql);
			if( $look_data==null ) {
				$sql = 'INSERT INTO ' . $wpdb->prefix . 'postviewsplus (count_type, count_id, look_ip, look_ip_time) VALUES ("' . $this->count_type . '", "' . $this->count_id . '", "", "' . ($time+$iptime*1.5) . '")';
				$wpdb->query($sql);
			}
			$look_ip = s2a($look_data->look_ip);
			if( $look_data->look_ip_time<$time ) {
				$time_check = $time - $iptime * 1.6;
				foreach( $look_ip AS $i => $t ) {
					if( $t<$time_check ) {
						unset($look_ip[$i]);
					}
				}
				$sql = 'UPDATE ' . $wpdb->prefix . 'postviewsplus SET look_ip_time="' . ($time+$iptime*1.5) . '" WHERE count_type="' . $this->count_type . '" AND count_id="' . $this->count_id . '"';
				$wpdb->query($sql);
			}
			$do_increment_views = false;
			if( !isset($look_ip[$ip]) ) {
				$do_increment_views = true;
			} elseif( $look_ip[$ip]<$time ) {
				$do_increment_views = true;
			}
			$look_ip[$ip] = $time + $iptime;
			$sql = 'UPDATE ' . $wpdb->prefix . 'postviewsplus SET look_ip="' . a2s($look_ip) . '" WHERE count_type="' . $this->count_type . '" AND count_id="' . $this->count_id . '"';
			$wpdb->query($sql);
			if( $do_increment_views ) {
				switch( $this->count_type ) {
					case 'post':
						if( $this->count_bot=='bot' ) {
							$post_bot_views = intval(get_post_meta($this->count_id, 'bot_views', true));
							if( !update_post_meta($this->count_id, 'bot_views', ($post_bot_views+1)) ) {
								add_post_meta($this->count_id, 'bot_views', 1, true);
							}
						} else {
							$post_views = intval(get_post_meta($this->count_id, 'views', true));
							if( !update_post_meta($this->count_id, 'views', ($post_views+1)) ) {
								add_post_meta($this->count_id, 'views', 1, true);
							}
						}
						break;
					case 'index':
					case 'cat':
					case 'tag':
						$add_name = $this->count_type . '_' . $this->count_id;
						if( isset($views[$this->count_bot][$add_name]) ) {
							$this->views['now'][$this->count_bot][$add_name] += 1;
						} else {
							$this->views['now'][$this->count_bot][$add_name] = 1;
						}
						update_option('PV+_views', $this->views['now']);
						break;
				}
			}
		}
	}
	function change_views() {
		global $wpdb;
	 	$sql = 'SELECT * FROM ' . $wpdb->prefix . 'postviewsplus WHERE count_type="' . $this->count_type . '" AND count_id="' . $this->count_id . '"';
		$data = $wpdb->get_row($sql);
		if( $data ) {
			if( $data->tv!='' ) {
				$sql = 'SELECT pmu.post_id, IFNULL(pmu.meta_value, 0) AS user_views, IFNULL(pmb.meta_value, 0) AS bot_views FROM ' . $wpdb->postmeta . ' AS pmu LEFT JOIN ' . $wpdb->postmeta . ' AS pmb ON pmb.post_id=pmu.post_id AND pmb.meta_key="bot_views" WHERE pmu.meta_key="views" AND pmu.post_id IN (' . $data->tv . ')';
				$views = $wpdb->get_results($sql);
				foreach( $views AS $view ) {
					echo('if(document.getElementById("wppvp_tv_' . $view->post_id . '")){document.getElementById("wppvp_tv_' . $view->post_id . '").innerHTML="' . ($view->user_views + $view->bot_views) . '";}');
					echo('if(document.getElementById("wppvp_tuv_' . $view->post_id . '")){document.getElementById("wppvp_tuv_' . $view->post_id . '").innerHTML="' . ($view->user_views) . '";}');
					echo('if(document.getElementById("wppvp_tbv_' . $view->post_id . '")){document.getElementById("wppvp_tbv_' . $view->post_id . '").innerHTML="' . ($view->bot_views) . '";}');
				}
			}
			if( $data->gt!='' ) {
				$gt = explode(',', $data->gt);
				if( in_array(1, $gt) || in_array(2, $gt) ) {
					$total_views = $wpdb->get_var('SELECT SUM(IFNULL(CAST(meta_value AS UNSIGNED), 0)) FROM ' . $wpdb->postmeta . ' WHERE meta_key = "views" OR meta_key = "bot_views"');
					if( in_array(1, $gt) ) {
						echo('document.getElementById("wppvp_gt_1").innerHTML="' . ($total_views) . '";');
					}
					if( in_array(2, $gt) ) {
						echo('document.getElementById("wppvp_gt_2").innerHTML="' . ($total_views+array_sum($this->views['now']['user'])+array_sum($this->views['now']['bot'])) . '";');
					}
				} else {
					$total_views = $wpdb->get_var('SELECT SUM(IFNULL(CAST(meta_value AS UNSIGNED), 0)) FROM ' . $wpdb->postmeta . ' WHERE meta_key = "views"');
					if( in_array(3, $gt) ) {
						echo('document.getElementById("wppvp_gt_3").innerHTML="' . ($total_views) . '";');
					}
					if( in_array(4, $gt) ) {
						echo('document.getElementById("wppvp_gt_4").innerHTML="' . ($total_views+array_sum($this->views['now']['user'])+array_sum($this->views['now']['bot'])) . '";');
					}
				}
			}
		}
	}
	function add_cache_stats($addin) {
		global $wpdb, $post;
		if( $this->cache_stats ) {
			$sql = 'UPDATE ' . $wpdb->prefix . 'postviewsplus SET tv="", gt="" WHERE count_type="' . $this->count_type . '" AND count_id="' . $this->count_id . '"';
			$wpdb->query($sql);
			$this->cache_stats = false;
		}
		$sql = 'SELECT * FROM ' . $wpdb->prefix . 'postviewsplus WHERE count_type="' . $this->count_type . '" AND count_id="' . $this->count_id . '"';
		$data = $wpdb->get_row($sql);
		if( $data ) {
			switch( $addin ) {
				case 'tv':
					if( $data->tv=='' ) {
						$temp = array($post->ID);
					} else {
						$temp = explode(',', $data->tv);
						if( !in_array($post->ID, $temp) ) {
							$temp[] = $post->ID;
						}
					}
					$sql = 'UPDATE ' . $wpdb->prefix . 'postviewsplus SET tv="' . implode(',', $temp) . '"WHERE count_type="' . $this->count_type . '" AND count_id="' . $this->count_id . '"';
					break;
				case 'gt1':
				case 'gt2':
				case 'gt3':
				case 'gt4':
					if( count($temp)==0 ) {
						$temp = array($addin[2]);
					} else {
						$temp = explode(',', $data->gt);
						$temp[] = $addin[2];
					}
					$sql = 'UPDATE ' . $wpdb->prefix . 'postviewsplus SET gt="' . implode(',', $temp) . '" WHERE count_type="' . $this->count_type . '" AND count_id="' . $this->count_id . '"';
					break;
			}
		} else {
			switch( $addin ) {
				case 'tv':
					$sql = 'INSERT INTO ' . $wpdb->prefix . 'postviewsplus (count_type, count_id, tv) VALUES ("' . $this->count_type . '", "' . $this->count_id . '", "' . $post->ID . '")';
					break;
				case 'gt1':
				case 'gt2':
				case 'gt3':
				case 'gt4':
					$sql = 'INSERT INTO ' . $wpdb->prefix . 'postviewsplus (count_type, count_id, gt) VALUES ("' . $this->count_type . '", "' . $this->count_id . '", "' . $addin[2] . '")';
					break;
			}
		}
		$wpdb->query($sql);
	}
	function update() {
		$this->botAgent['now'] = s2a(get_option('PV+_botagent'));
 		$this->pv_option['now'] = get_option('PV+_option');
		$this->views['now'] = get_option('PV+_views');
	}
}

$pv_data = new wppvp;

function wp_snippet_chars($text, $length = 0) {
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
		add_options_page('WP-PostViews Plus', 'PostViews+', 'manage_options', dirname(__FILE__).'/postviews_plus_admin.php');
	}
}

// Function: Calculate Post Views
add_action('loop_start', 'process_postviews');
function process_postviews() {
	global $post, $pv_data;
	if( !wp_is_post_revision($post) ) {
		$pv_data->add_count_data();
		echo("\n" . '<!-- Start Of Script Generated By WP-PostViews Plus -->' . "\n");
		wp_print_scripts('jquery');
		echo('<script type="text/javascript">' . "\n");
		echo('/* <![CDATA[ */' . "\n");
		if( !is_user_logged_in() || $pv_data->pv_option['now']['userlogoin']==1 ) {
			if( defined('WP_CACHE') && WP_CACHE ) {
				echo("jQuery.ajax({type:'GET',url:'" . plugins_url('wp-postviews-plus/postviews_plus.php') . "',data:'todowppvp=add&type=" . $pv_data->count_type . "&id=" . $pv_data->count_id . "',cache:false,dataType:'script'});" . "\n");
				if( $pv_data->count_bot=='bot' ) {
					$pv_data->increment_views();
				}
			} else {
				$pv_data->increment_views();
			}
		} else {
			echo("jQuery.ajax({type:'GET',url:'" . plugins_url('wp-postviews-plus/postviews_plus.php') . "',data:'todowppvp=&type=" . $pv_data->count_type . "&id=" . $pv_data->count_id . "',cache:false,dataType:'script'});" . "\n");
		}
		echo('/* ]]> */' . "\n");
		echo('</script>' . "\n");
		echo('<!-- End Of Script Generated By WP-PostViews Plus -->' . "\n");
	}
}

// Function: Display The Post Total Views
function the_views($text_views=' Views', $display=true) {
	global $post;
	$post_views = intval(get_post_meta($post->ID, 'views', true)) + intval(get_post_meta($post->ID, 'bot_views', true));
	if( $display ) {
		echo('<span id="wppvp_tv_' . $post->ID . '">' . number_format($post_views) . '</span>' . $text_views);
		if( defined('WP_CACHE') && WP_CACHE ) {
			global $pv_data;
			$pv_data->add_cache_stats('tv');
		}
	} else {
		return $post_views;
	}
}

// Function: Display The Post User Views
function the_user_views($text_views=' User Views', $display=true) {
	global $post;
	$post_user_views = intval(get_post_meta($post->ID, 'views', true));
	if( $display ) {
		echo('<span id="wppvp_tuv_' . $post->ID . '">' . number_format($post_user_views) . '</span>' . $text_views);
		if( defined('WP_CACHE') && WP_CACHE ) {
			global $pv_data;
			$pv_data->add_cache_stats('tv');
		}
	} else {
		return $post_user_views;
	}
}

// Function: Display The Post Bot Views
function the_bot_views($text_views=' Bot Views', $display=true) {
	global $post;
	$post_bot_views = intval(get_post_meta($post->ID, 'bot_views', true));
	if( $display ) {
		echo('<span id="wppvp_tbv_' . $post->ID . '">' . number_format($post_bot_views) . '</span>' . $text_views);
		if( defined('WP_CACHE') && WP_CACHE ) {
			global $pv_data;
			$pv_data->add_cache_stats('tv');
		}
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
		$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, (IFNULL(CAST(pm1.meta_value AS UNSIGNED), 0) + IFNULL(CAST(pm2.meta_value AS UNSIGNED), 0)) AS views FROM ' . $wpdb->posts . ' AS p LEFT JOIN ' . $wpdb->postmeta . ' AS pm1 ON pm1.post_id = p.ID AND pm1.meta_key = "views" LEFT JOIN ' . $wpdb->postmeta . ' AS pm2 ON pm2.post_id = p.ID AND pm2.meta_key = "bot_views" WHERE p.post_date < "' . current_time('mysql') . '" AND p.post_status = "publish" AND p.post_password = "" AND ' . $where . ' ORDER BY views DESC LIMIT ' . $limit);
		$output_format = $output_format['mostviewsbot'];
	} else {
		$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, IFNULL(CAST(pm.meta_value AS UNSIGNED), 0) AS views FROM ' . $wpdb->posts . ' AS p LEFT JOIN ' . $wpdb->postmeta . ' AS pm ON pm.post_id = p.ID AND pm.meta_key = "views" WHERE p.post_date < "' . current_time('mysql') . '" AND p.post_status = "publish" AND p.post_password = "" AND ' . $where . ' ORDER BY views DESC LIMIT ' . $limit);
		$output_format = $output_format['mostviewsnobot'];
	}
	if( $most_views ) {
		$output = '';
		foreach( $most_views as $most_view ) {
			$post_title = $most_view->post_title;
			$post_views = number_format(intval($most_view->views));
			$post_link = '<a href="' . get_permalink($most_view->ID) . '">' . wp_snippet_chars($post_title, $chars) . '</a>';
			$post_date = get_the_time(get_option('date_format'), $most_view->ID);
			$output .= '<li>' . sprintf($output_format, $post_views, $post_link, $post_date) . '</li>' . "\n";
		}
	} else {
		$output = '<li>' . __('N/A', 'wp-postviews_plus') . '</li>';
	}
	if( $display ) {
		echo($output);
	} else {
		return $output;
	}
}

### Function: make sql for Category ID
function make_sql_category_id($category_id) {
 global $wpdb, $post;
	if( is_int($category_id) && $category_id>0 ) {
		$ttid = $wpdb->get_col('SELECT term_taxonomy_id FROM ' . $wpdb->term_taxonomy . ' WHERE term_id = ' . $category_id);
		$make_sql = 'tr.term_taxonomy_id IN (' . join(', ', $ttid) . ')';
	} elseif( is_array($category_id) ) {
		$term_id = 'term_id IN (' . implode(', ', $category_id) . ')';
		$ttid = $wpdb->get_col('SELECT term_taxonomy_id FROM ' . $wpdb->term_taxonomy . ' WHERE ' . $term_id);
		$make_sql .= 'tr.term_taxonomy_id IN (' . join(', ', $ttid) . ')';
	} else {
		$make_sql .= 'tr.term_taxonomy_id IN (';
		$category = get_the_category($post->ID);
		foreach( $category AS $cate )	{
			$make_sql .= $cate->term_taxonomy_id . ', ';
		}
		$make_sql = substr($make_sql, 0, -2);
		$make_sql .= ')';
	}
	return $make_sql;
}

### Function: Display Most Viewed Post By Category ID
function get_most_viewed_category($category_id=1, $mode='', $limit=10, $chars=0, $display=true, $with_bot=true) {
	global $wpdb;
	$output_format = get_option('PV+_option');
	$where = make_sql_category_id($category_id);
	if( $with_bot ) {
		$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, (IFNULL(CAST(pm1.meta_value AS UNSIGNED), 0) + IFNULL(CAST(pm2.meta_value AS UNSIGNED), 0)) AS views FROM ' . $wpdb->posts . ' AS p LEFT JOIN ' . $wpdb->postmeta . ' AS pm1 ON pm1.post_id = p.ID AND pm1.meta_key = "views" LEFT JOIN ' . $wpdb->postmeta . ' AS pm2 ON pm2.post_id = p.ID AND pm2.meta_key = "bot_views" LEFT JOIN ' . $wpdb->term_relationships . ' AS tr ON tr.object_id = p.ID WHERE p.post_date < "' . current_time('mysql') . '" AND p.post_status = "publish" AND p.post_type = "post" AND p.post_password = "" AND ' . $where . ' GROUP BY p.ID ORDER BY views DESC LIMIT ' . $limit);
		$output_format = $output_format['mostviewsbot'];
	} else {
		$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, IFNULL(CAST(pm.meta_value AS UNSIGNED), 0) AS views FROM ' . $wpdb->posts . ' AS p LEFT JOIN ' . $wpdb->postmeta . ' AS pm ON pm.post_id = p.ID AND pm.meta_key = "views" LEFT JOIN ' . $wpdb->term_relationships . ' AS tr ON tr.object_id = p.ID WHERE p.post_date < "' . current_time('mysql') . '" AND p.post_status = "publish" AND p.post_type = "post" AND p.post_password = "" AND ' . $where . ' GROUP BY p.ID ORDER BY views DESC LIMIT ' . $limit);
		$output_format = $output_format['mostviewsnobot'];
	}
	if( $most_views ) {
		$output = '';
		foreach( $most_views as $most_view ) {
			$post_title = $most_view->post_title;
			$post_views = number_format(intval($most_view->views));
			$post_link = '<a href="' . get_permalink($most_view->ID) . '">' . wp_snippet_chars($post_title, $chars) . '</a>';
			$post_date = get_the_time(get_option('date_format'), $most_view->ID);
			$output .= '<li>' . sprintf($output_format, $post_views, $post_link, $post_date) . '</li>' . "\n";
		}
	} else {
		$output = '<li>' . __('N/A', 'wp-postviews_plus') . '</li>' . "\n";
	}
	if( $display ) {
		echo($output);
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
		$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, (IFNULL(CAST(pm1.meta_value AS UNSIGNED), 0) + IFNULL(CAST(pm2.meta_value AS UNSIGNED), 0)) AS views FROM ' . $wpdb->posts . ' AS p LEFT JOIN ' . $wpdb->postmeta . ' AS pm1 ON pm1.post_id = p.ID AND pm1.meta_key = "views" LEFT JOIN ' . $wpdb->postmeta . ' AS pm2 ON pm2.post_id = p.ID AND pm2.meta_key = "bot_views" WHERE p.post_date < "' . current_time('mysql') . '" AND p.post_date > "' . $limit_date . '" AND p.post_status = "publish" AND p.post_password = "" AND ' . $where . ' ORDER BY views DESC LIMIT ' . $limit);
		$output_format = $output_format['mostviewsbot'];
	} else {
		$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, IFNULL(CAST(pm.meta_value AS UNSIGNED), 0) AS views FROM ' . $wpdb->posts . ' AS p LEFT JOIN ' . $wpdb->postmeta . ' AS pm ON pm.post_id = p.ID AND pm.meta_key = "views" WHERE p.post_date < "' . current_time('mysql') . '" AND p.post_date > "' . $limit_date . '" AND p.post_status = "publish" AND p.post_password = "" AND ' . $where . ' ORDER BY views DESC LIMIT ' . $limit);
		$output_format = $output_format['mostviewsnobot'];
	}
	if( $most_views ) {
		$output = '';
		foreach( $most_views as $most_view ) {
			$post_title = $most_view->post_title;
			$post_views = number_format(intval($most_view->views));
			$post_link = '<a href="' . get_permalink($most_view->ID) . '">' . wp_snippet_chars($post_title, $chars) . '</a>';
			$post_date = get_the_time(get_option('date_format'), $most_view->ID);
			$output .= '<li>' . sprintf($output_format, $post_views, $post_link, $post_date) . '</li>' . "\n";
		}
	} else {
		$output = '<li>' . __('N/A', 'postviews_plus') . '</li>' . "\n";
	}
	if( $display ) {
		echo($output);
	} else {
		return $output;
	}
}

### Function: Display Most Viewed Post In Last Days By Category
function get_timespan_most_viewed_cat($category_id=1, $mode='', $limit=10, $days=7, $display=true, $with_bot=true, $chars=0) {
	global $wpdb;
	$limit_date = current_time('timestamp') - ($days*86400);
	$limit_date = date('Y-m-d H:i:s', $limit_date);
	$output_format = get_option('PV+_option');
	$where = make_sql_category_id($category_id);
	if( $with_bot ) {
		$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, (IFNULL(CAST(pm1.meta_value AS UNSIGNED), 0) + IFNULL(CAST(pm2.meta_value AS UNSIGNED), 0)) AS views FROM ' . $wpdb->posts . ' AS p LEFT JOIN ' . $wpdb->postmeta . ' AS pm1 ON pm1.post_id = p.ID AND pm1.meta_key = "views" LEFT JOIN ' . $wpdb->postmeta . ' AS pm2 ON pm2.post_id = p.ID AND pm2.meta_key = "bot_views" LEFT JOIN ' . $wpdb->term_relationships . ' AS tr ON tr.object_id = p.ID WHERE p.post_date < "' . current_time('mysql') . '" AND p.post_date > "' . $limit_date . '" AND p.post_status = "publish" AND p.post_type = "post" AND p.post_password = "" AND ' . $where . ' GROUP BY p.ID ORDER BY views DESC LIMIT ' . $limit);
		$output_format = $output_format['mostviewsbot'];
	} else {
		$most_views = $wpdb->get_results('SELECT p.ID, p.post_title, IFNULL(CAST(pm.meta_value AS UNSIGNED), 0) AS views FROM ' . $wpdb->posts . ' AS p LEFT JOIN ' . $wpdb->postmeta . ' AS pm ON pm.post_id = p.ID AND pm.meta_key = "views" LEFT JOIN ' . $wpdb->term_relationships . ' AS tr ON tr.object_id = p.ID WHERE p.post_date < "' . current_time('mysql') . '" AND p.post_date > "' . $limit_date . '" AND p.post_status = "publish" AND p.post_type = "post" AND p.post_password = "" AND ' . $where . ' GROUP BY p.ID ORDER BY views DESC LIMIT ' . $limit);
		$output_format = $output_format['mostviewsnobot'];
	}
	if( $most_views ) {
		$output = '';
		foreach( $most_views as $most_view ) {
			$post_title = $most_view->post_title;
			$post_views = number_format(intval($most_view->views));
			$post_link = '<a href="' . get_permalink($most_view->ID) . '">' . wp_snippet_chars($post_title, $chars) . '</a>';
			$post_date = get_the_time(get_option('date_format'), $most_view->ID);
			$output .= '<li>' . sprintf($output_format, $post_views, $post_link, $post_date) . '</li>' . "\n";
		}
	} else {
		$output = '<li>' . __('N/A', 'postviews_plus') . '</li>' . "\n";
	}
	if( $display ) {
		echo($output);
	} else {
		return $output;
	}
}

// Function: Display Total Views
function get_totalviews($display=true, $with_bot=true, $with_post=true) {
	global $wpdb, $pv_data;
	if( $with_bot ) {
		$total_views = $wpdb->get_var('SELECT SUM(IFNULL(CAST(meta_value AS UNSIGNED), 0)) FROM ' . $wpdb->postmeta . ' WHERE meta_key = "views" OR meta_key = "bot_views"');
		$type = 1;
		if( $with_post ) {
			$total_views += array_sum($pv_data->views['now']['user']) + array_sum($pv_data->views['now']['bot']);
			$type = 2;
		}
	} else {
		$total_views = $wpdb->get_var('SELECT SUM(IFNULL(CAST(meta_value AS UNSIGNED), 0)) FROM ' . $wpdb->postmeta . ' WHERE meta_key = "views"');
		$type = 3;
		if( $with_post ) {
			$total_views += array_sum($pv_data->views['now']['user']);
			$type = 4;
		}
	}
	if( $display ) {
		echo('<span id="wppvp_gt_' . $type . '">' . number_format($total_views) . '</span>');
		if( defined('WP_CACHE') && WP_CACHE ) {
			$pv_data->add_cache_stats('gt' . $type);
		}
	} else {
		return $total_views;
	}
}

// Function: Add Option Value
register_activation_hook(__FILE__, 'postviews_plus_add');
function postviews_plus_add() {
	global $wpdb, $pv_data;
	if( !isset($pv_data) ) {
		$pv_data = new wppvp;
	}
	add_option('PV+_botagent', a2s($pv_data->botAgent['def']));
	add_option('PV+_option', $pv_data->pv_option['def']);
	add_option('PV+_useragent', '');
	add_option('PV+_views', $pv_data->views['def']);
	add_option('PV+_DBversion', '1.1.23');
	$sql = 'SHOW TABLES FROM ' . DB_NAME . ' LIKE "' . $wpdb->prefix . 'postviewsplus"';
	if( $wpdb->get_var($sql)==null ) {
		$wpdb->get_var('CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'postviewsplus (
			`count_type` VARCHAR(255) NOT NULL,
			`count_id` VARCHAR(255) NOT NULL,
			`tv` VARCHAR(255) NOT NULL DEFAULT "",
			`gt` VARCHAR(255) NOT NULL DEFAULT "",
			`look_ip` TEXT NOT NULL,
			`look_ip_time` INT UNSIGNED NOT NULL DEFAULT "0",
			PRIMARY KEY (`count_type`, `count_id`))');
	} else {
		$wpdb->get_var('ALTER TABLE ' . $wpdb->prefix . 'postviewsplus
			ADD `look_ip` TEXT NOT NULL,
			ADD `look_ip_time` INT UNSIGNED NOT NULL DEFAULT "0"');
	}
	$wpdb->get_var('ALTER TABLE ' . $wpdb->prefix . 'postviewsplus CHANGE `count_type` `count_type` VARCHAR(255) NOT NULL');
	$wpdb->get_var('ALTER TABLE ' . $wpdb->prefix . 'postviewsplus CHANGE `count_id` `count_id` VARCHAR(255) NOT NULL');
}
?>