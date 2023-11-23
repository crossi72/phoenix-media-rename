<?php

define("actionRename", "rename");
define("actionRenameRetitle", "rename_retitle");
define("success", "pmr_renamed");

class Phoenix_Media_Rename {

	private $is_media_rename_page;
	private $nonce_printed;

	/**
	 * Initializes the plugin
	 */
	function __construct() {
		$post = isset($_REQUEST['post']) ? get_post($_REQUEST['post']) : NULL;
		$is_media_edit_page = $post && $post->post_type == 'attachment' && $GLOBALS['pagenow'] == 'post.php';
		$is_media_listing_page = $GLOBALS['pagenow'] == 'upload.php';
		$this->is_media_rename_page = $is_media_edit_page || $is_media_listing_page;
	}

	/**
	 * Adds the "Filename" column at the media posts listing page
	 *
	 * @param [type] $columns
	 * @return void
	 */
	function add_filename_column($columns) {
		$columns['filename'] = 'Filename';
		return $columns;
	}

	/**
	 * Adds the "Filename" column content at the media posts listing page
	 *
	 * @param [type] $column_name
	 * @param [type] $post_id
	 * @return void
	 */
	function add_filename_column_content($column_name, $post_id) {
		if ($column_name == 'filename') {
			$file_parts = $this->get_file_parts($post_id);
			echo $this->get_filename_field($post_id, $file_parts['filename'], $file_parts['extension']);
		}
	}

	/**
	 * Add the "Filename" field to the Media form
	 *
	 * @param [type] $form_fields
	 * @param [type] $post
	 * @return array form fields
	 */
	function add_filename_field($form_fields, $post) {
		if (isset($GLOBALS['post']) && $GLOBALS['post']->post_type=='attachment') {
			$file_parts=$this->get_file_parts($GLOBALS['post']->ID);
			$form_fields['mr_filename']=array(
				'label' => __('Filename', 'phoenix-media-rename'),
				'input' => 'html',
				'html' => $this->get_filename_field($GLOBALS['post']->ID, $file_parts['filename'], $file_parts['extension'])
			);
		}
		return $form_fields;
	}

	/**
	 * Makes sure that the success message will be shown on bulk rename
	 *
	 * @return void
	 */
	function handle_bulk_pnx_rename_form_submit() {
		if ( array_search(constant("actionRename"), $_REQUEST, true) !== FALSE || array_search(constant("actionRenameRetitle"), $_REQUEST, true) !== FALSE ) {
			wp_redirect( add_query_arg( array(constant("success") => 1), wp_get_referer() ) );
			exit;
		}
	}

	/**
	 * Shows bulk rename success notice
	 *
	 * @return void
	 */
	function show_bulk_pnx_rename_success_notice() {
		if( isset($_REQUEST[constant("success")]) ) {
			echo '<div class="updated"><p>'.__('Medias successfully renamed!', 'phoenix-media-rename').'</p></div>';
		}
	}

	/**
	 * Print the JS code only on media.php and media-upload.php pages
	 *
	 * @return void
	 */
	function print_js() {
		if ($this->is_media_rename_page) {
			wp_enqueue_script('phoenix-media-rename', plugins_url('js/scripts.min.js', __FILE__), array('jquery'));
			?>

			<script type="text/javascript">
				MRSettings = {
					'labels': {
						'<?php echo constant("actionRename") ?>': '<?php echo  __('Rename', 'phoenix-media-rename') ?>',
						'<?php echo constant("actionRenameRetitle") ?>': '<?php echo  __('Rename & Retitle', 'phoenix-media-rename') ?>'
					}
				};
			</script>

			<?php
		}
	}

	/**
	 * Print the CSS styles only on media.php and media-upload.php pages
	 *
	 * @return void
	 */
	function print_css() {
		if ($this->is_media_rename_page) {
			wp_enqueue_style('phoenix-media-rename', plugins_url('css/style.css', __FILE__));
		}
	}

	/**
	 * Prints the "Filename" textfield
	 *
	 * @param [type] $post_id
	 * @param [type] $filename
	 * @param [type] $extension
	 * @return void
	 */
	function get_filename_field($post_id, $filename, $extension) {
		if (!isset($this->nonce_printed)) $this->nonce_printed=0;
		ob_start(); ?>

			<div class="phoenix-media-rename">
				<input type="text" class="text" autocomplete="post_title" value="<?php echo $filename ?>" title="<?php echo $filename ?>" data-post-id="<?php echo $post_id ?>" />
				<span class="file_ext">.<?php echo $extension ?></span>
				<span class="loader"></span>
				<span class="success"></span>
				<span class="error"></span>
				<?php if (!$this->nonce_printed) {
					wp_nonce_field('phoenix_media_rename', '_mr_wp_nonce');
					$this->nonce_printed++;
				} ?>
			</div>

		<?php return ob_get_clean();
	}

