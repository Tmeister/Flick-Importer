<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   FlickrImporter
 * @author    Enrique Chavez <noone@tmeister.net>
 * @license   GPL-2.0+
 * @link      http://example.com
 * @copyright 2014
 */

/*
27d7c205fbcdb50992d27d1bf1b4d139
a5c4465d61d31ab0
22573102@N06
*/

	$galleries = $this->get_galleries();
    $imported = get_terms( array('photoset') );

?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<form method="post" action="options.php">
        <?php settings_fields( 'flickr-settings-group' ); ?>
        <?php do_settings_sections( 'flickr-settings-group' ); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php echo __('Flickr API Key', $this->plugin_slug); ?></th>
                <td><input type="text" name="flickr_importer_key_app" value="<?php echo get_option('flickr_importer_key_app'); ?>" /></td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php echo __('Flickr User ID', $this->plugin_slug); ?></th>
                <td><input type="text" name="flickr_importer_userid" value="<?php echo get_option('flickr_importer_userid'); ?>" /></td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php echo __('Flickr Secret Key', $this->plugin_slug); ?></th>
                <td><input type="text" name="flickr_importer_secret_key" value="<?php echo get_option('flickr_importer_secret_key'); ?>" /></td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>

    <?php if (!get_option('flickr_importer_key_app') || !get_option('flickr_importer_userid')): ?>
        <div class="manage-menus">
            <?php echo __('Please enter your Flickr API Keys.', $this->plugin_slug) ?>
            <a href="https://www.flickr.com/services/apps/create/" target="_blank"><?php echo __('Flickr API', $this->plugin_slug); ?></a>
        </div>
    <?php return; endif; ?>

    <hr/>

    <h2><?php echo $galleries['total'] . " " .__('Available Galleries', $this->plugin_slug); ?></h2>

    <hr>


	<div class="status-holder manage-menus">
		<span class="label">Importing</span> <span class="count"></span> photos. <span class="realcount"></span> <span class="feedback"></span>
	</div>

	<br>


    <table class="wp-list-table widefat plugins">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-name" id="name"><?php echo __('Gallery', $this->plugin_slug); ?></th>
                <th scope="col" class="manage-column column-description" id="description"><?php echo __('Description', $this->plugin_slug); ?></th>
                <th scope="col" class="manage-column" id="photos"><?php echo __('Photos', $this->plugin_slug); ?></th>
                <th scope="col" class="manage-column" id="status"><?php echo __('Status', $this->plugin_slug); ?></th>
                <th scope="col" class="manage-column" id='actions'><?php echo __('Actions', $this->plugin_slug); ?></th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th scope="col" class="manage-column column-name" id="name"><?php echo __('Gallery', $this->plugin_slug); ?></th>
                <th scope="col" class="manage-column column-description" id="description"><?php echo __('Description', $this->plugin_slug); ?></th>
                <th scope="col" class="manage-column" id="photos"><?php echo __('Photos', $this->plugin_slug); ?></th>
                <th scope="col" class="manage-column" id="status"><?php echo __('Status', $this->plugin_slug); ?></th>
                <th scope="col" class="manage-column" id='actions'><?php echo __('Actions', $this->plugin_slug); ?></th>
            </tr>
        </tfood>
        <tbody id="the-list">
        	<?php
                foreach ($galleries['photoset'] as $gallery):
                    $exist = false;
                    foreach( $imported as $term ){
                        $localfid = get_option("taxonomy_" . $term->term_id);
                        if( $localfid['custom_term_meta_fid'] == $gallery['id']){
                            $exist = true;
                            $term = $term->term_id;
                            break;
                        }
                    }
            ?>
        		<tr class="<?php echo $exist ? 'active' : 'inactive'; ?>">
	        		<th class="plugin-title"><?php echo $gallery['title'] ?></th>
	        		<th class="column-descriptions desc"><?php echo $gallery['description'] ?></th>
	        		<th style="text-align:center;"><?php echo $gallery['photos'] ?></th>
	        		<th style="text-align:center;">
                        <?php echo $exist ? __('Imported', $this->plugin_slug) : __('NOT imported', $this->plugin_slug); ?>
	        		</th>
	        		<th style="text-align:center;">
	        			<a href="#" class="<?php echo !$exist ? 'importer' : 'reimporter';?>"
                           data-name="<?php echo $gallery['title'] ?>"
                           data-desc="<?php echo $gallery['description']?>"
                           data-fid="<?php echo $gallery['id'] ?>"
                           <?php if( $exist ): ?>
                               data-termid="<?php echo $term; ?>"
                           <?php endif; ?>
                        >
                            <?php echo !$exist ? 'Import' : 'Re-Import';?>
                        </a>
	        		</th>
	        	</tr>
        	<?php endforeach ?>

        </tbody>
    </table>
</div>
