<?php
/*
Plugin Name: WP-PostViews Plus
Plugin URI: http://fantasyworld.idv.tw/programs/wp_postviews_plus/
Description: Enables You To Display How Many Times A Post Had Been Viewed By User Or Bot.
Version: 1.0.12
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

// Function: Calculate Post Views
add_filter('the_content', 'process_postviews');
function process_postviews($content) {
	global $id;
	static $postid = array();
	$pv_option = get_settings('PV+_option');
	if( !in_array($id, $postid) ) {
		$postid[] = $id;
		if( empty($_COOKIE[USER_COOKIE]) || $pv_option['userlogoin']==1 ) {
			if( is_single() || is_page() || $pv_option['indexviews']==1 ) {
				$useragent = trim($_SERVER['HTTP_USER_AGENT']);
				$bot = false;
				$botAgent = s2a(get_settings('PV+_botagent'));
				if( strlen($useragent)>5 ) {
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
					$post_bot_views = intval(post_custom('bot_views'));
					if($post_bot_views > 0) {
						update_post_meta($id, 'bot_views', ($post_bot_views+1));
					} else {
						add_post_meta($id, 'bot_views', 1);
					}
				} else {
					$post_views = intval(post_custom('views'));
					$getUseragent = $pv_option['getuseragent'];
					if( $getUseragent==1 ) {
						$PV_useragent = s2a(get_settings('PV+_useragent'));
						if( !in_array($useragent, $PV_useragent) ) {
							$PV_useragent[] = $useragent;
							update_option('PV+_useragent', a2s($PV_useragent));
						}
					}
					if($post_views > 0) {
						update_post_meta($id, 'views', ($post_views+1));
					} else {
						add_post_meta($id, 'views', 1);
					}
				}
			}
		}
	}
	return $content;
}

// Function: Display The Post Total Views
function the_views($text_views='Views', $display=true) {
	$post_views = intval(post_custom('views')) + intval(post_custom('bot_views'));
	if( $display ) {
		echo number_format($post_views).' '.$text_views;
	} else {
		return $post_views;
	}
}

// Function: Display The Post User Views
function the_user_views($text_views='User Views', $display=true) {
	$post_user_views = intval(post_custom('views'));
	if( $display ) {
		echo number_format($post_user_views).' '.$text_views;
	} else {
		return $post_user_views;
	}
}

// Function: Display The Post Bot Views
function the_bot_views($text_views='Bot Views', $display=true) {
	$post_bot_views = intval(post_custom('bot_views'));
	if( $display ) {
		echo number_format($post_bot_views).' '.$text_views;
	} else {
		return $post_bot_views;
	}
}


// Function: Display Most Viewed Page/Post
if( !function_exists('get_most_viewed') ) {
	function get_most_viewed($mode='', $limit=10, $chars=0, $display=true, $with_bot=true) {
		global $wpdb, $post;
		if( $mode=='post' ) {
			$where = 'p.post_type = \'post\'';
		} elseif( $mode=='page' ) {
			$where = 'p.post_type = \'page\'';
		} else {
			$where = '(p.post_type = \'post\' OR p.post_type = \'page\')';
		}
		if( $with_bot ) {
			$most_viewed = $wpdb->get_results("SELECT p.ID, p.post_title, p.post_name, p.post_status, p.post_date, (CAST(pm1.meta_value AS UNSIGNED) + CAST(pm2.meta_value AS UNSIGNED)) AS views FROM ".$wpdb->posts." AS p LEFT JOIN ".$wpdb->postmeta." AS pm1 ON pm1.post_id = p.ID LEFT JOIN ".$wpdb->postmeta." AS pm2 ON pm2.post_id = p.ID WHERE p.post_date < '".current_time('mysql')."' AND p.post_status = 'publish' AND ".$where." AND pm1.meta_key = 'views' AND pm2.meta_key = 'bot_views' AND p.post_password = '' ORDER BY views DESC LIMIT ".$limit);
			$viewsworld = 'Views';
		} else {
			$most_viewed = $wpdb->get_results("SELECT p.ID, p.post_title, p.post_name, p.post_status, p.post_date, CAST(pm.meta_value AS UNSIGNED) AS views FROM ".$wpdb->posts." AS p LEFT JOIN ".$wpdb->postmeta." AS pm ON pm.post_id = p.ID WHERE p.post_date < '".current_time('mysql')."' AND p.post_status = 'publish' AND ".$where." AND pm.meta_key = 'views' AND p.post_password = '' ORDER BY views DESC LIMIT ".$limit);
			$viewsworld = 'User Views';
		}
		if( $most_viewed ) {
			$output = '';
			if( $chars>0 ) {
				foreach($most_viewed as $post) {
					$post_title = htmlspecialchars(stripslashes($post->post_title));
					$post_views = number_format(intval($post->views));
					$output .= '<li><a href="'.get_permalink().'">'.snippet_chars_1($post_title, $chars).'</a> - '.$post_views.' '.$viewsworld.'</li>'."\n";
				}
			} else {
				foreach($most_viewed as $post) {
					$post_title = htmlspecialchars(stripslashes($post->post_title));
					$post_views = intval($post->views);
					$post_views = number_format($post_views);
					$output .= '<li><a href="'.get_permalink().'">'.$post_title.'</a> - '.$post_views.' '.$viewsworld.'</li>'."\n";
				}
			}
		} else {
			$output = '<li>N/A</li>';
		}
		if( $display ) {
			echo $output;
		} else {
			return $output;
		}
	}
}

// Function: Display Most Viewed Page/Post In Last Days
// Added by Paolo Tagliaferri (http://www.vortexmind.net - webmaster@vortexmind.net)
function get_timespan_most_viewed($mode='', $limit=10, $days=7, $display=true, $with_bot=true) {
	global $wpdb, $post;
	$limit_date = current_time('timestamp') - ($days*86400);
	$limit_date = date("Y-m-d H:i:s",$limit_date);
	if( $mode=='post' ) {
		$where = 'p.post_type = \'post\'';
	} elseif( $mode=='page' ) {
		$where = 'p.post_type = \'page\'';
	} else {
		$where = '(p.post_type = \'post\' OR p.post_type = \'page\')';
	}
	if( $with_bot ) {
		$most_viewed = $wpdb->get_results("SELECT p.ID, p.post_title, p.post_name, p.post_status, p.post_date, (CAST(pm1.meta_value AS UNSIGNED) + CAST(pm2.meta_value AS UNSIGNED)) AS views FROM $wpdb->posts AS p LEFT JOIN $wpdb->postmeta AS pm1 ON pm1.post_id = p.ID LEFT JOIN $wpdb->postmeta AS pm2 ON pm2.post_id = p.ID WHERE p.post_date < '".current_time('mysql')."' AND p.post_date > '".$limit_date."' AND p.post_status = 'publish' AND ".$where." AND pm1.meta_key = 'views' AND pm2.meta_key = 'bot_views' AND p.post_password = '' ORDER BY views DESC LIMIT $limit");
		$viewsworld = 'Views';
	} else {
		$most_viewed = $wpdb->get_results("SELECT p.ID, p.post_title, p.post_name, p.post_status, p.post_date, CAST(pm.meta_value AS UNSIGNED) AS views FROM $wpdb->posts AS p LEFT JOIN $wpdb->postmeta AS pm ON pm.post_id = p.ID WHERE p.post_date < '".current_time('mysql')."' AND p.post_date > '".$limit_date."' AND p.post_status = 'publish' AND ".$where." AND pm.meta_key = 'views' AND p.post_password = '' ORDER BY views DESC LIMIT $limit");
		$viewsworld = 'User Views';
	}
	if( $most_viewed ) {
		$output = '';
		foreach($most_viewed as $post) {
			$post_title = htmlspecialchars(stripslashes($post->post_title));
			$post_views = number_format(intval($post->views));
			$output .= '<li><a href="'.get_permalink().'">'.$post_title.'</a> - '.$post_views.' '.$viewsworld.'</li>';
		}
	} else {
		$output = '<li>N/A</li>';
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
			$total_views = $wpdb->get_var("SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM $wpdb->postmeta WHERE meta_key = 'views' OR meta_key = 'bot_views'");
		} else {
			$total_views = $wpdb->get_var("SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM $wpdb->postmeta WHERE meta_key = 'views'");
		}
		if( $display ) {
			echo number_format($total_views);
		} else {
			return $total_views;
		}
	}
}

// Function: Snippet Characters
if( !function_exists('snippet_chars_1') ) {
	function snippet_chars_1($text, $length = 15) {
		mb_internal_encoding(get_bloginfo('charset'));
		$text = htmlspecialchars_decode($text);
		if( mb_strlen($text) > $length ) {
			return htmlspecialchars(mb_substr($text,0,$length)).' ...';
		} else {
			return htmlspecialchars($text);
		}
	}
}

// Function: Add Option Value
add_action('activate_postviews_plus/postviews_plus.php', 'postviews_plus_add');
function postviews_plus_add() {
	global $wpdb;
	$botAgent = array('bot','spider','validator','google');
	$pv_option = array('getuseragent'=>0, 'userlogoin'=>0, 'indexviews'=>0);
	add_option('PV+_botagent', a2s($botAgent), 'WP-PostViews Plus bot useragant');
	add_option('PV+_option', $pv_option, 'WP-PostViews Plus Option');
	add_option('PV+_useragent', '', 'WP-PostViews Plus get user useragant');
}
// Function: Add Options Page
add_action('admin_menu', 'postviews_plus_option');
function postviews_plus_option() {
	if( function_exists('add_options_page') ) {
		add_options_page('WP-PostViews Plus', 'PostViews+', 'manage_options', basename(__FILE__), 'postviews_plus_option_page');
	}
}
// Function: Show/Do Options Page
function postviews_plus_option_page()
{
	// Loading language file...
	$currentLocale = get_locale();
	if( !empty($currentLocale) ) {
		$moFile = dirname(__FILE__) . "/postviews_plus-" . $currentLocale . ".mo";
		if( @file_exists($moFile) && is_readable($moFile) ) {
			load_textdomain('postviews_plus', $moFile);
		}
	}

	global $_POST;
	$text = '';
	if( isset($_POST['update_op']) ) {
		$getuseragent = intval($_POST['getuseragent'])==1 ? 1 : 0;
		$userlogoin = intval($_POST['userlogoin'])==1 ? 1 : 0;
		$indexviews = intval($_POST['indexviews'])==1 ? 1 : 0;
		$pv_option = array('getuseragent' => $getuseragent, 'userlogoin'=>$userlogoin, 'indexviews'=>$indexviews);
		if( update_option('PV+_option', $pv_option) ) {
			$text .= '<font color="green">'.$update_text[$k].' '.__('Update Option Success' ,'postviews_plus').'</font><br />';
		}
	} elseif( isset($_POST['reset_op']) ) {
		$pv_option = array('getuseragent'=>0, 'userlogoin'=>0, 'indexviews'=>0);
		if( update_option('PV+_option', $pv_option) ) {
			$text .= '<font color="green">'.__('Reset Options Success' ,'postviews_plus').'</font>';
		}
	} elseif( isset($_POST['reset_pv']) ) {
		if( $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = 'views' OR meta_key = 'bot_views'") ) {
			$text .= '<font color="green">'.__('Reset Post View Timws Success' ,'postviews_plus').'</font>';
		}
	} elseif( isset($_POST['clear_user_ua']) ) {
		if( update_option('PV+_useragent', '') ) {
			$text .= '<font color="green">'.__('Cleared User User_agent Success' ,'postviews_plus').'</font>';
		}
	} elseif( isset($_POST['update_bot_ua']) ) {
		$botAgent = str_replace("\r\n", ARRAY_CAT, trim($_POST['botagent']));
		if( update_option('PV+_botagent', $botAgent) ) {
			$text .= '<font color="green">'.__('Update BOT User_agent Success' ,'postviews_plus').'</font><br />';
		}
	} elseif( isset($_POST['reset_bot_ua']) ) {
		$botAgent = array('bot','spider','validator','google');
		if( update_option('PV+_botagent', a2s($botAgent)) ) {
			$text .= '<font color="green">'.__('Reset Bot User_agent Success' ,'postviews_plus').'</font>';
		}
	} elseif( isset($_POST['ua_report']) ) {
		global $user_identity;
		$message = get_settings('PV+_botagent');
		$message = str_replace(ARRAY_CAT, "\n", $message);
		$message .= "\n".'By '.$user_identity.' From '.get_bloginfo('admin_email').' AT '.get_bloginfo('wpurl');
		if(FALSE != wp_mail('fantasyworldidvtw@gmail.com', 'Postviews+ BOT User_agent Report', $message)) {
			$text .= '<font color="green">'.__('Report BOT User_agent Success' ,'postviews_plus').'</font>';
		} else {
			$text .= '<font color="green">'.__('Report BOT User_agent Fail' ,'postviews_plus').'</font>';
		}
	}
	$pv_option = get_settings('PV+_option');
	$botAgent = s2a(get_settings('PV+_botagent'));
	$Useragent = s2a(get_settings('PV+_useragent'));
	if( !empty($text) ) {
		echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$text.'</p></div>';
	}
	echo '<div class="wrap"><h2>WP-PostViews Plus</h2>';
	echo '<form method="post" action="options-general.php?page='.basename(__FILE__).'"><table width="100%" align="center">';
	echo '<tr valign="top"><th align="left" width="30%">'.__('Options' ,'postviews_plus').'<br /><input type="submit" name="update_op" class="button" value="'.__('Update Options' ,'postviews_plus').'" /><br /><input type="submit" name="reset_op" class="button" value="'.__('Reset Option' ,'postviews_plus').'" /><br /><input type="submit" name="reset_pv" class="button" value="'.__('Reset Post Views' ,'postviews_plus').'" /></th>';
	echo '<td><input name="getuseragent" type="checkbox" value="1" '.($pv_option['getuseragent']?'checked="checked"':'').'/> '.__('Remember the User_agent Of User.' ,'postviews_plus').'<br />';
	echo '<input name="userlogoin" type="checkbox" value="1" '.($pv_option['userlogoin']?'checked="checked"':'').'/> '.__('Add Views number if User is logoin.' ,'postviews_plus').'<br />';
	echo '<input name="indexviews" type="checkbox" value="1" '.($pv_option['indexviews']?'checked="checked"':'').'/> '.__('Add Views number in Blog Index Page.' ,'postviews_plus').'<br /><br /></td></tr>';
	if( $pv_option['getuseragent']==1 ) {
		echo '<tr valign="top"><th align="left" width="30%">'.__('User User_agent' ,'postviews_plus').'<br /><input type="submit" name="clear_user_ua" class="button" value="'.__('Clear User User_agent record' ,'postviews_plus').'" /></th>';
		echo '<td>'.implode("<br />",$Useragent).'<br /><br /></td></tr>';
	}
	echo '<tr valign="top"><th align="left" width="30%">'.__('Bot User_agent' ,'postviews_plus').'<br /><input type="submit" name="update_bot_ua" class="button" value="'.__('Update BOT User_agent' ,'postviews_plus').'" /><br /><input type="submit" name="reset_bot_ua" class="button" value="'.__('Reset BOT User_agent' ,'postviews_plus').'" /><br /><input type="submit" name="ua_report" class="button" value="'.__('Report BOT User_agent To Author' ,'postviews_plus').'" /></th>';
	echo '<td>'.__('Here are a list of Bot User_agent. Start each User_agent on a new line.' ,'postviews_plus').'<br /><textarea name="botagent" cols="30" rows="'.(count($botAgent)+1).'">'.implode("\n",$botAgent).'</textarea></td></tr>';
	echo '</table></form></div>';
}
?>