	/**
	 * Handles AJAX rename queries
	 *
	 * @return void
	 */
	function ajax_pnx_rename() {
		if (check_ajax_referer('phoenix_media_rename', '_wpnonce', 0)) {
			$retitle = $_REQUEST['type'] == constant("actionRenameRetitle");
			echo $this->do_rename($_REQUEST['post_id'], $_REQUEST['new_filename'], $retitle);
		}
		exit;
	}

	/**
	 * retrive boolean option value
	 *
	 * @param [array] $options
	 * @param [string] $name
	 * @return void
	 */
	static function get_option_boolean($options, $name ){
		if ( isset($options[$name]) ){
			if ( $options[$name] ) {
				return true;
			}else{
				return false;
			}
		} else {
			// default
			return true;
		}
	}

	/**
	 * Handles the actual rename process
	 *
	 * @param [type] $attachment_id
	 * @param [type] $new_filename
	 * @param integer $retitle
	 * @return void
	 */
	static function do_rename($attachment_id, $new_filename, $retitle = 0) {
		// Variables
		$options = get_option( 'pmr_options' );
		$post = get_post($attachment_id);
		$file_parts = self::get_file_parts($attachment_id);
		$file_path = $file_parts['filepath'];
		$file_subfolder = $file_parts['subfolder'];
		$file_old_filename = $file_parts['filename'];
		$file_original_filename = $file_parts['originalfilename'];
		$file_filename_ends_with = $file_parts['endswith'];
		$file_extension = $file_parts['extension'];
		$file_edited = $file_parts['edited'];
		
		global $wpdb;

		$option_update_revisions = self::get_option_boolean( $options, 'pmr_update_revisions' );
		$option_remove_accents = self::get_option_boolean( $options, 'pmr_remove_accents' );

		//restore '-scaled' filename part if user removed it (due to poor code implementation in WordPress core)
		if ( ( $file_edited ) && ( $file_filename_ends_with == '-scaled' ) && ! (self::ends_with($new_filename, '-scaled' )) ){
			$new_filename = $new_filename . $file_filename_ends_with;
		}

		//sanitizing file name (using sanitize_title because sanitize_file_name doesn't remove accents)
		if ($option_remove_accents){
			$new_filename = sanitize_file_name( remove_accents( $new_filename ) );
		} else{
			$new_filename = sanitize_file_name( $new_filename );
		}

		$file_abs_path = $file_path . $file_old_filename . '.' .$file_extension;
		$file_abs_dir = $file_path;
		
		$file_rel_path = $file_subfolder . $file_old_filename . '.' .$file_extension;

		$new_filename_unsanitized = $new_filename;
		$new_file_rel_path = preg_replace('~[^/]+$~', $new_filename . '.' . $file_extension, $file_rel_path);
		$new_file_abs_path = preg_replace('~[^/]+$~', $new_filename . '.' . $file_extension, $file_abs_path);

		if ( is_plugin_active( 'amazon-s3-and-cloudfront/wordpress-s3.php' ) ) {
			//plugin is activated
			add_filter( 'as3cf_get_attached_file_copy_back_to_local', '__return_true' );
		} 

		//attachment miniatures
		$searches = self::get_attachment_urls($attachment_id, '', $file_edited, Operation::search);

		//Validations

		//check if old file still exists
		if ( ! file_exists($file_abs_path) ) return __('Can\'t find original file in the folder. Tried to rename ' . $file_abs_path, 'phoenix-media-rename');

		//check if post containing media file exists
		if (!$post) return __('Post with ID ' . $attachment_id . ' does not exist!');

		//check if type of post containing media file is "attachment"
		if ($post && $post->post_type != 'attachment') return __('Post with ID ' . $attachment_id . ' is not an attachment!', 'phoenix-media-rename');

		//check if new filename has been compiled
		if (!$new_filename) return __('The field is empty!', 'phoenix-media-rename');

		//check if new filename contains bad characters
		if ($option_remove_accents){
			if ($new_filename != sanitize_file_name( remove_accents( $new_filename ) )) return __('Bad characters or invalid filename!', 'phoenix-media-rename');
		}else{
			if ($new_filename != sanitize_file_name( $new_filename ) ) return __('Bad characters or invalid filename!', 'phoenix-media-rename');
		}

		//check if destination folder already contains a file with the target filename
		if (file_exists($new_file_abs_path)) return __('A file with that name already exists in the containing folder!', 'phoenix-media-rename');

		//check if destination folder is writable
		if (!is_writable($file_abs_dir)) return __('The media containing directory is not writable!', 'phoenix-media-rename');

		// Change the attachment post
		$post_changes['ID'] = $post->ID;
		$post_changes['guid'] = preg_replace('~[^/]+$~', $new_filename . '.' . $file_extension, $post->guid);
		$post_changes['post_title'] = ($retitle) ? self::filename_to_title($new_filename_unsanitized) : $post->post_title;
		$post_changes['post_name'] = wp_unique_post_slug($new_filename, $post->ID, $post->post_status, $post->post_type, $post->post_parent);
		wp_update_post($post_changes);

		self::delete_files($attachment_id, $file_old_filename, $file_extension);

		// Change attachment post metas & rename files
		if ( !@rename($file_abs_path, $new_file_abs_path) ) return __('File renaming error! Tryed to rename ' . $file_abs_path . ' in ' . $new_file_abs_path);

		update_post_meta($attachment_id, '_wp_attached_file', $new_file_rel_path);

		$metas = self::update_metadata(wp_get_attachment_metadata($attachment_id), wp_generate_attachment_metadata($attachment_id, $new_file_abs_path));

		wp_update_attachment_metadata($attachment_id, $metas);

		// Replace the old with the new media link in the content of all posts and metas
		$replaces = self::get_attachment_urls($attachment_id, $file_filename_ends_with, $file_edited, Operation::replace);

		$i = 0;
		$post_types = get_post_types();

		if ( !$option_update_revisions ) {
			unset( $post_types ['revision']);
		}

		unset( $post_types['attachment'] );
		
		while ( $posts = get_posts(array( 'post_type' => $post_types, 'post_status' => 'any', 'numberposts' => 100, 'offset' => $i * 100 )) ) {
			foreach ($posts as $post) {
				// Updating post content if necessary
				$new_post = array( 'ID' => $post->ID );
				$new_post['post_content'] = str_replace('\\', '\\\\', $post->post_content);
				$new_post['post_content'] = str_replace($searches, $replaces, $new_post['post_content']);
				if ($new_post['post_content'] != $post->post_content) wp_update_post($new_post);

				// Updating post metas if necessary
				$metas = get_post_meta($post->ID);
				foreach ($metas as $key => $meta) {
					switch ($key){
						case '_elementor_css':
							//delete elementor css, it will be generated at first page visit
							//don't need to check if plugin is active because the query is safe
							$table_name = $wpdb->prefix . 'postmeta';
							$wpdb->query( 
								$wpdb->prepare( 'DELETE FROM ' . $table_name . '
									WHERE post_id = %d
									AND meta_key = %s',
									array($post->ID, '_elementor_css')
								)
							);
						break;
						case '_elementor_data':
							//update elementor data, it will be used to regenerate css file
							//don't need to check if plugin is active because the query is safe
							$table_name = $wpdb->prefix . 'postmeta';

							for ( $i = 0; $i < sizeof ($searches); $i++ ){
								$wpdb->query( 
									$wpdb->prepare( 'UPDATE ' . $table_name . '
										SET meta_value = REPLACE (meta_value, %s, %s)
										WHERE post_id = %d
										AND meta_key = %s',
										array(str_replace( '/', '\/', $searches[$i] ) , str_replace( '/', '\/', $replaces[$i]), $post->ID, $key)
									)
								);
							}
						break;
						default:
							//update wp_postmeta
							$meta[0] = self::unserialize_deep($meta[0]);
							$new_meta = self::replace_media_urls($meta[0], $searches, $replaces);
							if ($new_meta != $meta[0]) update_post_meta($post->ID, $key, $new_meta, $meta[0]);
					}
				}
			}

			$i++;
		}

		// Updating options if necessary
		$options = self::get_all_options();
		foreach ($options as $option) {
			$option['value'] = self::unserialize_deep($option['value']);
			$new_option = self::replace_media_urls($option['value'], $searches, $replaces);
			if ($new_option != $option['value']) update_option($option['name'], $new_option);
		}

		if ( is_plugin_active( 'smart-slider-3/smart-slider-3.php' ) ) {
			//plugin is activated
			//Updating SmartSlider 3 tables
			self::update_smartslider( $file_old_filename, $new_filename, $file_extension );
		}

		return 1;
	}

