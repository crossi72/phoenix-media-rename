<?php
/*
* Phoenix Media Rename main class
*
*/

#region constants

define("pluginArchivarixExternalImagesImporter", "archivarix-external-images-importer/archivarix-external-images-importer.php");
define("pluginAmazonS3AndCloudfront", "amazon-s3-and-cloudfront/wordpress-s3.php");
define("pluginSmartSlider3", "smart-slider-3/smart-slider-3.php");
define("pluginShortpixelImageOptimiser", "shortpixel-image-optimiser/wp-shortpixel.php");
define("pluginWPML", "sitepress-multilingual-cms/sitepress.php");
define("pluginRedirection", "redirection/redirection.php");
define("pluginRankMath", "seo-by-rank-math/rank-math.php");
define("pluginElementor", "elementor/elementor.php");

abstract class elementor_element
{
	const css = 0;
	const data = 1;
}


#endregion

class pmr_plugins{

	/**
	 * check if plugin is active
	 *
	 * @param string $plugin_name
	 * @return boolean
	 */
	static function is_plugin_active($plugin_name){
		if(in_array($plugin_name, apply_filters('active_plugins', get_option('active_plugins')))){ 
			return true;
		} else {
			return false;
		}
	}

#region Smart Slider compatibility

	/**
	 * Update Smart Slider 3 custom table
	 *
	 * @param string $old_filename
	 * @param string $new_filename
	 * @param string $extension
	 * @return void
	 */
	static function update_smartslider($old_filename, $new_filename, $extension){
		global $wpdb;

		//compose file names
		$old_filename = $old_filename . '.' . $extension;
		$new_filename = $new_filename . '.' . $extension;

		if(empty($old_filename) || empty($new_filename))
		{
			return false;
		}
		if ($old_filename == ''){
			return false;
		}

		//escape filename for use in LIKE statement
		$old_filename = $wpdb->esc_like($old_filename);

		$filter = '%/'. $old_filename;

		//compose Smart Slider table name
		$tablename = $wpdb->prefix . 'nextend2_smartslider3_slides';

		if (! pmr_lib::table_exist($tablename)){
			//if table does not exist, exit and return false
			return false;
		}else{
			//if table exist, change file name
			$sqlQuery = "UPDATE ". $tablename ." SET thumbnail = REPLACE(thumbnail, %s, %s), params = REPLACE(params, %s, %s) WHERE thumbnail LIKE %s";

			$updated = $wpdb->query(
				$wpdb->prepare(
					$sqlQuery, $old_filename, $new_filename, $old_filename, $new_filename, $filter
				));
		}

		$tablename = $wpdb->prefix . 'nextend2_image_storage';

		if (pmr_lib::table_exist($tablename)){
			//if table exist, change file name (unnecessary table, does not exit if table is missing)
			$sqlQuery = "UPDATE ". $tablename ." SET image = REPLACE(image, %s, %s) WHERE image LIKE %s";

			$updated = $wpdb->query(
				$wpdb->prepare(
					$sqlQuery, $old_filename, $new_filename, $filter
				));
		}

		return true;
	}

#endregion

#region WPML compatibility

	/**
	 * Update Smart WPML custom table
	 *
	 * @param string $extension
	 * @return void
	 */
	static function update_wpml($post_id){
		// Get "trid" of the file
		$trid = apply_filters('wpml_element_trid', NULL, $post_id, 'post_attachment');

		if (empty($trid)) {
			//translation not found
		} else {
			//get all translations
			$translations = apply_filters('wpml_get_element_translations', NULL, $trid);

			//iterates through translations to update attachment metadata
			foreach ($translations as $translation) {
				if ($post_id == $translation->element_id) {
					//update filename
					update_post_meta($translation->element_id, '_wp_attached_file', get_post_meta($translation->element_id, '_wp_attached_file', true));

					//update metadata
					update_post_meta($translation->element_id, '_wp_attachment_metadata', get_post_meta($translation->element_id, '_wp_attachment_metadata', true));
				}
			}
		}
	}

#endregion

#region Redirection compatibility

