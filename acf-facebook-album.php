<?php

/*
Plugin Name:       Advanced Custom Fields: Facebook Album Field
Plugin URI:        https://github.com/Graffiti2000Srl/acf-facebook-album
Description:       Adds the Facebook Album field
Version:           0.2
Author:            Graffiti2000 s.r.l.
Author URI:        http://www.graffiti2000.com/
License:           GPL
Copyright:         Graffiti2000 s.r.l.
GitHub Plugin URI: https://github.com/Graffiti2000Srl/acf-facebook-album
GitHub Branch:     master
*/

define('ACF_FA_PATH', dirname(__FILE__));

class acf_facebook_album_plugin
{
	/**
	 * Array of settings
	 * 
	 * @var array
	 */
	public $settings;


	/**
	 *  Constructor
	 *
	 *  @since 0.1
	 *  @created: 2014/03/20
	 */

	public function __construct()
	{
		// vars
		$this->settings = array(
			'version'  => '0.1',
			'basename' => plugin_basename(__FILE__),
		);

		// actions
		add_action('acf/register_fields', array($this, 'register_fields'));
	}


	/**
	 *  Register the field to ACF plugin
	 *
	 *  @since: 0.1
	 *  @created: 2014/03/20
	 */

	public function register_fields()
	{
		require_once(ACF_FA_PATH . '/facebook-album.php');
	}

}

new acf_facebook_album_plugin();

?>
