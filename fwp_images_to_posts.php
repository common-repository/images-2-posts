<?php
/*
Plugin Name: Fuxy's WP Images 2 Posts
Plugin URI: http://fuxy.net/images-2-posts.html
Description: Creation of posts from mass uploads images.
Author: Fuxy
Version: 0.45
Author URI: http://fuxy.net/
License: GPL2
*/

// Define current version constant
define( 'FWP_I2P_VERSION', '0.45' );

//load translated strings
function my_plugin_init()
{
	load_plugin_textdomain('fwp-itp', false, dirname(plugin_basename( __FILE__ )).'/languages');
}
add_action('init','my_plugin_init');

// create custom plugin settings menu
add_action('admin_menu', 'fwp_itp_plugin_menu');

function fwp_itp_plugin_menu()
{
	//Create Menu Page
	$mypage = add_menu_page(  __( 'Images 2 Posts', 'fwp-itp' ), __( 'Images 2 Posts', 'fwp-itp' ), 'edit_theme_options', 'fwp_itp', 'fwp_itp_menage', path_join( WP_PLUGIN_URL, basename( dirname( __FILE__ ) ) ).'/icon.png' );
    add_action( "admin_print_scripts-$mypage", 'fwp_itp_loadScripts' );
    add_action( "admin_print_styles-$mypage", 'fwp_itp_loadStyles' );
}

