<?php
/*
* Functions to interact with WordPress database
*
*/

class phoenix_media_rename_db{

	/**
	 * Check if WordPress is installed as single or multiside and delete Phoenix Media Rename table
	 *
	 * @return void
	 */
	static function drop_tables(){
		// is_multisite() check is important here because get_sites() is not available on single site installs.
		if (is_multisite()) {
			//multisite
			foreach (get_sites() as $subsite) {
				//change active site
				switch_to_blog($subsite->blog_id);
				//create table in site database
				self::drop_table();

				restore_current_blog();
			}
		} else {
		//single site
			//create table
			self::drop_table();
		}
	}

	/**
	 * Delete Phoenix Media Rename options from database
	 *
	 * @return void
	 */
	static function delete_options(){
		self::delete_option('pmr_db_version');
		self::delete_option('pmr_options');
		self::delete_option('pmr_table_installed');
		self::delete_option('pmr_update_db_table');
		self::delete_option('pmr_update_revisions');
		self::delete_option('pmr_sanitize_filenames');
		self::delete_option('pmr_remove_accents');
		self::delete_option('pmr_debug_mode');
		self::delete_option('pmr_create_redirection');
		self::delete_option('pmr_serialize_if_filename_present');
		self::delete_option('pmr_filename_lowercase');
		self::delete_option('pmr_filename_header');
		self::delete_option('pmr_filename_trailer');
		self::delete_option('pmr_category_filename_header');
		self::delete_option('pmr_category_filename_trailer');
		self::delete_option('pmr_enable_alttext_integration');
	}

	static private function delete_option($option_name){
		delete_option($option_name);

		// for site options in Multisite
		delete_site_option($option_name);
	}

	/**
	 * Delete Phoenix Media Rename table from database
	 *
	 * @return void
	 */
	static function drop_table(){
		global $wpdb;

		//create sql query
		$sql = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . constant('PHOENIX_MEDIA_RENAME_TABLE_NAME');

		$wpdb->query(
				$sql
			);
	}

	/**
	 * Update db table structure for Phoenix Media Rename values
	 *
	 * @return void
	 */
	static function update_db_table(){
		global $wpdb;

		$table_name = $wpdb->prefix . constant('PHOENIX_MEDIA_RENAME_TABLE_NAME');

		$sql = "CREATE TABLE " . $table_name . " (
			id int(11) NOT NULL AUTO_INCREMENT,
			bulk_filename_header varchar(250) NULL,
			bulk_rename_in_progress int(11) NULL,
			bulk_rename_from_post_in_progress int(11) NULL,
			current_image_index int(11) NULL,
			PRIMARY KEY  (id)
		);";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}

	/**
	 * Create db table for Phoenix Media Rename values
	 *
	 * @return void
	 */
	static function phoenix_media_rename_create_db_table(){
		global $wpdb;

		//set charset
		$charset_collate = $wpdb->get_charset_collate();

		//create sql query
		$sql = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . constant('PHOENIX_MEDIA_RENAME_TABLE_NAME') . ' (
				ID INT NULL DEFAULT 1,
				bulk_filename_header VARCHAR(250) NULL DEFAULT NULL,
				bulk_rename_in_progress INT NULL DEFAULT NULL,
				bulk_rename_from_post_in_progress INT NULL DEFAULT NULL,
				current_image_index INT NULL DEFAULT NULL
			) ' . $charset_collate;

		$wpdb->query(
				$sql
			);
	}
}