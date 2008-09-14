<?php
function postviews_plus_option_update()
{
	global $wpdb;
	$text = '';
	if( isset($_POST['update_op']) ) {
		$pv_option = array();
		$pv_option['getuseragent'] = intval($_POST['getuseragent'])==1 ? 1 : 0;
		$pv_option['userlogoin'] = intval($_POST['userlogoin'])==1 ? 1 : 0;
		$pv_option['reportbot'] = intval($_POST['reportbot'])==1 ? 1 : 0;
		$pv_option['mostviewsbot'] = addslashes(trim($_POST['mostviewsbot']));
		$pv_option['mostviewsnobot'] = addslashes(trim($_POST['mostviewsnobot']));
		if( update_option('PV+_option', $pv_option) ) {
			$text .= '<font color="green">'.__('Update Option Success' ,'postviews_plus').'</font><br />';
		}
	} elseif( isset($_POST['reset_op']) ) {
		$pv_option = array('getuseragent'=>0, 'userlogoin'=>0, 'reportbot'=>1, 'mostviewsbot'=>'%2$s - %1$s '.__('Views', 'postviews_plus'), 'mostviewsnobot'=>'%2$s - %1$s '.__('User Views', 'postviews_plus'));
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
			$pv_option = get_option('PV+_option');
			if( $pv_option['reportbot'] )
			{
				global $user_identity;
				$message = $botAgent;
				$message = str_replace(ARRAY_CAT, "\n", $message);
				$message .= "\n".'By '.$user_identity.' From '.get_bloginfo('admin_email').' AT '.get_bloginfo('wpurl');
				if(FALSE != wp_mail('fantasyworldidvtw@gmail.com', $user_identity.' Postviews+ BOT User_agent Report', $message)) {
					$text .= '<font color="green">'.__('Report BOT User_agent Success' ,'postviews_plus').'</font>';
				} else {
					$text .= '<font color="red">'.__('Report BOT User_agent Fail' ,'postviews_plus').'</font>';
				}
			}
		}
	} elseif( isset($_POST['reset_bot_ua']) ) {
		$botAgent = array('bot','spider','validator','google');
		if( update_option('PV+_botagent', a2s($botAgent)) ) {
			$text .= '<font color="green">'.__('Reset Bot User_agent Success' ,'postviews_plus').'</font>';
		}
	}
	return $text;
}
function postviews_plus_option_page_25()
{
	if( isset($_POST['update_pvp']) ) {
		$text = postviews_plus_option_update();
	}
	$pv_option = get_option('PV+_option');
	$botAgent = s2a(get_option('PV+_botagent'));
	$Useragent = s2a(get_option('PV+_useragent'));
	if( !empty($text) ) {
		echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$text.'</p></div>';
	}
	echo '<div class="wrap"><h2>WP-PostViews Plus</h2>';
	echo '<form method="post" action=""><input type="hidden" name="update_pvp" value="" /><table class="form-table">';
	echo '<tr valign="top"><th scope="row">'.__('Options' ,'postviews_plus').'</th>';
	echo '<td><input name="getuseragent" type="checkbox" value="1" '.($pv_option['getuseragent']?'checked="checked"':'').'/> '.__('Remember the User_agent Of User.' ,'postviews_plus').'<br />';
	echo '<input name="userlogoin" type="checkbox" value="1" '.($pv_option['userlogoin']?'checked="checked"':'').'/> '.__('Add Views number if User is logoin.' ,'postviews_plus').'<br />';
	echo '<input name="reportbot" type="checkbox" value="1" '.($pv_option['reportbot']?'checked="checked"':'').'/> '.__('Report BOT User_agent when update.' ,'postviews_plus').'<br /><br />';
	echo __('Output format of most views. (%1$s: Views number. %2$s: Post link.)' ,'postviews_plus').'<br /><input name="mostviewsbot" type="text" value="'.$pv_option['mostviewsbot'].'" size="30" />'.__('with bot views.' ,'postviews_plus').'<br />';
	echo '<input name="mostviewsnobot" type="text" value="'.$pv_option['mostviewsnobot'].'" size="30" />'.__('without bot views.' ,'postviews_plus');
	echo '<p class="submit"><input type="submit" name="update_op" class="button" value="'.__('Update Options' ,'postviews_plus').'" /><input type="submit" name="reset_op" class="button" value="'.__('Reset Option' ,'postviews_plus').'" /><input type="submit" name="reset_pv" class="button" value="'.__('Reset Post Views' ,'postviews_plus').'" /></p>';
	echo '</td></tr>';
	if( $pv_option['getuseragent']==1 ) {
		echo '<tr valign="top"><th scope="row">'.__('User User_agent' ,'postviews_plus').'</th>';
		echo '<td>'.implode('<br />',$Useragent);
		echo '<p class="submit"><input type="submit" name="clear_user_ua" class="button" value="'.__('Clear User User_agent record' ,'postviews_plus').'" /></p>';
		echo '</td></tr>';
	}
	echo '<tr valign="top"><th scope="row">'.__('Bot User_agent' ,'postviews_plus').'</th>';
	echo '<td>'.__('Here are a list of Bot User_agent. Start each User_agent on a new line.' ,'postviews_plus').'<br /><textarea name="botagent" cols="30" rows="'.(count($botAgent)+1).'">'.implode("\n",$botAgent).'</textarea>';
	echo '<p class="submit"><input type="submit" name="update_bot_ua" class="button" value="'.__('Update BOT User_agent' ,'postviews_plus').'" /><input type="submit" name="reset_bot_ua" class="button" value="'.__('Reset BOT User_agent' ,'postviews_plus').'" /></p>';
	echo '</td></tr>';
	echo '</table></form></div>';
}

