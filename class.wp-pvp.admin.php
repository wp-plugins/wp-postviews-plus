<?php
defined('WP_PVP_VERSION') OR exit('No direct script access allowed');

class WP_PVP_admin {
	private static $initiated = false;
	private static $views_settings = array('PVP_options', 'widget_views-plus');
	private static $views_postmetas = array('views', 'bot_views');

	public static function init() {
		if( !self::$initiated ) {
			self::$initiated = true;

			if( isset($_POST['wp-pvp-uninstall']) && wp_verify_nonce($_POST['wp-pvp-uninstall'], 'wp-pvp-uninstall') ) {
				if( !empty($_POST['do']) ) {
					if( trim($_POST['uninstall_views_yes']) == 'yes' ) {
						add_action('admin_init', array('WP_PVP_admin', 'uninstall_all_data'));
					}
				}
			}

			add_filter('plugin_action_links', array('WP_PVP_admin', 'plugin_action_links'), 10, 2);
			add_action('admin_menu', array('WP_PVP_admin', 'admin_menu'));
		}
	}

	public static function plugin_action_links($links, $file) {
		if( $file == WP_PVP_PLUGIN_BASENAME ) {
			$settings_link = '<a href="options-general.php?page=wp_postviews_plus">' . __('Settings', WP_PVP::$textdomain) . '</a>';
			$links = array_merge(array($settings_link), $links);
		}
		return $links;
	}

	public static function admin_menu() {
		add_options_page('WP-PostViews Plus', __('PostViews+', WP_PVP::$textdomain), 'manage_options', 'wp_postviews_plus', array('WP_PVP_admin', 'setting'));
	}

