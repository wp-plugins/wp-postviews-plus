<?php
if( isset($_POST['update_pvp']) ) {
	if( isset($_POST['update_op']) ) {
		$pv_option = array();
		$pv_option['getuseragent'] = intval($_POST['getuseragent'])==1 ? 1 : 0;
		$pv_option['userlogoin'] = intval($_POST['userlogoin'])==1 ? 1 : 0;
		$pv_option['reportbot'] = intval($_POST['reportbot'])==1 ? 1 : 0;
		$pv_option['mostviewsbot'] = htmlspecialchars($_POST['mostviewsbot'], ENT_QUOTES);
		$pv_option['mostviewsnobot'] = htmlspecialchars($_POST['mostviewsnobot'], ENT_QUOTES);
		if( $pv_option['getuseragent']==0 ) {
		  update_option('PV+_useragent', '');
		}
		if( update_option('PV+_option', $pv_option) ) {
			$text .= '<font color="green">'.__('Update Option Success' ,'postviews_plus').'</font><br />';
		}
	} elseif( isset($_POST['reset_op']) ) {
		if( update_option('PV+_option', $pv_config->def_pv_option) ) {
			$text .= '<font color="green">'.__('Reset Options Success' ,'postviews_plus').'</font>';
		}
	} elseif( isset($_POST['reset_pv']) ) {
		update_option('PV+_views', $pv_config->def_views);
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
			$pv_option = get_settings('PV+_option');
			if( $pv_option['reportbot'] )
			{
				global $blog_name;
				$message = $botAgent;
				$message = str_replace(ARRAY_CAT, "\n", $message);
				$message .= "\n".'By '.$blog_name.' From '.get_bloginfo('admin_email').' AT '.get_bloginfo('wpurl');
				if(FALSE != wp_mail('fantasyworldidvtw@gmail.com', $blog_name.' Postviews+ BOT User_agent Report', $message)) {
					$text .= '<font color="green">'.__('Report BOT User_agent Success' ,'postviews_plus').'</font>';
				} else {
					$text .= '<font color="red">'.__('Report BOT User_agent Fail' ,'postviews_plus').'</font>';
				}
			}
		}
	} elseif( isset($_POST['reset_bot_ua']) ) {
		if( update_option('PV+_botagent', a2s($pv_config->def_botAgent)) ) {
			$text .= '<font color="green">'.__('Reset Bot User_agent Success' ,'postviews_plus').'</font>';
		}
	}
}
$pv_option = get_option('PV+_option');
$botAgent = s2a(get_option('PV+_botagent'));
$Useragent = s2a(get_option('PV+_useragent'));
if( !empty($text) ) {
	echo('<!-- Last Action --><div id="message" class="updated fade"><p>'.$text.'</p></div>');
}
?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"><br />
	</div><h2>WP-PostViews Plus</h2>
	<form method="post" action="">
		<input type="hidden" name="update_pvp" value="" />
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Options' ,'postviews_plus'); ?></th>
				<td>
					<input name="getuseragent" type="checkbox" value="1" <?php echo($pv_option['getuseragent']?'checked="checked" ':''); ?>/> <?php _e('Remember the User_agent Of User.' ,'postviews_plus'); ?><br />
					<input name="userlogoin" type="checkbox" value="1" <?php echo($pv_option['userlogoin']?'checked="checked" ':''); ?>/> <?php _e('Add Views number if User is logoin.' ,'postviews_plus'); ?><br />
					<input name="reportbot" type="checkbox" value="1" <?php echo($pv_option['reportbot']?'checked="checked" ':''); ?>/> <?php _e('Report BOT User_agent when update.' ,'postviews_plus'); ?><br /><br /><? _e('Output format of most views. (%1$s: Views number. %2$s: Post link. %3$s: Post Date.)' ,'postviews_plus'); ?><br />
					<input name="mostviewsbot" type="text" value="<?php echo($pv_option['mostviewsbot']); ?>" size="30" /> <?php _e('with bot views.' ,'postviews_plus'); ?><br />
					<input name="mostviewsnobot" type="text" value="<?php echo($pv_option['mostviewsnobot']); ?>" size="30" /> <?php _e('without bot views.' ,'postviews_plus'); ?>
					<p class="submit">
						<input type="submit" name="update_op" class="button" value="<?php _e('Update Options' ,'postviews_plus'); ?>" />
						<input type="submit" name="reset_op" class="button" value="<?php _e('Reset Option' ,'postviews_plus'); ?>" />
						<input type="submit" name="reset_pv" class="button" value="<?php _e('Reset Post Views' ,'postviews_plus'); ?>" />
					</p>
				</td>
			</tr>
			<?php if( $pv_option['getuseragent']==1 ) { ?>
				<tr valign="top">
					<th scope="row"><?php _e('User User_agent' ,'postviews_plus'); ?></th>
					<td><?php echo(implode("<br />+",$Useragent)); ?>
						<p class="submit"><input type="submit" name="clear_user_ua" class="button" value="<?php _e('Clear User User_agent record' ,'postviews_plus'); ?>" /></p>
					</td>
				</tr>
			<?php } ?>
			<tr valign="top">
				<th scope="row"><?php _e('Bot User_agent' ,'postviews_plus'); ?></th>
				<td>
					<?php _e('Here are a list of Bot User_agent. Start each User_agent on a new line.' ,'postviews_plus'); ?><br /><textarea name="botagent" cols="30" rows="<?php echo(count($botAgent)+1); ?>"><?php echo(implode("\n",$botAgent)); ?></textarea>
					<p class="submit">
						<input type="submit" name="update_bot_ua" class="button" value="<?php _e('Update BOT User_agent' ,'postviews_plus'); ?>" />
						<input type="submit" name="reset_bot_ua" class="button" value="<?php _e('Reset BOT User_agent' ,'postviews_plus'); ?>'" />
					</p>
				</td>
			</tr>
		</table>
	</form>
</div>