function fwp_itp_menage()
{
?>
	<div class="wrap">
		<?php screen_icon( 'plugins' ); ?>
		<h2><?php _e('Images 2 Posts', 'fwp-itp'); ?></h2>
		<br/>
		<h3><?php _e('Post/Page type', 'fwp-itp'); ?></h3>
		<form id="fwp_itp_form" name="fwp_itp_form" actions="">
			<?php
				$args = array(
					'public'   => true,
					'_builtin' => false
				);
				$exclude = array( 'attachment', 'revision', 'nav_menu_item', 'wpcf7_contact_form' );
				$post_types = get_post_types( $args, 'objects', 'or' );
				if ( $post_types )
					echo '<select id="select_cats" name="select_cats" >'."\n\r";
				foreach ( $post_types as $idx => $post_type )
				{
					if ( !in_array( $idx, $exclude ) )
						echo '<option value='. $idx .'>' . $post_type->labels->name . ' - (' . $idx . ')</option>'."\n\r";
				}
				if ( $post_types )
					echo '</select>'."\n\r";
            ?>
            <p style="position:relative;top:-50px;text-align:center;width:200px;font-size:1.5em;float:right"><?php _e('Buy me a Beer', 'fwp-itp'); ?><br/><br/>
            <a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&amp;business=milen@fuxy.net&amp;item_name=Donation+for+Wordpress+Plugin:+Images+2+Posts"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" alt="PayPal - The safer, easier way to pay online!"></a>
            </p>
			<div class="hr"></div>
			<h3><?php _e( 'Categories', 'fwp-itp' ); ?></h3>
			<select id="fwp_itp_cats" class="chzn-select" multiple="multiple" data-placeholder="<?php _e( 'Choose a categories', 'fwp-itp' ); ?>" style="width:350px;" name="fwp_itp_cats">
			<?php 
			$args = array(
				'orderby'			=> 'name',
				'hide_empty'	=> 0,
				'taxonomy'		=> 'category',
			);
			$categories = get_categories( $args );
			foreach ( $categories as $idx => $category ) :
			?>
			<option value="<?php echo $category->term_id; ?>" /><?php echo __( $category->name ); ?></option>
			<?php endforeach; ?>
			</select>
			<h3><?php _e( 'Taxonomies', 'fwp-itp' ); ?></h3>
			<select id="fwp_itp_terms" class="chzn-select" multiple="multiple" data-placeholder="<?php _e( 'Choose a terms', 'fwp-itp' ); ?>" style="width:350px;" name="fwp_itp_terms">
			<?php 
				$args=array(
					'public'   => true,
					'_builtin' => false
				);
				$output = 'objects'; // or objects
				$operator = 'and'; // 'and' or 'or'
				$taxonomies = get_taxonomies( $args, $output, $operator ); 
				if  ($taxonomies) {
					foreach ( $taxonomies as $taxonomy )
					{
						echo '<optgroup label="'. $taxonomy->label. '">';
				
						$terms = get_terms( $taxonomy->name, array(
							'orderby'    => 'count',
							'hide_empty' => 0
						 ) );
									
						foreach ( $terms as $idx => $term ) : ?>
							<option value="<?php echo $taxonomy->name.'|'.$term->term_id; ?>" /><?php echo __( $term->name ); ?></option>
						<?php endforeach;
					}
				}
			?>
			</select>
			<div class="hr"></div>
			<h3><?php _e( 'Post status', 'fwp-itp' ); ?></h3>
			<select id="fwp_itp_post_status" name="fwp_itp_post_status">
				<option value="draft"><?php _e( 'Draft', 'fwp-itp' ); ?></option>
				<option value="publish"><?php _e( 'Published', 'fwp-itp' ); ?></option>
			</select>
			<div class="hr"></div>
			<?php 
				setlocale(LC_ALL, get_locale().'.utf-8');
				$dateTime = new DateTime("now", new DateTimeZone(timezone_name_from_abbr("",3600 * get_option('gmt_offset'),0)));
			?>
			
<!-- Schedule & Step Start -->
			<p><input type="checkbox" id="itp_add_schedule_button" name="itp_add_schedule_button" value="yes" /><label for="itp_add_schedule_button"> <?php echo __( 'Schedule &amp; Step', 'fwp-itp' ); ?></label></p>
			<fieldset id="itp_add_schedule">
				<p>
				<select id="itp_month" name="itp_month">
					<?php 
						for( $i = 1; $i <= 12; $i++ ) {
							$current = ( $dateTime->format('n') == $i ) ? ' selected="selected"' : '';
							echo '<option value="'.$i.'"'.$current.'>'.strftime( '%b', mktime( 0, 0, 0, $i, 1 ) ).'</option>';
						}
					?>
				</select>
				<input type="text" id="itp_day" name="itp_day" value="<?php echo $dateTime->format('j'); ?>" size="2" maxlength="2" autocomplete="off">, <input type="text" id="itp_year" name="itp_year" value="<?php echo $dateTime->format('Y'); ?>" size="4" maxlength="4" autocomplete="off"> @ <input type="text" id="itp_hour" name="itp_hour" value="<?php echo $dateTime->format('H'); ?>" size="2" maxlength="2" autocomplete="off"> : <input type="text" id="itp_minute" name="itp_minute" value="<?php echo $dateTime->format('i'); ?>" size="2" maxlength="2" autocomplete="off">
				</p>
				<p>
					<?php _e( 'Step', 'fwp-itp' ); ?>
					<input type="text" id="itp_step" name="itp_step" size="2" maxlength="2" value="0">
					<select id="itp_repeat" name="itp_repeat">
						<option value="1"><?php _e( 'Minutes', 'fwp-itp' ); ?></option>
						<option value="2"><?php _e( 'Hours', 'fwp-itp' ); ?></option>
						<option value="3"><?php _e( 'Days', 'fwp-itp' ); ?></option>
					</select>
				</p>
				<div class="hr"></div>
			</fieldset>
<!-- Schedule & Step End -->

<!-- Insert Image to content Start -->
			<p><input type="checkbox" id="itp_add_image_button" name="itp_add_image_button" value="yes" /><label for="itp_add_image_button"> <?php echo __( 'Insert Image to content', 'fwp-itp' ); ?></label></p>
			<fieldset id="itp_add_image">
				<?php
					global $wpdb;
					$wp_size_names = $wpdb->get_results( "SELECT option_name FROM `wp_starter_options` WHERE `option_name` LIKE '%_size_w'" );
					$sizes = array();
					foreach ( $wp_size_names as $size_name )
					{
						if ( $size_name->option_name != 'embed_size_w' )
							$sizes[] = str_replace( '_size_w', '', $size_name->option_name );
					}
		
					$wp_size_names = $sizes;
					$sizes_wp = array();
					foreach ( $wp_size_names as $size_name ) {
							$sizes_wp[$size_name] = array(
											'size_w'    => get_option("{$size_name}_size_w"),
											'size_h'    => get_option("{$size_name}_size_h"),
											'crop'      => get_option("{$size_name}_crop")
									);
					}
				?>
				<h3><?php _e( 'Image Size', 'fwp-itp' ); ?></h3>
				<select id="itp_image_size" name="itp_image_size">
					<option value="full"><?php _e( 'Select Image Size', 'fwp-itp' ); ?></option>
					<option value="full"><?php _e( 'full', 'fwp-itp' ); ?></option>
					<?php foreach ( $sizes_wp as $idx => $size ) : ?>
					<option value="<?php echo $idx; ?>"><?php echo $idx . ': ' . $size['size_w'] . ' x ' . $size['size_h']; ?></option>
					<?php endforeach; ?>
				</select>
				<h3><?php _e( 'Link URL', 'fwp-itp' ); ?></h3>
				<input type="text" class="text urlfield regular-text" id="itp_url" name="attachments" value=""><br>
				<br/>
				<input type="radio" name="url" checked="checked" id="urlnone" value="3"><label for="urlnone" class="url"><?php _e( 'None', 'fwp-itp' ); ?></label>
				<input type="radio" name="url" id="urlfile" value="0"><label for="urlfile" class="url"><?php _e( 'File URL', 'fwp-itp' ); ?></label>
				<input type="radio" name="url" id="urlpost" value="1"><label for="urlpost" class="url"><?php _e( 'Post URL', 'fwp-itp' ); ?></label>
				<input type="radio" name="url" id="urlcustom" value="2"><label for="urlcustom" class="url"><?php _e( 'Custom URL', 'fwp-itp' ); ?></label></td>
				<br/><br/>
				<input type="radio" name="align" checked="checked" id="image-align-none" value="alignnone"><label for="image-align-none" class="align image-align-none-label"><?php _e( 'None', 'fwp-itp' ); ?></label>
				<input type="radio" name="align" id="image-align-left" value="alignleft"><label for="image-align-left" class="align image-align-left-label"><?php _e( 'Left', 'fwp-itp' ); ?></label>
				<input type="radio" name="align" id="image-align-center" value="aligncenter"><label for="image-align-center" class="align image-align-center-label"><?php _e( 'Center', 'fwp-itp' ); ?></label>
				<input type="radio" name="align" id="image-align-right" value="alignright"><label for="image-align-right" class="align image-align-right-label"><?php _e( 'Right', 'fwp-itp' ); ?></label></td>
				<div class="hr"></div>
			</fieldset>
<!-- Insert Image to content End -->
			
			<p><input type="checkbox" checked="checked" id="itp_add_exif" name="itp_add_exif" /><label for="itp_add_exif"> <?php echo __( 'Add EXIF data', 'fwp-itp' ); ?></label></p>
			<p><input type="checkbox" checked="checked" id="itp_add_featured" name="itp_add_featured" /><label for="itp_add_featured"> <?php echo __( 'Use as Futured Image', 'fwp-itp' ); ?></label></p>
			<div class="hr"></div>
		</form>

		<style type="text/css" media="screen">
			.uploadifyQueueItem { background-color: #f5f5f5; border: 2px solid #e5e5e5; font: 11px Verdana, Geneva, sans-serif; margin-top: 5px; padding: 10px; width: 350px; }
			.uploadifyError { background-color: #fde5dd !important; border: 2px solid #fbcbbc !important; }
			.uploadifyQueueItem .cancel { float: right; }
			.uploadifyQueue .completed { background-color: #e5e5e5; }
			.uploadifyProgress { background-color: #e5e5e5; margin-top: 10px; width: 100%; }
			.uploadifyProgressBar { background-color: #0099ff; height: 3px; width: 1px; }
			#status-message { margin: 10px 0; font-style: italic; color: gray; }
			#file_uploadUploader { margin-top: 10px; }
			#itp_add_image, #itp_add_schedule { display: none; }
			.align { background: url( "images/align-none.png" ) no-repeat scroll left top; padding-left: 25px; margin: 5px 15px 0 0; }
			.url { padding-left: 5px; margin: 5px 15px 0 0; }
			.image-align-none-label { background-image: url( "images/align-none.png" ); }
			.image-align-left-label { background-image: url( "images/align-left.png" ); }
			.image-align-center-label { background-image: url( "images/align-center.png" ); }
			.image-align-right-label { background-image: url( "images/align-right.png" ); }
			.hr { height: 5px; display: block; overflow: hidden; margin: 15px 0; border: 0; background-color: #e5e5e5; }
		</style>
		
		<input id="file_upload" name="file_upload" type="file" />
		<div id="status-message"><?php _e( 'Select files to upload', 'fwp-itp' ); ?>:</div>
		<div id="custom-queue" class="uploadifyQueue"></div>
		<br/>
		
		<script type="text/javascript">
			jQuery(document).ready(function()
			{
				jQuery('#file_upload').uploadify(
				{
					'uploader'				: '<?php echo path_join( WP_PLUGIN_URL, basename( dirname( __FILE__ ) ) ); ?>/uploadify/uploadify.swf',
					'script'					: '<?php echo path_join( WP_PLUGIN_URL, basename( dirname( __FILE__ ) ) ); ?>/uploadify/uploadify_wp.php',
					'cancelImg'				: '<?php echo path_join( WP_PLUGIN_URL, basename( dirname( __FILE__ ) ) ); ?>/uploadify/cancel.png',
					'folder'					: '<?php echo path_join( WP_PLUGIN_URL, basename( dirname( __FILE__ ) ) ); ?>/uploads',
					'multi'          	: true,
					'fileExt'        	: '*.jpg;*.gif;*.png;*.pdf',
					'fileDesc'       	: 'Image Files (.JPG, .GIF, .PNG, .PDF)',
					'queueID'        	: 'custom-queue',
					'removeCompleted'	: false,
					'buttonText'			: '<?php echo __( 'Select Images', 'fwp-itp' ); ?>',
					'onSelectOnce'   	: function( event, data ) { jQuery('#status-message').text(data.filesSelected + ' <?php _e( 'files have been added to the queue.', 'fwp-itp' ); ?>'); },
					'onAllComplete'  	: function( event, data ) { jQuery('#status-message').text(data.filesUploaded + ' <?php _e( 'files uploaded', 'fwp-itp' ); ?>, ' + data.errors + ' <?php _e( 'errors.', 'fwp-itp' ); ?>'); },
					'onOpen'					: serializeData,
					'onAllComplete'		: function( event, data ) { count = 0; }
			  });
			
				jQuery(".chzn-select").chosen();

				jQuery("#itp_add_schedule_button").change(function()
				{
					jQuery( '#itp_add_schedule' ).toggle();
				});

				jQuery( '#itp_add_image_button' ).click(function()
				{
					jQuery( '#itp_add_image' ).toggle();
				});
			});
			
			var myDateStr = null;
			var myDate;
			var count = 0;
			var b = false;
			function uploadFiles()
            {
				//myDate = new Date( jQuery('#itp_year').val(), jQuery('#itp_month').val(), jQuery('#itp_day').val(), jQuery('#itp_hour').val(), jQuery('#itp_minute').val() );
                myDate = new Date();
                myDate.setFullYear( parseInt( jQuery('#itp_year').val() ) );
                myDate.setMonth( parseInt( jQuery('#itp_month').val() )-1 );
                myDate.setDate( parseInt( jQuery('#itp_day').val() ) );
                myDate.setHours( parseInt( jQuery('#itp_hour').val() ) );
                myDate.setMinutes( parseInt( jQuery('#itp_minute').val() ) );
				
				serializeData();
				b = true;

				jQuery('#file_upload').uploadifyUpload();
			}
			
			function serializeData()
			{
				var cats;
				if ( jQuery('select#fwp_itp_cats').val() )
				{
					cats = jQuery('select#fwp_itp_cats').val().toString().replace( ',', '|' );
				}
				
				var terms;
				if ( jQuery('select#fwp_itp_terms').val() )
				{
					terms = jQuery('select#fwp_itp_terms').val().toString().replace( ',', '||' );
				}
				
				if ( jQuery('#itp_add_schedule_button').is(':checked') )
				{
					if ( b && parseInt( jQuery('#itp_step').val() ) > 0 )
					{
						switch( parseInt( jQuery('#itp_repeat').val() ) )
						{
							case 1:
								myDate.setMinutes( myDate.getMinutes() + parseInt( jQuery('#itp_step').val() ) );
								break;
							case 2:
								myDate.setHours( myDate.getHours() + parseInt( jQuery('#itp_step').val() ) );
								break;
							case 3:
								myDate.setDate( myDate.getDate() + parseInt( jQuery('#itp_step').val() ) );
								break;
						}
					}
                    myDateStr = myDate.getFullYear().toString()+'-'+(myDate.getMonth()+1).toString()+'-'+pad(myDate.getDate())+' '+pad(myDate.getHours())+':'+pad(myDate.getMinutes())+':00';
                    //myDateStr = myDate.getFullYear().toString()+'-'+myDate.getMonth().toString()+'-'+pad(myDate.getDate())+' '+pad(myDate.getHours())+':'+pad(myDate.getMinutes())+':00';
                    console.log(myDateStr);
				}
				else
				{
					myDateStr = '';
				}
				jQuery('#file_upload').uploadifySettings('scriptData',
				{
					'itp_post_type'			: jQuery('#select_cats').val(), 
					'itp_cats'					: cats, 
					'itp_terms'					: terms, 
					'itp_status'				: jQuery('#fwp_itp_post_status').val(), 
					'itp_add_exif'			: jQuery('#itp_add_exif').is(':checked') ? 'yes' : 'no', 
					'fwp_itp_field'			: jQuery('#fwp_itp_field').val(),
					'itp_add_featured'	: jQuery('#itp_add_featured').is(':checked') ? 'yes' : 'no',
					'itp_schedule_date'	: myDateStr,
					'itp_add_image'			: jQuery('#itp_add_image_button').is(':checked') ? 'yes' : 'no',
					'itp_image_size'		: jQuery('#itp_add_image_button').is(':checked') ? jQuery('#itp_image_size').val() : '',
					'itp_url'						: jQuery('#itp_add_image_button').is(':checked') ? jQuery('#itp_url').val() : '',
					'itp_url_type'			: jQuery('#itp_add_image_button').is(':checked') ? jQuery('input[name=url]:checked').val() : '',
					'itp_url_align'			: jQuery('#itp_add_image_button').is(':checked') ? jQuery('input[name=align]:checked').val() : ''
				});
			}
			
			var pad = function(val) { var str = val.toString(); return (str.length < 2) ? "0" + str : str};
			
		</script>
		<a class="button-secondary" href="javascript:uploadFiles();"><?php _e( 'Upload Files', 'fwp-itp' ); ?></a>
		<a class="button-secondary" href="javascript:jQuery('#file_upload').uploadifyClearQueue();"><?php _e( 'Cancel', 'fwp-itp' ); ?></a>
		<br class="clear" />
	</div>
<?php
}

function fwp_itp_loadScripts()
{
    wp_enqueue_script('jquery');
    
    wp_register_script('itp_uploadify', path_join( WP_PLUGIN_URL, basename( dirname( __FILE__ ) ) ) . '/uploadify/jquery.uploadify.v2.1.4.min.js', array('jquery'), '2.1.0' );
    wp_enqueue_script( 'itp_uploadify' );
    
    wp_register_script('itp_chosen', path_join( WP_PLUGIN_URL, basename( dirname( __FILE__ ) ) ) . '/chosen/chosen.jquery.min.js', array('jquery'), '0.9.1' );
    wp_enqueue_script( 'itp_chosen' );
    
    wp_enqueue_script('swfobject');
}

function fwp_itp_loadStyles()
{
	wp_register_style('itp_chosen_styles', path_join( WP_PLUGIN_URL, basename( dirname( __FILE__ ) ) ) . '/chosen/chosen.css' );
	wp_enqueue_style( 'itp_chosen_styles' );
}