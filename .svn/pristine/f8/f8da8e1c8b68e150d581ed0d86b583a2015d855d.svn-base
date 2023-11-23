<?php

/*
Plugin Name: Phoenix Media Rename
Plugin URI: http://wordpress.org/extend/plugins/phoenix-media-rename/
Description: The Phoenix Media Rename plugin allows you to simply rename your media files, once uploaded.
Version: 1.1.0
Author: crossi72
Author URI: http://eurosoftlab.com
Text Domain: phoenix-media-rename
License: This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
         This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
         You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

defined('ABSPATH') or die();

include_once('class-media-rename.php');

add_action('plugins_loaded', 'phoenix_media_rename_init');
function phoenix_media_rename_init() {
	$mr = new Phoenix_Media_Rename;

	add_filter( 'manage_media_columns', array($mr, 'add_filename_column') );
	add_filter( 'attachment_fields_to_edit', array($mr, 'add_filename_field'), 10, 2 ); 
	add_filter( 'sanitize_file_name_chars', array($mr, 'add_special_chars'), 10, 1 );

	add_action( 'load-upload.php', array($mr, 'handle_bulk_rename_form_submit') );
	add_action( 'admin_notices', array($mr, 'show_bulk_rename_success_notice') );
	add_action( 'manage_media_custom_column', array($mr, 'add_filename_column_content'), 10, 2 );
	add_action( 'wp_ajax_phoenix_media_rename', array($mr, 'ajax_rename') );
	add_action( 'admin_enqueue_scripts', array($mr, 'print_js') );
	add_action( 'admin_enqueue_scripts', array($mr, 'print_css') );
}

add_action( 'plugins_loaded', 'phoenix_media_rename_load_plugin_textdomain' );

function phoenix_media_rename_load_plugin_textdomain() {
	load_plugin_textdomain( 'phoenix-media-rename', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}