	/**
	 * Updates metadata array
	 *
	 * @param [array] $old_meta
	 * @param [array] $new_meta
	 * @return array
	 */
	static function update_metadata($old_meta, $new_meta){
		$result = $old_meta;

		//update ShortPixel thumbnails data
		if ( is_plugin_active( 'shortpixel-image-optimiser/wp-shortpixel.php' ) ) {
			foreach ( wp_get_registered_image_subsizes( ) as $image_size_name => $image_size_value){
				for ( $i = 0; $i < count( $result['ShortPixel']['thumbsOptList'] ); $i++){
					if ( $result['ShortPixel']['thumbsOptList'][$i] == $old_meta['sizes'][$image_size_name]['file'] ){
						//this is the same thumbnail: update ShortPixel file name
						$result['ShortPixel']['thumbsOptList'][$i] = $new_meta['sizes'][$image_size_name]['file'];
					}
				}
			}
		}

		foreach ($new_meta as $key => $value) {
			switch ( $key ){
				case 'file':
					//change the file name in meta
					$result[$key] = $value;
					break;
				case 'sizes':
					//change the file name in miniatures
					$result[$key] = $value;
					break;
				default:
					if ( ! array_key_exists($key, $result) ){
						//add missing keys (if needed)
						array_push($result[$key], $value);
						$result[$key] = $value;
					}
			}
		}

		return $result;
	}

