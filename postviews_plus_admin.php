<?php
if( isset($_POST['update_pvp']) ) {
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
		if( update_option('PV+_option', $pv_data->pv_option['def']) ) {
			$text .= '<font color="green">'.__('Reset Options Success' ,'postviews_plus').'</font>';
		}
	} elseif( isset($_POST['reset_pv']) ) {
	  update_option('PV+_views', $pv_data->views['def']);
		if( $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = 'views' OR meta_key = 'bot_views'") ) {
			$text .= '<font color="green">'.__('Reset Post View Timws Success' ,'postviews_plus').'</font>';
		}
	} elseif( isset($_POST['clear_user_ua']) ) {
		if( update_option('PV+_useragent', '') ) {
			$text .= '<font color="green">'.__('Cleared User User_agent Success' ,'postviews_plus').'</font>';
		}
	} elseif( isset($_POST['update_bot_ua']) ) {
		$botAgent = strtolower(ereg_replace("(\r\n)+", ARRAY_CAT, trim($_POST['botagent'])));
		if( update_option('PV+_botagent', $botAgent) ) {
			$text .= '<font color="green">'.__('Update BOT User_agent Success' ,'postviews_plus').'</font><br />';
			if( $pv_data->pv_option['now']['reportbot'] )
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
		if( update_option('PV+_botagent',a2s($pv_data->botAgent['def'])) ) {
			$text .= '<font color="green">'.__('Reset Bot User_agent Success' ,'postviews_plus').'</font>';
		}
	}
}

$pv_data->update();

if( !empty($text) ) {
	echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$text.'</p></div>';
}
echo '<div class="wrap"><h2>WP-PostViews Plus</h2>';
echo '<form method="post" action=""><input type="hidden" name="update_pvp" value="" /><table class="form-table">';
echo '<tr valign="top"><th scope="row">'.__('Options' ,'postviews_plus').'</th>';
echo '<td><input name="getuseragent" type="checkbox" value="1" '.($pv_data->pv_option['now']['getuseragent']?'checked="checked"':'').'/> '.__('Remember the User_agent Of User.' ,'postviews_plus').'<br />';
echo '<input name="userlogoin" type="checkbox" value="1" '.($pv_data->pv_option['now']['userlogoin']?'checked="checked"':'').'/> '.__('Add Views number if User is logoin.' ,'postviews_plus').'<br />';
echo '<input name="reportbot" type="checkbox" value="1" '.($pv_data->pv_option['now']['reportbot']?'checked="checked"':'').'/> '.__('Report BOT User_agent when update.' ,'postviews_plus').'<br />';
echo '<br />'.__('Output format of most views. (%1$s: Views number. %2$s: Post link. %3$s: Post Date.)' ,'postviews_plus').'<br /><input name="mostviewsbot" type="text" value="'.$pv_data->pv_option['now']['mostviewsbot'].'" size="30" />'.__('with bot views.' ,'postviews_plus').'<br />';
echo '<input name="mostviewsnobot" type="text" value="'.$pv_data->pv_option['now']['mostviewsnobot'].'" size="30" />'.__('without bot views.' ,'postviews_plus');
echo '<p class="submit"><input type="submit" name="update_op" class="button" value="'.__('Update Options' ,'postviews_plus').'" /><input type="submit" name="reset_op" class="button" value="'.__('Reset Option' ,'postviews_plus').'" /><input type="submit" name="reset_pv" class="button" value="'.__('Reset Post Views' ,'postviews_plus').'" /></p>';
echo '</td></tr>';
if( $pv_data->pv_option['now']['getuseragent']==1 ) {
  $Useragent = s2a(get_option('PV+_useragent'));
	echo '<tr valign="top"><th scope="row">'.__('User User_agent' ,'postviews_plus').'</th>';
	echo '<td>'.implode("<br />", $Useragent);
	echo '<p class="submit"><input type="submit" name="clear_user_ua" class="button" value="'.__('Clear User User_agent record' ,'postviews_plus').'" /></p>';
	echo '</td></tr>';
}
echo '<tr valign="top"><th scope="row">'.__('Bot User_agent' ,'postviews_plus').'</th>';
echo '<td>'.__('Here are a list of Bot User_agent. Start each User_agent on a new line.' ,'postviews_plus').'<br /><textarea name="botagent" cols="30" rows="'.(count($pv_data->botAgent['now'])+1).'">'.implode("\n", $pv_data->botAgent['now']).'</textarea>';
echo '<p class="submit"><input type="submit" name="update_bot_ua" class="button" value="'.__('Update BOT User_agent' ,'postviews_plus').'" /><input type="submit" name="reset_bot_ua" class="button" value="'.__('Reset BOT User_agent' ,'postviews_plus').'" /></p>';
echo '</td></tr>';
echo '</table></form></div>';
?>