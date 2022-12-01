<?
/*-----------------------------------------------------------------------------
Filename: ImageUtilitiesLib.php
Author: MWM Consulting, Inc.
http://www.mwmconsulting.biz

Description: Image Utilities functions for image uploads
Returns: Array with the following cells:

   $image_info["image_file"] = Image Filename
   $image_info["tn_file"] = Thumbnail Image Filename
   $image_info["image_size"] = Image Size (Array - See "getimagesize" php function - http://ca3.php.net/manual/en/function.getimagesize.php)
   $image_info["tn_image_size"] = Thumbnail Image Size (Array - See "getimagesize" php function - http://ca3.php.net/manual/en/function.getimagesize.php)

Copyright MWM Consulting, Inc.
All Rights Reserved
-----------------------------------------------------------------------------*/

function UploadImage($image_root, 
                     $fieldname,
                     $image_max_width,
                     $image_max_height,
					 					 $image_max_file_size = -1, 
                     $file_overwrite = true,
					 					 $file_next_name = false,
                     $createThumbnail = true,
                     $thumbnail_suffix = "_tn",
                     $thumbnail_max_width = 100,
                     $thumbnail_max_height = 100,
                     $jpeg_quality = 90, 
                     $embedWatermark = false,
					 					 $watermarkImage = "")
{
   global $HTTP_POST_FILES;

   //
   // Upload the chosen image.
   //
   $upload = new Upload($HTTP_POST_FILES);

   if ($image_max_file_size == "") {
   	$upload->maxupload_size = -1;
   } else {
   	$upload->maxupload_size = $image_max_file_size;
   }

   if ($upload->save($image_root, $fieldname, $file_overwrite, $file_next_name) == false)
   {
      return $upload->errors;
   }

   $realimage_filename = $upload->final_filename;
   $extension = substr($realimage_filename, -3);
   
   // Set source filename
   $src_filename = $image_root . $realimage_filename;
	
   //
   // Calculate resized image dimensions.
   //
   $image_size = getimagesize($src_filename);
   
	 if (($image_size[0] > $image_max_width) || ($image_size[1] > $image_max_height))	{
      // Determine if the width or height is larger.
      if ($image_size[0] > $image_size[1])
      {
         // Calculate percentage of thumbnail width of original width.
         $image_width = $image_max_width;
         $scale = $image_width / $image_size[0];
    
         // Calculate the corresponding height based on the scale.
         $image_height = $image_size[1] * $scale;
         
         // Check to ensure new height is not greater than max height.
         if ($image_height > $image_max_height) {
	         // Calculate percentage of thumbnail height of original height.
	         $image_height = $image_max_height;
	         $scale = $image_height / $image_size[1];
	   
	         // Calculate the corresponding width based on the scale.
	         $image_width = $image_size[0] * $scale;         	
         }
      }
      else
      {
         // Calculate percentage of thumbnail height of original height.
         $image_height = $image_max_height;
         $scale = $image_height / $image_size[1];
   
         // Calculate the corresponding width based on the scale.
         $image_width = $image_size[0] * $scale;

         // Check to ensure new width is not greater than max width.
         if ($image_width > $image_max_width) {
	         // Calculate percentage of thumbnail width of original width.
	         $image_width = $image_max_width;
	         $scale = $image_width / $image_size[0];
	    
	         // Calculate the corresponding height based on the scale.
	         $image_height = $image_size[1] * $scale;         	
         }
      }
	} else {
        $image_width = $image_size[0];
        $image_height = $image_size[1];	
	}

	//
	// Prepare images for watermarking and resizing.
	//

	// Create image
	switch($image_size[2]) {
		case IMAGETYPE_GIF :
			$src_img = imagecreatefromgif($src_filename);
			break;
		case IMAGETYPE_JPEG :
			$src_img = imagecreatefromjpeg($src_filename);
			break;
		case IMAGETYPE_PNG :
			$src_img = imagecreatefrompng($src_filename);
			break;
		default :
			echo "Image Type Not Supported!";
	}
			
	imagealphablending($src_img, true);

	$dst_filename = $src_filename;
	$dst_img = imagecreatetruecolor($image_width, $image_height);

	// Watermark image
	if ($embedWatermark == true) {
		  // Get watermark image type
		  $watermark_image_size = getimagesize($watermarkImage);
		  
	    // Prepare watermark
			// Create watermark image
			switch($watermark_image_size[2]) {
				case IMAGETYPE_GIF :
					$src_watermark = imagecreatefromgif($watermarkImage);
					break;
				case IMAGETYPE_JPEG :
					$src_watermark = imagecreatefromjpeg($watermarkImage);
					break;
				case IMAGETYPE_PNG :
					$src_watermark = imagecreatefrompng($watermarkImage);
					break;
				default :
					// echo "Image Type Not Supported!";
			}
			
	      $watermark_width = imagesx($src_watermark);
	      $watermark_height = imagesy($src_watermark);

	      // Scale watermark
	      $watermark_new_width = $image_size[0] - 10;
	      $wm_scale = $watermark_new_width / $watermark_width;
    
          // Calculate the corresponding watermark height based on the scale.
          $watermark_new_height = $watermark_height * $wm_scale;
	      
	      // Create new watermark image
	      $dst_watermark = imagecreatetruecolor($watermark_new_width, $watermark_new_height);
          imagealphablending($dst_watermark, false);
	      
          // Resize watermark
          imagecopyresampled($dst_watermark, $src_watermark, 0, 0, 0, 0, $watermark_new_width, $watermark_new_height, $watermark_width, $watermark_height);

	      // Position watermark
	      $dest_x = $image_size[0] - $watermark_new_width - 5;
	      $dest_y = $image_size[1] - $watermark_new_height - 5;
	   
	      // Embed watermark
	      // imagecopymerge($src_img, $dst_watermark, $dest_x, $dest_y, 0, 0, $watermark_new_width, $watermark_new_height, 100);
	      imagecopy($src_img, $dst_watermark, $dest_x, $dest_y, 0, 0, $watermark_new_width, $watermark_new_height); 
	}

    // Resize image
    imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $image_width, $image_height, $image_size[0], $image_size[1]);

	// Not necessary.  Testing to get a GIF with > 128 color palette
	// imagecolormatch($src_img, $dst_img);

  //
	// Save image.
	//
	switch($image_size[2]) {
		case IMAGETYPE_GIF :
		    imagegif($dst_img, $dst_filename);
			break;
		case IMAGETYPE_JPEG :
		    imagejpeg($dst_img, $dst_filename, $jpeg_quality);
			break;
		case IMAGETYPE_PNG :
		    imagepng($dst_img, $dst_filename);
			break;
		default :
			// echo "Image Type Not Supported!";
	}

    // Get the width and height of the new image.
    $new_image_size = getimagesize($dst_filename);

    imagedestroy($src_img);
    imagedestroy($dst_img);
    imagedestroy($src_watermark);
    imagedestroy($dst_watermark);

   //
   // Create jpeg thumbnail copy of image.
   //

   if ($createThumbnail == true)
   {
    // Check to see if thumbnail already smaller than passed in pixel value
	  if ($new_image_size[0] < $thumbnail_max_width && $new_image_size[1] < $thumbnail_max_height) {
  	  $thumbnail_width = $new_image_size[0];
		  $thumbnail_height = $new_image_size[1];
	  } else {
		  // Determine if the thumbnail max width or height is larger.
		  if ($thumbnail_max_width < $thumbnail_max_height)
		  {
			 // Calculate percentage of thumbnail width of original width.
			 $thumbnail_width = $thumbnail_max_width;
			 $scale = $thumbnail_width / $new_image_size[0];
		 
			 // Calculate the corresponding height based on the scale.
			 $thumbnail_height = $new_image_size[1] * $scale;
		  }
		  else
		  {
			 // Calculate percentage of thumbnail height of original height.
			 $thumbnail_height = $thumbnail_max_height;
			 $scale = $thumbnail_height / $new_image_size[1];
		 
			 // Calculate the corresponding width based on the scale.
			 $thumbnail_width = $new_image_size[0] * $scale;
		  }
	  }
      
	  // Do the actual resizing.
      $new_basename = basename($realimage_filename, "." . $extension);
      //  $new_filename = $new_basename . $thumbnail_suffix . $extension;
      $new_filename = $new_basename . $thumbnail_suffix . ".jpg";
      $dst_filename = $image_root . $new_filename;
   
	// Create image
	switch($image_size[2]) {
		case IMAGETYPE_GIF :
			$src_img = imagecreatefromgif($src_filename);
			break;
		case IMAGETYPE_JPEG :
			$src_img = imagecreatefromjpeg($src_filename);
			break;
		case IMAGETYPE_PNG :
			$src_img = imagecreatefrompng($src_filename);
			break;
		default :
			// echo "Image Type Not Supported!";
	}
			
	  $dst_img = imagecreatetruecolor($thumbnail_width, $thumbnail_height);
   
      imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $thumbnail_width, $thumbnail_height, $new_image_size[0], $new_image_size[1]);
      imagejpeg($dst_img, $dst_filename, $jpeg_quality);
      imagedestroy($src_img);
      imagedestroy($dst_img);
   
      // Get the width and height of the thumbnail.
      $tn_image_size = getimagesize($dst_filename);
   }

   //
   // Populate image_info array so it can be returned.
   //
   $image_info["image_file"] = $realimage_filename;
   $image_info["tn_file"] = $new_filename;
   $image_info["image_size"] = $new_image_size;
   $image_info["tn_image_size"] = $tn_image_size;

   return $image_info;
}