function postviews_plus_option_page()
{
	if( isset($_POST['update_pvp']) ) {
		$text = postviews_plus_option_update();
	}
	$pv_option = get_option('PV+_option');
	$botAgent = s2a(get_option('PV+_botagent'));
	$Useragent = s2a(get_option('PV+_useragent'));
	if( !empty($text) ) {
		echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$text.'</p></div>';
	}
	echo '<div class="wrap"><h2>WP-PostViews Plus</h2>';
	echo '<form method="post" action=""><input type="hidden" name="update_pvp" value="" /><table width="100%" align="center">';
	echo '<tr valign="top"><th align="left" width="30%">'.__('Options' ,'postviews_plus').'<br /><input type="submit" name="update_op" class="button" value="'.__('Update Options' ,'postviews_plus').'" /><br /><input type="submit" name="reset_op" class="button" value="'.__('Reset Option' ,'postviews_plus').'" /><br /><input type="submit" name="reset_pv" class="button" value="'.__('Reset Post Views' ,'postviews_plus').'" /></th>';
	echo '<td><input name="getuseragent" type="checkbox" value="1" '.($pv_option['getuseragent']?'checked="checked"':'').'/> '.__('Remember the User_agent Of User.' ,'postviews_plus').'<br />';
	echo '<input name="userlogoin" type="checkbox" value="1" '.($pv_option['userlogoin']?'checked="checked"':'').'/> '.__('Add Views number if User is logoin.' ,'postviews_plus').'<br />';
	echo '<input name="reportbot" type="checkbox" value="1" '.($pv_option['reportbot']?'checked="checked"':'').'/> '.__('Report BOT User_agent when update.' ,'postviews_plus').'<br /><br />';
	echo __('Output format of most views. (%1$s: Views number. %2$s: Post link.)' ,'postviews_plus').'<br /><input name="mostviewsbot" type="text" value="'.$pv_option['mostviewsbot'].'" size="30" />'.__('with bot views.' ,'postviews_plus').'<br />';
	echo '<input name="mostviewsnobot" type="text" value="'.$pv_option['mostviewsnobot'].'" size="30" />'.__('without bot views.' ,'postviews_plus').'<br /></td></tr>';
	if( $pv_option['getuseragent']==1 ) {
		echo '<tr valign="top"><th align="left" width="30%">'.__('User User_agent' ,'postviews_plus').'<br /><input type="submit" name="clear_user_ua" class="button" value="'.__('Clear User User_agent record' ,'postviews_plus').'" /></th>';
		echo '<td>'.implode('<br />',$Useragent).'<br /><br /></td></tr>';
	}
	echo '<tr valign="top"><th align="left" width="30%">'.__('Bot User_agent' ,'postviews_plus').'<br /><input type="submit" name="update_bot_ua" class="button" value="'.__('Update BOT User_agent' ,'postviews_plus').'" /><br /><input type="submit" name="reset_bot_ua" class="button" value="'.__('Reset BOT User_agent' ,'postviews_plus').'" /></th>';
	echo '<td>'.__('Here are a list of Bot User_agent. Start each User_agent on a new line.' ,'postviews_plus').'<br /><textarea name="botagent" cols="30" rows="'.(count($botAgent)+1).'">'.implode("\n",$botAgent).'</textarea></td></tr>';
	echo '</table></form></div>';
}
?>