	/**
	 * Delete files from upload folder
	 *
	 * @param [integer] $attachment_id
	 * @return void
	 */
	static function delete_files($attachment_id, $original_filename, $extension){
		$uploads_path = wp_upload_dir();
		$uploads_path = $uploads_path['path'];

		foreach (get_intermediate_image_sizes() as $size) {
			$size_data = image_get_intermediate_size($attachment_id, $size);
			
			@unlink( $uploads_path . DIRECTORY_SEPARATOR . $size_data['file'] );
		}
	}

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
		$old_filename = $wpdb->esc_like( $old_filename );

		$filter = '%/'. $old_filename;

		//compose Smart Slider table name
		$tablename = $wpdb->prefix . 'nextend2_smartslider3_slides';

		if (!self::TableExist($tablename)){
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

		if (self::TableExist($tablename)){
			//if table exist, change file name (unnecessary table, does not exit if table is missing)
			$sqlQuery = "UPDATE ". $tablename ." SET image = REPLACE(image, %s, %s) WHERE image LIKE %s";

			$updated = $wpdb->query(
				$wpdb->prepare( 
					$sqlQuery, $old_filename, $new_filename, $filter
				));
		}

		return true;
	}

	/**
	 * Get attachment filename
	 *
	 * @param [integer] $post_id
	 * @return void
	 */
	static function get_filename($post_id) {
		$filename = get_attached_file($post_id);

		return $filename;
	}

	/**
	 * Get attachment filename
	 *
	 * @param [integer] $post_id
	 * @return void
	 */
	static function get_file_parts($post_id) {
		$filename = self::get_filename($post_id);

		return self::file_parts($filename, $post_id);
	}

	/**
	 * Extract filename and extension
	 *
	 * @param [string] $filename
	 * @param [integer] $post_id
	 * @return void
	 */
	static function file_parts($filename, $post_id){
		//read post meta to check if image has been edited
		$post_meta = get_post_meta($post_id, '_wp_attachment_metadata', 1);
		$file_path = wp_upload_dir();

		if ( isset( $post_meta['original_image'] ) ){
			$edited = true;
			$original_filename = $post_meta['original_image'];
		} else {
			$edited = false;
			$original_filename = "";
		}

		//separate filename and extension
		preg_match('~([^/]+)\.([^\.]+)$~', basename($filename), $file_parts);

		// $filepath = $file_path['path'] . '/';
		// $subfolder = $file_path['subdir'] . '/';
		$filepath = str_replace(basename($filename), '', $filename);
		$subfolder = str_replace($file_path['basedir'], '', $filepath);

		//remove first slash from subfolder (it breaks image metadata)
		if ( strlen( $subfolder ) > 0 ){
			if ( substr( $subfolder, 0, 1 ) == '/' ) {
				$subfolder = substr( $subfolder, 1, strlen( $subfolder ) -1 );
			}
		}

		$filename = $file_parts[1];

		//check if filename ends with "-scaled"
		if ( ( $edited ) && ( self::ends_with($file_parts[1], '-scaled' ) ) ) {
			$endsWith = '-scaled';
		} else {
			$endsWith = '';
		}

		$result = array(
			'filepath'			=> $filepath,
			'subfolder'			=> $subfolder,
			'filename'			=> $filename,
			'extension'			=> $file_parts[2],
			'endswith'			=> $endsWith,
			'edited'			=> $edited,
			'originalfilename'	=> $original_filename
		);

		return $result;
	}

