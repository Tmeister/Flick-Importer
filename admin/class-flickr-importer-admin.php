<?php
/**
 * Flickr Importer
 *
 * @package   FlickrImporterAdmin
 * @author    Enrique Chavez <noone@tmeister.net>
 * @license   GPL-2.0+
 * @link      http://example.com
 * @copyright 2014
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `class-flickr-importer.php`
 *
 * @package FlickrImporterAdmin
 * @author  Enrique Chavez <noone@tmeister.net>
 */
class FlickrImporterAdmin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

    /**
     * Instance of PHPFlickr Library.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected $phpflickr = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		$plugin = FlickrImporter::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		if( get_option('flickr_importer_key_app') && get_option('flickr_importer_secret_key') ) {
            $this->key = get_option('flickr_importer_key_app');
            $this->secret = get_option('flickr_importer_secret_key');
            $this->nsid = get_option('flickr_importer_userid');
            $this->phpflickr = new phpflickr($this->key, $this->secret);
        }

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		// Actions
        add_action( 'admin_init', array($this, 'register_settings') );
        add_action( 'init', array($this, 'add_custom_post_type'));

        add_action( 'photoset_add_form_fields', array($this, 'photo_add_new_meta_field'), 10, 2 );
        add_action( 'photoset_edit_form_fields', array($this, 'photo_edit_new_meta_field'), 10, 2 );
        add_action( 'edited_photoset', array( $this, 'save_photos_taxonomy_meta' ));
		add_action( 'create_photoset', array( $this, 'save_photos_taxonomy_meta'));

		add_action( 'wp_ajax_import_gallery', array($this, 'import_gallery') );
		add_action( 'wp_ajax_import_photo', array($this, 'import_photo') );
        add_action( 'wp_ajax_reimport_gallery', array($this, 'reimport_gallery') );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), FlickrImporter::VERSION );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), FlickrImporter::VERSION );
			wp_localize_script( $this->plugin_slug . '-admin-script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		}

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		$this->plugin_screen_hook_suffix = add_management_page(
			__( 'Flickr Importer', $this->plugin_slug ),
			__( 'Flickr Importer', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'tools.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}

    /**
     * Add settings plugin.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        register_setting( 'flickr-settings-group', 'flickr_importer_key_app' );
    	register_setting( 'flickr-settings-group', 'flickr_importer_secret_key' );
    	register_setting( 'flickr-settings-group', 'flickr_importer_userid' );
    }

    /**
     * Get Galleries in Flickr.
     *
     * @since    1.0.0
     */

    public function get_galleries(){
		$key = 'xshot_galleries';
		if( !$galleries = get_transient( $key  )){
            $galleries = $this->phpflickr->photosets_getList($this->nsid);
            set_transient( $key, $galleries, 60 * 60 * 24 );
        }
        return $galleries;
	}

	public function get_single_gallery($id, $force = false){
		$key = 'xshot_single_gallery_'.$id;

        if( $force ){
            delete_transient($key);
        }

		if( !$gallery = get_transient( $key )){
			$gallery = $this->phpflickr->photosets_getPhotos( $id );
            set_transient( $key, $gallery, 60 * 60 * 24 );
		}

        return $gallery;
	}

	public function import_gallery(){
		$fid = $_POST['fid'];
		$title = $_POST['title'];
		$description = $_POST['description'];

		if( !$fid && !$title && !$description){
			echo json_encode(array('status' => 'fail', 'message' => 'Missing Fields'));
			die();
		}

		$set = $this->get_single_gallery( $fid );

		$photoset = wp_insert_term( $title, 'photoset', array('description' => $description) );

		if( is_wp_error( $photoset ) ) {
		    echo json_encode(array('status' => 'fail', 'message' => $photoset->get_error_message()));
		    die();
		}

        update_option( "taxonomy_" . $photoset['term_id'], array('custom_term_meta_fid' => $fid ) );

		echo json_encode(array('status' => 'ok', 'data' => $set, 'photosetID' => $photoset));
		die();
	}

    function reimport_gallery(){
        $fid = $_POST['fid'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $termid = $_POST['termid'];

        $posts = get_posts(array(
            'post_type' => 'flphoto',
            'posts_per_page' => 200,
            'tax_query' => array(
                array(
                    'taxonomy' => 'photoset',
                    'terms' => array($termid),
                )
            )
        ));

        foreach( $posts as $post ){
            wp_delete_post( $post->ID, true);
        }

        wp_delete_term( $termid, 'photoset');
        delete_option( "taxonomy_" . $termid );

        $set = $this->get_single_gallery( $fid );

        $photoset = wp_insert_term( $title, 'photoset', array('description' => $description) );

        if( is_wp_error( $photoset ) ) {
            echo json_encode(array('status' => 'fail', 'message' => $photoset->get_error_message()));
            die();
        }

        update_option( "taxonomy_" . $photoset['term_id'], array('custom_term_meta_fid' => $fid ) );

        echo json_encode(array('status' => 'ok', 'data' => $set, 'photosetID' => $photoset));
        die();
    }

	function import_photo(){
		$id = $_POST['photoId'];
		$title = $_POST['photoTitle'];
		$photosetID = $_POST['photosetID'];

		if( !$id || !$title || !$photosetID){
			echo json_encode(array('status' => 'fail', 'message' => 'Missing Fields'));
			die();
		}

		$photos = $this->phpflickr->photos_getSizes( $id );
		$photo = $this->get_photo_info($id);
		$medium = $this->get_photo_path( $photos, 'Medium 640' );
		$original = $this->get_photo_path( $photos, 'Large' );

		$photoPost = array(
			'post_name' => $photo['photo']['title'],
			'post_title' => $photo['photo']['title'],
			'post_type' => 'flphoto',
			'post_content' => '',
			'post_excerpt' => '',
			'post_status' => 'publish'

		);

		$postID = wp_insert_post($photoPost, true );

		if( is_wp_error( $postID ) ) {
		    echo json_encode(array('status' => 'fail', 'message' => $postID->get_error_message()));
		    die();
		}

        add_post_meta( $postID, 'medium_photo', $medium );
        add_post_meta( $postID, 'original_photo', $original );


		$photoset_insert = wp_set_object_terms( $postID, array( (int) $photosetID['term_id'] ), 'photoset');

		if( is_wp_error( $photoset_insert ) ) {
		    echo json_encode(array('status' => 'fail', 'message' => $photoset_insert->get_error_message()));
		    die();
		}

		echo json_encode(array('status' => 'ok', 'pid' => $id));
		die();
	}

	function get_photo_info($photoid){
		$photoinfo = $this->phpflickr->photos_getInfo( $photoid );
		return $photoinfo;
	}

	function get_photo_path($photos, $size){
		$original = null;
		foreach ($photos as $photo) {
			if( $photo['label'] == $size ){
				return $photo['source'];
			}
			if( $photo['label'] == 'Original' ){
				$original = $photo['source'];
			}
		}

		/*
		* Do no found the Size sending the original
		*/
		return $original;
	}

	/**
     * Create The Custom Post Type
     *
     * @since    1.0.0
     */

	public function add_custom_post_type(){
		$labels = array(
			'name'               => _x( 'Photos', 'post type general name', $this->plugin_slug ),
			'singular_name'      => _x( 'Photo', 'post type singular name', $this->plugin_slug ),
			'menu_name'          => _x( 'Photos', 'admin menu', $this->plugin_slug ),
			'name_admin_bar'     => _x( 'Photo', 'add new on admin bar', $this->plugin_slug ),
			'add_new'            => _x( 'Add New', 'photo', $this->plugin_slug ),
			'add_new_item'       => __( 'Add New Photo', $this->plugin_slug ),
			'new_item'           => __( 'New Photo', $this->plugin_slug ),
			'edit_item'          => __( 'Edit Photo', $this->plugin_slug ),
			'view_item'          => __( 'View Photo', $this->plugin_slug ),
			'all_items'          => __( 'All Photos', $this->plugin_slug ),
			'search_items'       => __( 'Search Photos', $this->plugin_slug ),
			'parent_item_colon'  => __( 'Parent Photos:', $this->plugin_slug ),
			'not_found'          => __( 'No photos found.', $this->plugin_slug ),
			'not_found_in_trash' => __( 'No photos found in Trash.', $this->plugin_slug ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'photo' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'custom-fields' )
		);

		register_post_type( 'flphoto', $args );

		// Add new taxonomy, NOT hierarchical (like tags)
		$labels = array(
			'name'                       => _x( 'PhotoSets', 'taxonomy general name' ),
			'singular_name'              => _x( 'PhotoSet', 'taxonomy singular name' ),
			'search_items'               => __( 'Search PhotoSets' ),
			'popular_items'              => __( 'Popular PhotoSets' ),
			'all_items'                  => __( 'All PhotoSets' ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => __( 'Edit PhotoSet' ),
			'update_item'                => __( 'Update PhotoSet' ),
			'add_new_item'               => __( 'Add New PhotoSet' ),
			'new_item_name'              => __( 'New PhotoSet Name' ),
			'separate_items_with_commas' => __( 'Separate writers with commas' ),
			'add_or_remove_items'        => __( 'Add or remove writers' ),
			'choose_from_most_used'      => __( 'Choose from the most used writers' ),
			'not_found'                  => __( 'No writers found.' ),
			'menu_name'                  => __( 'PhotoSets' ),
		);

		$args = array(
			'hierarchical'          => false,
			'labels'                => $labels,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'photoset' ),
		);

		register_taxonomy( 'photoset', 'flphoto', $args );
	}

	public function photo_add_new_meta_field() {
	?>
		<div class="form-field">
			<label for="term_meta[custom_term_meta_fid]"><?php _e( 'Flickr ID', $this->plugin_slug ); ?></label>
			<input type="text" name="term_meta[custom_term_meta_fid]" id="term_meta[custom_term_meta_fid]" value="">
			<p class="description"><?php _e( 'This is the PhotoSet Flickr ID',$this->plugin_slug ); ?></p>
		</div>
	<?php
	}

	public function photo_edit_new_meta_field($term) {
		$t_id = $term->term_id;
		$term_meta = get_option( "taxonomy_$t_id" );
	?>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="term_meta[custom_term_meta_fid]"><?php _e( 'Flickr ID', $this->plugin_slug ); ?></label></th>
			<td>
				<input type="text" name="term_meta[custom_term_meta_fid]" id="term_meta[custom_term_meta_fid]" value="<?php echo esc_attr( $term_meta['custom_term_meta_fid'] ) ? esc_attr( $term_meta['custom_term_meta_fid'] ) : ''; ?>">
				<p class="description"><?php _e( 'This is the Flickr ID',$this->plugin_slug ); ?></p>
			</td>
		</tr>
	<?php
	}

	public function save_photos_taxonomy_meta($term_id) {
		if ( isset( $_POST['term_meta'] ) ) {
			$t_id = $term_id;
			$term_meta = get_option( "taxonomy_$t_id" );
			$cat_keys = array_keys( $_POST['term_meta'] );
			foreach ( $cat_keys as $key ) {
				if ( isset ( $_POST['term_meta'][$key] ) ) {
					$term_meta[$key] = $_POST['term_meta'][$key];
				}
			}
			update_option( "taxonomy_$t_id", $term_meta );
		}
	}

}