	/**
	 * Add a redirection from the old URL to the NEW one using Redirection plugin
	 *
	 * @param string $old_filename
	 * @param string $new_filename
	 * @param string $extension
	 * @param boolean $option_create_redirection
	 * @return void
	 */
	static function add_redirection($old_filename, $new_filename, $extension, $file_subfolder, $option_create_redirection, $plugin){
		if ($option_create_redirection){
			//option is active

			//fix file name
			if ($file_subfolder){
				$old_filename = $file_subfolder . $old_filename . '.' . $extension;
				$new_filename = $file_subfolder . $new_filename .'.' . $extension;
			} else {
				$old_filename = $old_filename . '.' . $extension;
				$new_filename = $new_filename .'.' . $extension;
			}

			//add upload folder
			if (defined('UPLOADS')) {
				$upload_folder = UPLOADS;

				$old_filename = get_site_url() . '/' . $upload_folder . '/' . $old_filename;
				$new_filename = get_site_url() . '/' . $upload_folder . '/' . $new_filename;
			} else {
				$upload_folder = wp_upload_dir()['baseurl'] . '/';

				$old_filename = $upload_folder . $old_filename;
				$new_filename = $upload_folder . $new_filename;
			}

			$old_filename = str_replace('\\', '/' , $old_filename);
			$new_filename = str_replace('\\', '/' , $new_filename);

			switch ($plugin){
				case constant("pluginRedirection"):
				//Redirection
					try{
						if (class_exists('Red_Item')){
							//include Redirection code
							require_once WP_PLUGIN_DIR . '/redirection/models/group.php';

							$details = [
								'url'			=> $old_filename,
								'action_data'	=> [ 'url' => $new_filename ],
								'action_type'	=> 'url',
								'title'			=> 'Phoenix Media Rename',
								'status'		=> 'enabled',
								'regex'			=> false,
								'group_id'		=> 2, //set group to "updated posts"
								'match_type'	=> 'url',
							];

							//add redirection via Redirection's functions
							$result = Red_Item::create($details);
						}
					}catch(exception $e){
					}

					break;
				case constant("pluginRankMath"):
				//Rank Math SEO
					try{
						$redirection = RankMath\Redirections\Redirection::from(
							[
							'url_to' => $new_filename,
							'header_code' => '301',
							]
							);

						$redirection->set_nocache(true);
						$redirection->add_source($old_filename, 'exact');
						$redirection->add_destination($new_filename);
						$redirection->save();
					}catch(exception $e){
					}
					break;
			}
		}
	}

#endregion

#region ShortPixel compatibility

	/**
	 * Update ShortPixel metadata to avoid another compression of the image after the renaming process
	 *
	 * @param array $result
	 * @param string $key
	 * @param string $old_filename
	 * @param string $new_filename
	 * @return void
	 */
	static function update_shortpixel_metadata($result, $key, $old_filename, $new_filename){
		//check if Shortpixel data contains thumbs data
		if (array_key_exists($key, $result['ShortPixel'])){
			for ($i = 0; $i < count($result['ShortPixel'][$key]); $i++){
					try{
					$shortpixel_meta = $result['ShortPixel'][$key][$i];

					$new_shortpixel_meta = str_replace($old_filename, $new_filename, $shortpixel_meta);

					if ($shortpixel_meta != $new_shortpixel_meta){
						$result['ShortPixel'][$key][$i] = $new_shortpixel_meta;
					}
				}catch(exception $e){

				}
			}
		}

		return $result;
	}

#endregion

#region Elementor compatibility

	/**
	 * update elementor data, it will be used by Elementor to regenerate css file
	 *
	 * @param elementor_element $element
	 * @param integer $post_id
	 * @param string $key
	 * @param array $searches
	 * @param array $replaces
	 * @return void
	 */
	static function update_elementor_data($element, $post_id, $key = '', $searches = '', $replaces = ''){
		global $wpdb;

		if (pmr_plugins::is_plugin_active(constant("pluginElementor"))) {
			$table_name = $wpdb->prefix . 'postmeta';

			switch ($element){
				case elementor_element::css:
					//delete elementor css, it will be generated at first page visit
					try {
						$wpdb->query(
							$wpdb->prepare('DELETE FROM ' . $table_name . '
								WHERE post_id = %d
								AND meta_key = %s',
								array($post_id, '_elementor_css')
							)
						);
					} catch(exception $e){

					}
					break;
				case elementor_element::data:
					try{
						for ($i = 0; $i < sizeof($searches); $i++){
							$wpdb->query(
								$wpdb->prepare('UPDATE ' . $table_name . '
									SET meta_value = REPLACE (meta_value, %s, %s)
									WHERE post_id = %d
									AND meta_key = %s',
									array(str_replace('/', '\/', $searches[$i]) , str_replace('/', '\/', $replaces[$i]), $post_id, $key)
								)
							);
						}
					} catch(exception $e){

					}
					break;
			}
		}
	}

#endregion

}