	private static function update_setting() {
		global $wpdb;

		WP_PVP::$options['count'] = isset($_POST['views_count']) ? intval($_POST['views_count']) : WP_PVP::$options['count'];
		WP_PVP::$options['check_reflash'] = isset($_POST['views_check_reflash']) ? intval($_POST['views_check_reflash']) : WP_PVP::$options['check_reflash'];
		WP_PVP::$options['timeout'] = isset($_POST['views_timeout']) ? intval($_POST['views_timeout']) : WP_PVP::$options['timeout'];
		WP_PVP::$options['template'] = isset($_POST['views_template_template']) ? trim($_POST['views_template_template']) : WP_PVP::$options['template'];
		WP_PVP::$options['user_template'] = isset($_POST['views_template_user_template']) ? trim($_POST['views_template_user_template']) : WP_PVP::$options['user_template'];
		WP_PVP::$options['bot_template'] = isset($_POST['views_template_bot_template']) ? trim($_POST['views_template_bot_template']) : WP_PVP::$options['bot_template'];
		WP_PVP::$options['most_viewed_template'] = isset($_POST['views_template_most_viewed']) ? trim($_POST['views_template_most_viewed']) : WP_PVP::$options['most_viewed_template'];
		WP_PVP::$options['set_thumbnail_size_h'] = isset($_POST['set_thumbnail_size_h']) ? intval($_POST['set_thumbnail_size_h']) : WP_PVP::$options['set_thumbnail_size_h'];
		WP_PVP::$options['set_thumbnail_size_w'] = isset($_POST['set_thumbnail_size_w']) ? intval($_POST['set_thumbnail_size_w']) : WP_PVP::$options['set_thumbnail_size_w'];

		WP_PVP::$options['display_home'] = isset($_POST['views_display_home']) ? intval($_POST['views_display_home']) : WP_PVP::$options['display_home'];
		WP_PVP::$options['display_single'] = isset($_POST['views_display_single']) ? intval($_POST['views_display_single']) : WP_PVP::$options['display_single'];
		WP_PVP::$options['display_page'] = isset($_POST['views_display_page']) ? intval($_POST['views_display_page']) : WP_PVP::$options['display_page'];
		WP_PVP::$options['display_archive'] = isset($_POST['views_display_archive']) ? intval($_POST['views_display_archive']) : WP_PVP::$options['display_archive'];
		WP_PVP::$options['display_search'] = isset($_POST['views_display_search']) ? intval($_POST['views_display_search']) : WP_PVP::$options['display_search'];
		WP_PVP::$options['display_other'] = isset($_POST['views_display_other']) ? intval($_POST['views_display_other']) : WP_PVP::$options['display_other'];

		if( isset($_POST['views_botagent']) ) {
			$botagent = explode("\r\n", trim($_POST['views_botagent']));
			if( !is_array($botagent) ) {
				$botagent = explode("\n", trim($_POST['views_botagent']));
			}
			if( !is_array($botagent) ) {
				$botagent = array('bot', 'spider', 'slurp');
			}
			WP_PVP::$options['botagent'] = $botagent;
		}
		if( WP_PVP::$options['check_reflash'] ) {
			$charset_collate = $wpdb->get_charset_collate();
			$wpdb->query('CREATE TABLE IF NOT EXISTS `' . $wpdb->postviews_plus_reflash . '` (
				`post_id` BIGINT(20) unsigned NOT NULL DEFAULT "0",
				`user_ip` VARCHAR(100) NOT NULL DEFAULT "",
				`look_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`post_id`, `user_ip`),
				INDEX (`look_time`)
			) ' . $charset_collate . ';');
		} else {
			$wpdb->query('DROP TABLE IF EXISTS `' . $wpdb->postviews_plus_reflash . '`');
		}
		if( update_option('PVP_options', WP_PVP::$options) ) {
			echo('<span style="color:green">' . __('Updated Options Success', WP_PVP::$textdomain) . '</span>');
		}
	}

	private static function reset_setting() {
		if( update_option('PVP_options', WP_PVP::$default_options) ) {
			echo('<span style="color:green">' . __('Reset Optionsto Default Success', WP_PVP::$textdomain) . '</span>');
		}
	}

	public static function uninstall_all_data() {
		global $wpdb;
		$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->postviews_plus);
		$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->postviews_plus_reflash);
		foreach( self::$views_settings as $setting ) {
			delete_option($setting);
		}
		foreach( self::$views_postmetas as $postmeta ) {
			$wpdb->delete($wpdb->postmeta, array('meta_key' => $postmeta), array('%s'));
		}
		deactivate_plugins(WP_PVP_PLUGIN_BASENAME);
		wp_redirect('plugins.php');
		exit();
	}

	public static function setting() {
		global $wpdb;
		
		if( isset($_POST['wp-pvp-setting']) && wp_verify_nonce($_POST['wp-pvp-setting'], 'wp-pvp-setting') ) {
			if( !empty($_POST['Update']) ) {
				self::update_setting();
				WP_PVP::$options = get_option('PVP_options', WP_PVP::$options);
			}
			if( !empty($_POST['Default']) ) {
				self::reset_setting();
				WP_PVP::$options = get_option('PVP_options', WP_PVP::$options);
			}
		}
		?>
		<?php if(!empty($text)) { echo '<div id="message" class="updated fade"><p>'.$text.'</p></div>'; } ?>
		<div class="wrap"><form method="post" action="">
			<h2><?php _e('Post Views Plus Options', WP_PVP::$textdomain); ?></h2>
			<h3 class="title"><?php _e('Basic Options', WP_PVP::$textdomain); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row"><?php _e('Count Views From:', WP_PVP::$textdomain); ?></th>
					<td>
						<select name="views_count" size="1">
							<option value="0"<?php selected('0', WP_PVP::$options['count']); ?>><?php _e('Everyone', WP_PVP::$textdomain); ?></option>
							<option value="1"<?php selected('1', WP_PVP::$options['count']); ?>><?php _e('Guests Only', WP_PVP::$textdomain); ?></option>
							<option value="2"<?php selected('2', WP_PVP::$options['count']); ?>><?php _e('Registered Users Only', WP_PVP::$textdomain); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e('Reflash check:', WP_PVP::$textdomain); ?></th>
					<td>
						<select name="views_check_reflash" size="1">
							<option value="0"<?php selected('0', WP_PVP::$options['check_reflash']); ?>><?php _e('Close', WP_PVP::$textdomain); ?></option>
							<option value="1"<?php selected('1', WP_PVP::$options['check_reflash']); ?>><?php _e('Open', WP_PVP::$textdomain); ?></option>
						</select>
						<?php _e('Check is based on IP.', WP_PVP::$textdomain); ?><br>
						<?php _e('Reflash timeout:', WP_PVP::$textdomain); ?>
						<input type="text" id="views_timeout" name="views_timeout" size="10" value="<?php echo(WP_PVP::$options['timeout']); ?>" /><?php _e('second.', WP_PVP::$textdomain); ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e('Views Template:', WP_PVP::$textdomain); ?></th>
					<td>
						<?php _e('All views:', WP_PVP::$textdomain); ?>
						<input type="text" id="views_template_template" name="views_template_template" size="70" value="<?php echo htmlspecialchars(stripslashes(WP_PVP::$options['template'])); ?>" /><br>
						<?php _e('Allowed Variables:', WP_PVP::$textdomain); ?> - %VIEW_COUNT%<br><br>
						<?php _e('Only user views:', WP_PVP::$textdomain); ?>
						<input type="text" id="views_template_user_template" name="views_template_user_template" size="70" value="<?php echo htmlspecialchars(stripslashes(WP_PVP::$options['user_template'])); ?>" /><br>
						<?php _e('Allowed Variables:', WP_PVP::$textdomain); ?> - %VIEW_COUNT%<br><br>
						<?php _e('Only bot views:', WP_PVP::$textdomain); ?>
						<input type="text" id="views_template_bot_template" name="views_template_bot_template" size="70" value="<?php echo htmlspecialchars(stripslashes(WP_PVP::$options['bot_template'])); ?>" /><br>
						<?php _e('Allowed Variables:', WP_PVP::$textdomain); ?> - %VIEW_COUNT%
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e('Most Viewed Template:', WP_PVP::$textdomain); ?></th>
					<td>
						<textarea cols="65" rows="4"  id="views_template_most_viewed" name="views_template_most_viewed"><?php echo htmlspecialchars(stripslashes(WP_PVP::$options['most_viewed_template'])); ?></textarea><br>
						<?php _e('Allowed Variables:', WP_PVP::$textdomain); ?> - %VIEW_COUNT% - %POST_TITLE% - %POST_EXCERPT% - %POST_CONTENT% - %POST_DATE% - %POST_URL% - %POST_THUMBNAIL%<br><br>
						<?php _e('Size of post thumbnail: ', WP_PVP::$textdomain); _e('Width: ', WP_PVP::$textdomain); ?> <input type="text" id="set_thumbnail_size_w" name="set_thumbnail_size_w" size="5" value="<?php echo(WP_PVP::$options['set_thumbnail_size_w']); ?>" />
						<?php _e('Height: ', WP_PVP::$textdomain); ?> <input type="text" id="set_thumbnail_size_h" name="set_thumbnail_size_h" size="5" value="<?php echo(WP_PVP::$options['set_thumbnail_size_h']); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e('BOT User_agent:', WP_PVP::$textdomain); ?></th>
					<td>
						<textarea cols="30" rows="<?php echo(count(WP_PVP::$options['botagent'])+1); ?>"  id="views_botagent" name="views_botagent"><?php echo htmlspecialchars(stripslashes(implode("\n",WP_PVP::$options['botagent']))); ?></textarea><br>
						<?php _e('For each BOT user_agent one line.', WP_PVP::$textdomain); ?>
					</td>
				</tr>
			</table>
			<p>&nbsp;</p>
			<h3 class="title"><?php _e('Display Options', WP_PVP::$textdomain); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row"><?php _e('Home Page:', WP_PVP::$textdomain); ?></th>
					<td>
						<select name="views_display_home" size="1">
							<option value="0"<?php selected('0', WP_PVP::$options['display_home']); ?>><?php _e('Display to everyone', WP_PVP::$textdomain); ?></option>
							<option value="1"<?php selected('1', WP_PVP::$options['display_home']); ?>><?php _e('Display to registered users only', WP_PVP::$textdomain); ?></option>
							<option value="2"<?php selected('2', WP_PVP::$options['display_home']); ?>><?php _e('Don\'t display on home page', WP_PVP::$textdomain); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e('Singe Posts:', WP_PVP::$textdomain); ?></th>
					<td>
						<select name="views_display_single" size="1">
							<option value="0"<?php selected('0', WP_PVP::$options['display_single']); ?>><?php _e('Display to everyone', WP_PVP::$textdomain); ?></option>
							<option value="1"<?php selected('1', WP_PVP::$options['display_single']); ?>><?php _e('Display to registered users only', WP_PVP::$textdomain); ?></option>
							<option value="2"<?php selected('2', WP_PVP::$options['display_single']); ?>><?php _e('Don\'t display on single posts', WP_PVP::$textdomain); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e('Pages:', WP_PVP::$textdomain); ?></th>
					<td>
						<select name="views_display_page" size="1">
							<option value="0"<?php selected('0', WP_PVP::$options['display_page']); ?>><?php _e('Display to everyone', WP_PVP::$textdomain); ?></option>
							<option value="1"<?php selected('1', WP_PVP::$options['display_page']); ?>><?php _e('Display to registered users only', WP_PVP::$textdomain); ?></option>
							<option value="2"<?php selected('2', WP_PVP::$options['display_page']); ?>><?php _e('Don\'t display on pages', WP_PVP::$textdomain); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e('Archive Pages:', WP_PVP::$textdomain); ?></th>
					<td>
						<select name="views_display_archive" size="1">
							<option value="0"<?php selected('0', WP_PVP::$options['display_archive']); ?>><?php _e('Display to everyone', WP_PVP::$textdomain); ?></option>
							<option value="1"<?php selected('1', WP_PVP::$options['display_archive']); ?>><?php _e('Display to registered users only', WP_PVP::$textdomain); ?></option>
							<option value="2"<?php selected('2', WP_PVP::$options['display_archive']); ?>><?php _e('Don\'t display on archive pages', WP_PVP::$textdomain); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e('Search Pages:', WP_PVP::$textdomain); ?></th>
					<td>
						<select name="views_display_search" size="1">
							<option value="0"<?php selected('0', WP_PVP::$options['display_search']); ?>><?php _e('Display to everyone', WP_PVP::$textdomain); ?></option>
							<option value="1"<?php selected('1', WP_PVP::$options['display_search']); ?>><?php _e('Display to registered users only', WP_PVP::$textdomain); ?></option>
							<option value="2"<?php selected('2', WP_PVP::$options['display_search']); ?>><?php _e('Don\'t display on search pages', WP_PVP::$textdomain); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e('Other Pages:', WP_PVP::$textdomain); ?></th>
					<td>
						<select name="views_display_other" size="1">
							<option value="0"<?php selected('0', WP_PVP::$options['display_other']); ?>><?php _e('Display to everyone', WP_PVP::$textdomain); ?></option>
							<option value="1"<?php selected('1', WP_PVP::$options['display_other']); ?>><?php _e('Display to registered users only', WP_PVP::$textdomain); ?></option>
							<option value="2"<?php selected('2', WP_PVP::$options['display_other']); ?>><?php _e('Don\'t display on other pages', WP_PVP::$textdomain); ?></option>
						</select>
					</td>
				</tr>
			</table>
			<p><?php _e('These options specify where the view counts should be displayed and to whom.<br>Note that the theme files must contain a call to <code>the_views()</code> in order for any view count to be displayed.', WP_PVP::$textdomain); ?></p>
			<p class="submit">
				<input type="submit" name="Update" class="button-primary" value="<?php _e('Save Changes', WP_PVP::$textdomain); ?>" />
				<input type="submit" name="Default" class="button-primary" value="<?php _e('Reset to Default', WP_PVP::$textdomain); ?>" />
				<?php wp_nonce_field('wp-pvp-setting', 'wp-pvp-setting'); ?>
			</p>
		</form></div>


		<h2><?php _e('Uninstall WP-PostViews Plus', WP_PVP::$textdomain); ?></h2>
		<p><?php _e('Deactivating WP-PostViews Plus plugin does not remove any data that may have been created, such as the views data. To completely remove this plugin, you can uninstall it here.', WP_PVP::$textdomain); ?></p>
		<div style="color: red">
			<h3 class="title"><?php _e('WARNING:', WP_PVP::$textdomain); ?></h3>
			<?php _e('Once uninstalled, this cannot be undone. You should use a Database Backup plugin of WordPress to back up all the data first.', WP_PVP::$textdomain); ?>
			<p>
				<?php printf(__('The database table <strong>%s</strong> will be DELETED.', WP_PVP::$textdomain), $wpdb->postviews_plus); ?><br>
				<?php printf(__('The database table <strong>%s</strong> will be DELETED.', WP_PVP::$textdomain), $wpdb->postviews_plus_reflash); ?>
			</p>
			<?php _e('The following WordPress Options/PostMetas will be DELETED:', WP_PVP::$textdomain); ?><br>
			<?php _e('WordPress Options', WP_PVP::$textdomain); ?><br>
			<ol>
				<?php foreach( self::$views_settings as $settings) {
					echo '<li>'.$settings.'</li>'."\n";
				} ?>
			</ol>
			<?php _e('WordPress PostMetas', WP_PVP::$textdomain); ?><br>
			<ol>
				<?php foreach( self::$views_postmetas as $postmeta ) {
					echo '<li>'.$postmeta.'</li>'."\n";
				} ?>
			</ol>
		</div>
		<form method="post" action="">
			<input type="checkbox" name="uninstall_views_yes" value="yes" />&nbsp;<?php _e('Yes', WP_PVP::$textdomain); ?>
			<input type="submit" name="do" value="<?php _e('UNINSTALL WP-PostViews Plus', WP_PVP::$textdomain); ?>" class="button" onclick="return confirm('<?php _e('You Are About To Uninstall WP-PostViews Plus From WordPress.\nThis Action Is Not Reversible.\n\n Choose [Cancel] To Stop, [OK] To Uninstall.', WP_PVP::$textdomain); ?>')" />
			<?php wp_nonce_field('wp-pvp-uninstall', 'wp-pvp-uninstall'); ?>
		</form>

		<h2><?php _e('Thank', WP_PVP::$textdomain); ?></h2>
		<p><?php _e('Translation contributors', WP_PVP::$textdomain); ?>：</p>
		<p>zh_CN(简体中文) By ddbiz</p>
		<?php
	}
}