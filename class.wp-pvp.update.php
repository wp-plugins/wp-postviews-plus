<?php
defined('WP_PVP_VERSION') OR exit('No direct script access allowed');

class WP_PVP_update {
	public static function update() {
		$now_version = get_option('PVP_version');

		if( $now_version === FALSE ) {
			$now_version = '0.0.0';
		}
		if( $now_version == WP_PVP_VERSION ) {
			return;
		}

		if( version_compare($now_version, '2.0.0', '<' ) ) {
			update_option('PVP_version', '2.0.0');
		}
	}

	private static function update_1_0_0() {
	}
}