	/**
	 * Check if strings ends with a sequence of characters
	 *
	 * @param [string] $haystack
	 * @param [string] $needle
	 * @return void
	 */
	static function ends_with( $haystack, $needle ) {
		$length = strlen( $needle );
		if( !$length ) {
			return true;
		}
		return substr( $haystack, -$length ) === $needle;
	}

	/**
	 * Adds more problematic characters to the "sanitize_file_name_chars" filter
	 *
	 * @param [string] $special_chars
	 * @return void
	 */
	static function add_special_chars($special_chars) {
		return array_merge($special_chars, array('%', '^'));
	}

	/**
	 * Returns the attachment URL and sizes URLs, in case of an image
	 *
	 * @param [integer] $attachment_id
	 * @param [string] $filename_ends_with
	 * @param [boolean] $remove_suffix
	 * @param [Operation] $operation
	 * @return void
	 */
	static function get_attachment_urls($attachment_id, $filename_ends_with, $remove_suffix, $operation) {
		$urls = array( wp_get_attachment_url($attachment_id) );
		// $filename = '';

		if ( wp_attachment_is_image($attachment_id) ) {
			foreach (get_intermediate_image_sizes() as $size) {
				$image = wp_get_attachment_image_src($attachment_id, $size);

				// if ( ( $operation == Operation::replace ) && (remove_suffix) ) {
				// 	// get filename
				// 	preg_match('~([^/]+)(-scaled-)(.)+\.([^\.]+)$~', $image[0], $file_parts);

				// 	if ( $file_parts[2] = '-scaled-' ){
				// 		//image is a miniature, remove -scaled to obtain original filename
				// 		$image[0] = preg_replace('~([^/]+)(-scaled-)(.+)\.([^\.]+)$~', '\1-\3', $image[0]);

				// 		// //get file path
				// 		// $filepath = substr($image[0], 0, strrpos($image[0], '/')) . '/';

				// 		//scaled image, add scaled filename
				// 		$image[0] = $image[0] . '.' . end($file_parts);
				// 	}
				// }

				$urls[] = $image[0];
			}

		}

		return array_unique($urls);
	}

	/**
	 * Convert filename to post title
	 *
	 * @param [string] $filename
	 * @return void
	 */
	static function filename_to_title($filename) {
		return $filename;
	}

	/**
	 * Get all options
	 *
	 * @return void
	 */
	static function get_all_options() {
		return $GLOBALS['wpdb']->get_results("SELECT option_name as name, option_value as value FROM {$GLOBALS['wpdb']->options}", ARRAY_A);
	}

	/**
	 * Replace the media url and fix serialization if necessary
	 *
	 * @param [string] $subj
	 * @param [string] $searches
	 * @param [string] $replaces
	 * @return void
	 */
	static function replace_media_urls($subj, &$searches, &$replaces) {
		$subj = is_object($subj) ? clone $subj : $subj;

		if (!is_scalar($subj) && is_countable($subj) && count($subj)) {
			foreach($subj as &$item) {
				$item = self::replace_media_urls($item, $searches, $replaces);
			}
		} else {
			$subj = is_string($subj) ? str_replace($searches, $replaces, $subj) : $subj;
		}
		
		return $subj;
	}

	/**
	 * Unserializes a variable until reaching a non-serialized value
	 *
	 * @param [type] $var
	 * @return void
	 */
	static function unserialize_deep($var) {
		while ( is_serialized($var) ) {
			$var = @unserialize($var);
		}

		return $var;
	}
		
	/**
	 * Check if table exists
	 *
	 * @param [type] $tablename
	 * @return boolean
	 */
	static function TableExist($tablename){
		global $wpdb;

		if($wpdb->get_var("SHOW TABLES LIKE '$tablename'") == $tablename){
			//table is not present
			return true;
		}else{
			return false;
		}
	}
}

/**
 * Polyfill for compatibility with old PHP versions (less than 7)
 */
if (!function_exists('is_countable')) {
	function is_countable($var) {
		return (is_array($var) || $var instanceof Countable);
	}
}

abstract class Operation
{
	const search = 0;
	const replace = 1;
}
