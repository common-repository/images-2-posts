<?php

require_once( '../../../../wp-load.php' );
require_once( '../../../../wp-admin/includes/image.php' );

if ( !empty( $_FILES ) )
{
	$tempFile = $_FILES[ 'Filedata' ][ 'tmp_name' ];
	$targetPath = $_SERVER[ 'DOCUMENT_ROOT' ] . $_REQUEST[ 'folder' ] . '/';
	$targetFile =  str_replace( '//', '/', $targetPath ) . $_FILES[ 'Filedata' ][ 'name' ];
	
	$upload = wp_upload_bits( $_FILES[ 'Filedata' ][ 'name' ], null, file_get_contents( $tempFile ));
	
	$mime = wp_check_filetype( $upload[ 'file' ] );
	if ($mime)
		$type = $mime[ 'type' ];
	
	// Create post object
  $path_parts = pathinfo($_FILES[ 'Filedata' ][ 'name' ]);

    if ( isset($_POST['itp_cats']) )
        $cats = explode( '|', $_POST['itp_cats'] );
    else
        $cats = array();

  $exif_data = exif_read_data ( $upload[ 'file' ] );

    if ( isset($exif_data['Make']) )
        $emake = $exif_data['Make'];
    if ( isset($exif_data['Model']) )
        $emodel = $exif_data['Model'];
    if ( isset($exif_data['ExposureTime']) )
        $eexposuretime = $exif_data['ExposureTime'];
    if ( isset($exif_data['FNumber']) )
        $efnumber = $exif_data['FNumber'];
    if ( isset($exif_data['ISOSpeedRatings']) )
        $eiso = $exif_data['ISOSpeedRatings'];
    if ( isset($exif_data['DateTime']) )
        $edate = $exif_data['DateTime'];
	
	$now = new DateTime("now");
	$now = $now->setTimezone( new DateTimezone( get_option('timezone_string') ) );
	// $now->format('Y-m-d H:i:s');
	
	$my_post = array(
		'post_title' => wp_strip_all_tags( $path_parts['filename'] ),
		'post_name' => sanitize_title( $path_parts['filename'] ),
		'post_content' => "",
		'post_status' => $_POST['itp_status'],
		'post_type' => $_POST['itp_post_type'],
		'post_date' => $now->format('Y-m-d H:i:s')
	);

	if ( isset($_POST['itp_schedule_date']) && $_POST['itp_schedule_date'] != '' )
	{
		$now = new DateTime( $_POST['itp_schedule_date'] );
		
		$my_post['post_date'] = $now->format('Y-m-d H:i:s');
		$my_post['post_status'] = 'publish';
	}

	if ( $cats )
		$my_post['post_category'] = $cats;

	// Insert the post into the database
	$post_ID = wp_insert_post( $my_post );

	if ( isset( $_POST['itp_terms'] ) )
	{
		$terms = explode( '||', $_POST['itp_terms'] );
		foreach ($terms as $term)
		{
			$tmp = explode( '|', $term );
			$txnm = $tmp[0];
			$trm[] = (int)$tmp[1];
		}
		wp_set_object_terms( $post_ID, $trm, $txnm );
	}

	$attachment = array(
		'post_title' => wp_strip_all_tags( $path_parts['filename'] ),
		'post_content' => '',
		'post_type' => 'attachment',
		'post_status' => 'publish',
		'post_parent' => $post_ID,
		'post_mime_type' => $type,
		'guid' => $upload[ 'url' ]
	);

	$att_ID = wp_insert_attachment( $attachment, $upload[ 'file' ], $post_ID );
	
	wp_update_attachment_metadata( $att_ID, wp_generate_attachment_metadata( $att_ID, $upload[ 'file' ] ) );

	// Add as Featured Image
	if ( $_POST['itp_add_featured'] == 'yes' )
	{	
		update_post_meta( $post_ID, '_thumbnail_id', $att_ID );
	}

	// Add EXIF data
	if ( $_POST['itp_add_exif'] == 'yes' && isset($emodel) && $emodel != null )
    {
        if ( isset( $emake ) )
            update_post_meta( $post_ID, '_emake', $emake );
        if ( isset( $emodel ) )
            update_post_meta( $post_ID, '_emodel', $emodel );
        if ( isset( $eexposuretime ) )
            update_post_meta( $post_ID, '_eexposuretime', $eexposuretime );
        if ( isset( $efnumber ) )
            update_post_meta( $post_ID, '_efnumber', $efnumber );
        if ( isset( $eiso ) )
            update_post_meta( $post_ID, '_eiso', $eiso );
        if ( isset( $edate ) )
            update_post_meta( $post_ID, '_edate', $edate );
		update_post_meta( $post_ID, '_exif', 'yes' );
	}

	echo $upload[ 'file' ];
	
	if ( $_POST['itp_add_image'] == 'yes' )
	{
	  $my_post = array();
	  $my_post['ID'] = $post_ID;
		
		$content = '';
		
    if ( $type != 'application/pdf' )
    {
      $att = wp_get_attachment_image_src( $att_ID, $_POST['itp_image_size'] );
      $image = '<img src="'.$att[0].'" alt="" title="" class="'.$_POST['itp_url_align'].'" />';
    }

		// Link
		switch ( $_POST['itp_url_type'] )
		{
			case '0':
				if ( $type != 'application/pdf' )
				{
					$att_full = wp_get_attachment_image_src( $att_ID, 'full' );
					$content = '<a href="'.$att_full[0].'">'.$image.'</a>';
				}
				else
				{
					$att_full = wp_get_attachment_url( $att_ID );
					$content = '<a href="'.$att_full.'">'.$path_parts['filename'].'</a>';
				}
				break;
			case '1':
				if ( $type != 'application/pdf' )
					$content = '<a href="'.get_permalink( $att_ID ).'">'.$image.'</a>';
				else
					$content = '<a href="'.get_permalink( $att_ID ).'">'.$path_parts['filename'].'</a>';
				break;
			case '2':
				if ( $type != 'application/pdf' )
					$content = '<a href="'.$_POST['itp_url'].'">'.$image.'</a>';
				else
					$content = '<a href="'.$_POST['itp_url'].'">'.$path_parts['filename'].'</a>';
				break;
			case '3':
				if ( $type == 'application/pdf' )
					$image = wp_get_attachment_url( $att_ID );
				$content = $image;
				break;
		}
		
		$my_post['post_content'] = $content;
		
	  wp_update_post( $my_post );
	}
}