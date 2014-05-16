<?php
/**
 * The WordPress Plugin Boilerplate.
 *
 * A foundation off of which to build well-documented WordPress plugins that
 * also follow WordPress Coding Standards and PHP best practices.
 *
 * @package   FlickrImporter
 * @author    Enrique Chavez <noone@tmeister.net>
 * @license   GPL-2.0+
 * @link      http://example.com
 * @copyright 2014
 *
 * @wordpress-plugin
 * Plugin Name:       Flickr Importer
 * Plugin URI:        http://enriquechavez.co
 * Description:       Flickr Importer for WordPress
 * Version:           1.0.0
 * Author:            Enrique Chavez
 * Author URI:        http://github.com/tmeister
 * Text Domain:       flickr-importer-locale
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/tmeister/flickr-importer
 * WordPress-Plugin-Boilerplate: v2.6.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'public/class-flickr-importer.php' );


add_action( 'plugins_loaded', array( 'FlickrImporter', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

if ( is_admin() ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/includes/phpflickr/phpflickr.php' );
    require_once( plugin_dir_path( __FILE__ ) . 'admin/class-flickr-importer-admin.php' );
	add_action( 'plugins_loaded', array( 'FlickrImporterAdmin', 'get_instance' ) );

}
