<?php

class PmrSettingsPage
{
	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $options;

	/**
	 * Start up
	 */
	public function __construct()
	{
		add_action( 'admin_menu', array( $this, 'add_pmr_settings_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
		add_option( 'pmr_options', array('pmr_update_revisions' => true), '', 'yes' );
		add_option( 'pmr_options', array('pmr_remove_accents' => true), '', 'yes' );
		add_filter( 'plugin_action_links_'. PMR_BASENAME, array($this, 'pmr_add_action_links') );
	}

	function pmr_add_action_links ( $links ) {
		$mylinks = array(
			'<a href="' . admin_url( 'options-general.php?page=pmr-setting-admin' ) . '">'. __('Settings', 'phoenix-media-rename') .'</a>',
			'<a href="https://paypal.me/crossi72" target="_blank">'. __('Donate to this plugin', 'phoenix-media-rename') .'</a>',
		);
		return array_merge( $links, $mylinks );
	}

	/**
	 * Add options page
	 */
	public function add_pmr_settings_page()
	{
		// This page will be under "Settings"
		add_options_page(
			'Settings Admin', 
			'Phoenix Media Rename', 
			'manage_options', 
			'pmr-setting-admin', 
			array( $this, 'create_admin_page' )
		);
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page()
	{
		// Set class property
		$this->options = get_option( 'pmr_options' );
		?>
		<div class="wrap">
			<h1>Phoenix Media Rename Settings</h1>
			<form method="post" action="options.php">
			<?php
				// This prints out all hidden setting fields
				settings_fields( 'pmr_option_group' );
				do_settings_sections( 'pmr-setting-admin' );
				submit_button();
			?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init()
	{
		register_setting(
			'pmr_option_group', // Option group
			'pmr_options', // Option name
			array( $this, 'sanitize' ) // Sanitize
		);

		add_settings_section(
			'setting_section_revisions_media_rename', // pmr_update_revisions
			__('Revisions', 'phoenix-media-rename'), // Title
			array( $this, 'print_section_revisions_info' ), // Callback
			'pmr-setting-admin' // Page
		);

		add_settings_field(
			'pmr_update_revisions', // ID
			__('Update Revisions', 'phoenix-media-rename'), // Title 
			array( $this, 'pmr_update_revisions_callback' ), // Callback
			'pmr-setting-admin', // Page
			'setting_section_revisions_media_rename' // Section
		);

		add_settings_section(
			'setting_section_remove_accents_media_rename', // pmr_update_revisions
			__('Accents', 'phoenix-media-rename'), // Title
			array( $this, 'print_remove_accent_section_info' ), // Callback
			'pmr-setting-admin' // Page
		);

		add_settings_field(
			'pmr_remove_accents', // ID
			__('Remove accents', 'phoenix-media-rename'), // Title 
			array( $this, 'pmr_remove_accents_callback' ), // Callback
			'pmr-setting-admin', // Page
			'setting_section_remove_accents_media_rename' // Section
		);

	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 */
	public function sanitize( $input )
	{
	if( !is_array( $input ) || empty( $input ) || ( false === $input ) ) {
		$new_input['pmr_update_revisions'] = false;
		$new_input['pmr_remove_accents'] = false;
	}

	if( isset( $input['pmr_update_revisions'] ) && ( 1 == $input['pmr_update_revisions'] ) ){
		$new_input['pmr_update_revisions'] = true;
	} else {
		$new_input['pmr_update_revisions'] = false;
	}

	if( isset( $input['pmr_remove_accents'] ) && ( 1 == $input['pmr_remove_accents'] ) ){
		$new_input['pmr_remove_accents'] = true;
	} else {
		$new_input['pmr_remove_accents'] = false;
	}

	return $new_input;
	}

	/** 
	 * Print the Section text
	 */
	public function print_section_revisions_info()
	{
		print __('Check to processing revisions, uncheck to avoid processing revisions:', 'phoenix-media-rename');
	}

	/** 
	 * Print the Section text
	 */
	public function print_remove_accent_section_info()
	{
		print __('Check to remove accents from file name, uncheck to leave accents:', 'phoenix-media-rename');
	}

	/** 
	 * Get the settings option array and print one of its values
	 */
	public function pmr_update_revisions_callback()
	{
		echo '<input type="checkbox" id="pmr_update_revisions" name="pmr_options[pmr_update_revisions]" value="1" '. checked(1, isset( $this->options['pmr_update_revisions'] ) ? esc_attr( $this->options['pmr_update_revisions']) : 1, false) . '//>';
	}

	/** 
	 * Get the settings option array and print one of its values
	 */
	public function pmr_remove_accents_callback()
	{
		echo '<input type="checkbox" id="pmr_remove_accents" name="pmr_options[pmr_remove_accents]" value="1" '. checked(1, isset( $this->options['pmr_remove_accents'] ) ? esc_attr( $this->options['pmr_remove_accents']) : 1, false) . '//>';
	}

}

if( is_admin() )
	$pmr_settings_page = new PmrSettingsPage();