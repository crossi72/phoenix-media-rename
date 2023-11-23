<?php

/*
Plugin Name: Phoenix Media Rename
Plugin URI: http://wordpress.org/extend/plugins/phoenix-media-rename/
Description: The Phoenix Media Rename plugin allows you to simply rename your media files, once uploaded.
Version: 2.2.5
Author: crossi72
Author URI: https://eurosoftlab.com
Text Domain: phoenix-media-rename
License: GPL3
{Plugin Name} is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
{Plugin Name} is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>.

Phoenix icon http://sid-raphael.deviantart.com/art/Fire-Phoenix-Full-Feather-192575471 by http://sid-raphael.deviantart.com/ is licenced under https://creativecommons.org/licenses/by-sa/3.0/ 
*/

defined('ABSPATH') or die();

define ('PMR_BASENAME', plugin_basename( __FILE__ ));

require_once('class-media-rename.php');
require_once('pmr-settings.php');

add_action('plugins_loaded', 'phoenix_media_rename_init');
function phoenix_media_rename_init() {
	$mr = new Phoenix_Media_Rename;

	add_filter( 'manage_media_columns', array($mr, 'add_filename_column'), 99);
	add_filter( 'attachment_fields_to_edit', array($mr, 'add_filename_field'), 10, 2 ); 
	add_filter( 'sanitize_file_name_chars', array($mr, 'add_special_chars'), 10, 1 );

	add_action( 'load-upload.php', array($mr, 'handle_bulk_pnx_rename_form_submit') );
	add_action( 'admin_notices', array($mr, 'show_bulk_pnx_rename_success_notice') );
	add_action( 'manage_media_custom_column', array($mr, 'add_filename_column_content'), 10, 2 );
	add_action( 'wp_ajax_phoenix_media_rename', array($mr, 'ajax_pnx_rename') );
	add_action( 'admin_enqueue_scripts', array($mr, 'print_js') );
	add_action( 'admin_enqueue_scripts', array($mr, 'print_css') );
}

add_action( 'plugins_loaded', 'phoenix_media_rename_load_plugin_textdomain' );

function phoenix_media_rename_load_plugin_textdomain() {
	load_plugin_textdomain( 'phoenix-media-rename', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