// ----------------------------------------------------------------------

function DuplicateImage($image_root, 
                     $image_name,
                     $image_max_width,
                     $image_max_height,
                     $image_suffix,
                     $createThumbnail = true,
                     $thumbnail_suffix = "_tn",
                     $thumbnail_max_width = 100,
                     $thumbnail_max_height = 100,
                     $jpeg_quality = 90, 
                     $embedWatermark = false,
					 					 $watermarkImage = "")
{

   // Set image extension
   $extension = substr($image_name, -3);
   
   // Set image basename
   $src_basename = basename($image_name, "." . $extension);
   
   // Set source filename
   $src_filename = $image_root . $image_name;
   
   // Set destination filename
   $dst_filename = $image_root . $src_basename . $image_suffix . "." . $extension;
	
   //
   // Calculate resized image dimensions.
   //
   $image_size = getimagesize($src_filename);
   
	 if (($image_size[0] > $image_max_width) || ($image_size[1] > $image_max_height))	{
      // Determine if the width or height is larger.
      if ($image_size[0] > $image_size[1])
      {
         // Calculate percentage of thumbnail width of original width.
         $image_width = $image_max_width;
         $scale = $image_width / $image_size[0];
    
         // Calculate the corresponding height based on the scale.
         $image_height = $image_size[1] * $scale;
         
         // Check to ensure new height is not greater than max height.
         if ($image_height > $image_max_height) {
	         // Calculate percentage of thumbnail height of original height.
	         $image_height = $image_max_height;
	         $scale = $image_height / $image_size[1];
	   
	         // Calculate the corresponding width based on the scale.
	         $image_width = $image_size[0] * $scale;         	
         }
      }
      else
      {
         // Calculate percentage of thumbnail height of original height.
         $image_height = $image_max_height;
         $scale = $image_height / $image_size[1];
   
         // Calculate the corresponding width based on the scale.
         $image_width = $image_size[0] * $scale;

         // Check to ensure new width is not greater than max width.
         if ($image_width > $image_max_width) {
	         // Calculate percentage of thumbnail width of original width.
	         $image_width = $image_max_width;
	         $scale = $image_width / $image_size[0];
	    
	         // Calculate the corresponding height based on the scale.
	         $image_height = $image_size[1] * $scale;         	
         }
      }
	} else {
        $image_width = $image_size[0];
        $image_height = $image_size[1];	
	}

	//
	// Prepare images for watermarking and resizing.
	//

	// Create image
	switch($image_size[2]) {
		case IMAGETYPE_GIF :
			$src_img = imagecreatefromgif($src_filename);
			break;
		case IMAGETYPE_JPEG :
			$src_img = imagecreatefromjpeg($src_filename);
			break;
		case IMAGETYPE_PNG :
			$src_img = imagecreatefrompng($src_filename);
			break;
		default :
			echo "Image Type Not Supported!";
	}
			
	imagealphablending($src_img, true);

	$dst_img = imagecreatetruecolor($image_width, $image_height);

	// Watermark image
	if ($embedWatermark == true) {
		  // Get watermark image type
		  $watermark_image_size = getimagesize($watermarkImage);
		  
	    // Prepare watermark
			// Create watermark image
			switch($watermark_image_size[2]) {
				case IMAGETYPE_GIF :
					$src_watermark = imagecreatefromgif($watermarkImage);
					break;
				case IMAGETYPE_JPEG :
					$src_watermark = imagecreatefromjpeg($watermarkImage);
					break;
				case IMAGETYPE_PNG :
					$src_watermark = imagecreatefrompng($watermarkImage);
					break;
				default :
					// echo "Image Type Not Supported!";
			}
			
	      $watermark_width = imagesx($src_watermark);
	      $watermark_height = imagesy($src_watermark);

	      // Scale watermark
	      $watermark_new_width = $image_size[0] - 10;
	      $wm_scale = $watermark_new_width / $watermark_width;
    
          // Calculate the corresponding watermark height based on the scale.
          $watermark_new_height = $watermark_height * $wm_scale;
	      
	      // Create new watermark image
	      $dst_watermark = imagecreatetruecolor($watermark_new_width, $watermark_new_height);
          imagealphablending($dst_watermark, false);
	      
          // Resize watermark
          imagecopyresampled($dst_watermark, $src_watermark, 0, 0, 0, 0, $watermark_new_width, $watermark_new_height, $watermark_width, $watermark_height);

	      // Position watermark
	      $dest_x = $image_size[0] - $watermark_new_width - 5;
	      $dest_y = $image_size[1] - $watermark_new_height - 5;
	   
	      // Embed watermark
	      // imagecopymerge($src_img, $dst_watermark, $dest_x, $dest_y, 0, 0, $watermark_new_width, $watermark_new_height, 100);
	      imagecopy($src_img, $dst_watermark, $dest_x, $dest_y, 0, 0, $watermark_new_width, $watermark_new_height); 
	}

    // Resize image
    imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $image_width, $image_height, $image_size[0], $image_size[1]);

	// Not necessary.  Testing to get a GIF with > 128 color palette
	// imagecolormatch($src_img, $dst_img);

  //
	// Save image.
	//
	switch($image_size[2]) {
		case IMAGETYPE_GIF :
		    imagegif($dst_img, $dst_filename);
			break;
		case IMAGETYPE_JPEG :
		    imagejpeg($dst_img, $dst_filename, $jpeg_quality);
			break;
		case IMAGETYPE_PNG :
		    imagepng($dst_img, $dst_filename);
			break;
		default :
			// echo "Image Type Not Supported!";
	}

    // Get the width and height of the new image.
    $new_image_size = getimagesize($dst_filename);

    imagedestroy($src_img);
    imagedestroy($dst_img);
    imagedestroy($src_watermark);
    imagedestroy($dst_watermark);

   //
   // Create jpeg thumbnail copy of image.
   //

   if ($createThumbnail == true)
   {
    // Check to see if thumbnail already smaller than passed in pixel value
	  if ($new_image_size[0] < $thumbnail_max_width && $new_image_size[1] < $thumbnail_max_height) {
  	  $thumbnail_width = $new_image_size[0];
		  $thumbnail_height = $new_image_size[1];
	  } else {
		  // Determine if the thumbnail max width or height is larger.
		  if ($thumbnail_max_width < $thumbnail_max_height)
		  {
			 // Calculate percentage of thumbnail width of original width.
			 $thumbnail_width = $thumbnail_max_width;
			 $scale = $thumbnail_width / $new_image_size[0];
		 
			 // Calculate the corresponding height based on the scale.
			 $thumbnail_height = $new_image_size[1] * $scale;
		  }
		  else
		  {
			 // Calculate percentage of thumbnail height of original height.
			 $thumbnail_height = $thumbnail_max_height;
			 $scale = $thumbnail_height / $new_image_size[1];
		 
			 // Calculate the corresponding width based on the scale.
			 $thumbnail_width = $new_image_size[0] * $scale;
		  }
	  }
      
	  // Do the actual resizing.
    $new_basename = basename($dst_filename, "." . $extension);
		// $new_filename = $new_basename . $thumbnail_suffix . $extension;
    $new_filename = $new_basename . $thumbnail_suffix . ".jpg";
    $tn_dst_filename = $image_root . $new_filename;
   
	// Create image
	switch($image_size[2]) {
		case IMAGETYPE_GIF :
			$src_img = imagecreatefromgif($src_filename);
			break;
		case IMAGETYPE_JPEG :
			$src_img = imagecreatefromjpeg($src_filename);
			break;
		case IMAGETYPE_PNG :
			$src_img = imagecreatefrompng($src_filename);
			break;
		default :
			// echo "Image Type Not Supported!";
	}
			
	  $dst_img = imagecreatetruecolor($thumbnail_width, $thumbnail_height);
   
      imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $thumbnail_width, $thumbnail_height, $new_image_size[0], $new_image_size[1]);
      imagejpeg($dst_img, $tn_dst_filename, $jpeg_quality);
      imagedestroy($src_img);
      imagedestroy($dst_img);
   
      // Get the width and height of the thumbnail.
      $tn_image_size = getimagesize($tn_dst_filename);
   }

   //
   // Populate image_info array so it can be returned.
   //
   $image_info["image_file"] = basename($dst_filename);
   $image_info["tn_file"] = $new_filename;
   $image_info["image_size"] = $new_image_size;
   $image_info["tn_image_size"] = $tn_image_size;

   return $image_info;
}
?>