<?php

/*
Plugin Name: Phoenix Media Rename
Plugin URI: https://www.eurosoftlab.com/en/phoenix-media-rename/
Description: The Phoenix Media Rename plugin allows you to simply rename your media files, once uploaded.
Version: 3.13.1
Author: crossi72
Author URI: https://eurosoftlab.com
Text Domain: phoenix-media-rename
License: GPL3
Phoenix Media Rename is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Phoenix Media Rename is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>.

Phoenix icon has been generated using Microsoft Designer
*/

defined('ABSPATH') or die();
define('PHOENIX_MEDIA_RENAME_SCHEMA_VERSION', '1.0.1');
define ('PHOENIX_MEDIA_RENAME_BASENAME', plugin_basename(__FILE__));
define ('PHOENIX_MEDIA_RENAME_TEXT_DOMAIN', 'phoenix-media-rename');
define ('PHOENIX_MEDIA_RENAME_TABLE_NAME', 'pmr_status');

require_once('classes/class-media-rename.php');
require_once('classes/class-pmr-options.php');
require_once('classes/class-pmr-settings.php');
require_once('classes/class-lib.php');
require_once('classes/class-pmr-db.php');

//load Phoenix Media Rename only for backend and AJAX calls
if (is_admin()){
	add_action('plugins_loaded', 'phoenix_media_rename_init');

	function phoenix_media_rename_init() {
		if ((isset($_GET['page'])) && ($_GET['page'] == 'mla-menu')){
			//disable Phoenix Media Rename on Media Library Assistant custom library page
			//can't use get_query_var because it works only inside a loop
		} else {
			$mr = new Phoenix_Media_Rename;

			add_filter('manage_media_columns', array($mr, 'add_filename_column'), 99);
			add_filter('attachment_fields_to_edit', array($mr, 'add_filename_field'), 10, 2);
			add_filter('sanitize_file_name_chars', array($mr, 'add_special_chars'), 10, 1);

			add_action('load-upload.php', array($mr, 'handle_bulk_pnx_rename_form_submit'));
			add_action('admin_notices', array($mr, 'show_bulk_pnx_rename_success_notice'));
			add_action('manage_media_custom_column', array($mr, 'add_filename_column_content'), 10, 2);
			add_action('wp_ajax_phoenix_media_rename', array($mr, 'ajax_pnx_rename'));
			add_action('admin_enqueue_scripts', array($mr, 'print_js'));
			add_action('admin_enqueue_scripts', 'phoenix_media_rename_lib::print_options_js');
			add_action('admin_enqueue_scripts', array($mr, 'print_css'));
			add_action('admin_footer', array($mr, 'init_temporary_data'));
		}
	}

	add_action('plugins_loaded', 'phoenix_media_rename_load_plugin_textdomain');

	function phoenix_media_rename_load_plugin_textdomain() {
		load_plugin_textdomain(constant('PHOENIX_MEDIA_RENAME_TEXT_DOMAIN'), FALSE, basename(dirname(__FILE__)) . '/languages/');
	}

	register_uninstall_hook(__FILE__, 'phoenix_media_rename_uninstall');

	/**
	 * Uninstallation hook: it will delete Phoenix Media Rename table from db
	 */
	function phoenix_media_rename_uninstall() {
		//delete Phoenix Media Rename's options
		phoenix_media_rename_db::delete_options();
	}

	register_activation_hook(__FILE__, 'phoenix_media_rename_activate');

	function phoenix_media_rename_activate() {
		add_option('Activated_phoenix_media_rename', 'phoenix-media-rename');
	}

	add_action('in_plugin_update_message-phoenix-media-rename/phoenix-media-rename.php', 'phoenix_media_rename_plugin_update_message', 10, 2);

	function phoenix_media_rename_plugin_update_message($plugin_data, $new_data) {
		if (isset($plugin_data['update']) && $plugin_data['update'] && isset($new_data->upgrade_notice)) {
			printf(
				'<div class="update-message"><p><strong>%s</strong>: %s</p></div>',
				$new_data -> new_version,
				wpautop($new_data -> upgrade_notice)
			);
		}
	}
}
