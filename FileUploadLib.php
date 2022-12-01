<?
/* -----------------------------------------------------------------------------------
Filename: FileUploadLib.php
Author: MWM Consulting, Inc.
http://www.mwmconsulting.biz

Description: File Upload functions & classes
NOTE - Includes upload code written by Sloppycode.net.
       See below for Sloppycode.net � information.

Copyright MWM Consulting, Inc.
All Rights Reserved
----------------------------------------------------------------------------------- */

/*
*******************************************************************************
* � Sloppycode.net All rights reserved.
*
* This is a standard copyright header for all source code appearing
* at sloppycode.net. This application/class/script may be redistributed,
* as long as the above copyright remains intact. 
* Comments to sloppycode@sloppycode.net
*******************************************************************************
*/

/**
 * @title Upload class - wrapper for uploading files. See accompanying docs
 * @author C.Small
 * @version 1.0
 *
 * More features and better error checking will come in the next version
**/

/*
The Upload class is a wrapper for uploading files using html forms. The form 
should have the 'enctype="multipart/form-data"' attribute for this the files to 
be uploaded properly. The Class should be created by passing $HTTP_POST_FILES 
as the only argument for the constructor 
(e.g. $upload = new Upload(&$HTTP_POST_FILES)), and also by reference 
(the & indicates this). See the example for a sample usage. 

Methods/Properties 
------------------

Methods: 
$upload->Upload(&$HTTP_POST_FILES) 
Constructor for the class. This should be called with $HTTP_POST_FILES, passed 
by reference (using & prefixed to it), for the Class to work correctly. See the 
example for usage.  

$upload->save($directory, $field, $overwrite,$mode=0777) 
Saves the form field specified in $field, to the directory specified by 
$directory, using the filename of the file uploaded. If $overwrite is set to 
true, this will overwrite the file if it already exists in the directory. 
$mode is the unix mode to save as, default is 777. Returns true if the upload 
was succesful, or false if not - the error can then be retrieved with the 
$errors property.  

$upload->saveAs($filename, $directory, $field, $overwrite,$mode=0777) 
Saves the form field specified in $field, to the directory specified by 
$directory, with the filename specified by $filename. If $overwrite is set to 
true, this will overwrite the file if it already exists in the directory. 
$mode is the unix mode to save as, default is 777. Returns true if the upload 
was succesful, or false if not - the error can then be retrieved with the 
$errors property.  

$upload->getFilename($field) 
Returns a string with the filename for the html form field specified by $field.  

$upload->getFileMimeType($field) 
Returns a string with the mime type (e.g. image/gif) for the html form field 
specified by $field.  

$upload->getFileSize($field) 
Returns a string with the filesize of a html form field, specified by $field.  
  
Properties: 
$upload->maxupload_size 
Maximum size, in bytes, that any uploaded file can be. If the filesize exceeds 
this, an error is stored in the $errors property, and the save() and saveas() 
methods will return false.  

$upload->errors 
Contains an error description if the save() or saveAs() methods returned false.  

$upload->isPosted 
Use this property to determine whether the form has been posted or not. The 
save and saveAs methods detect whether the form has been posted anyhow, this 
property can be used with the getFilename etc. methods, as shown in the example.  

*/

function UploadFile($directory, $field, $file_overwrite = false, $file_next_name = false, $max_file_size = "")
{
   global $HTTP_POST_FILES;

   //
   // Upload the file.
   //
   $upload = new Upload($HTTP_POST_FILES);
   
   if ($max_file_size == "") {
   	$upload->maxupload_size = -1;
   } else {
   	$upload->maxupload_size = $max_file_size;
   }
   
   $filename = $upload->getFileName($field);
   
   if ($upload->save($directory, $field, $file_overwrite, $file_next_name) == false)
   {
      return $upload->errors;
   }

   $file_info["filename"] = $filename;
   
   return $file_info;
}

Class Upload
{
   var $maxupload_size;
   var $HTTP_POST_FILES;
   var $errors;
   var $final_filename;
	

   //--------------------------------------------------------------------------
   // Constructor
   //--------------------------------------------------------------------------
   function Upload($HTTP_POST_FILES)
   {
      $this->HTTP_POST_FILES = $HTTP_POST_FILES;
      $this->isPosted = false;
   }
	

   //--------------------------------------------------------------------------
   // save
   //--------------------------------------------------------------------------
   function save($directory, $field, $overwrite, $next_name, $mode=0777)
   {
      $tempName = $this->HTTP_POST_FILES[$field]['tmp_name'];
      $filename = str_replace(" ", "_", $this->HTTP_POST_FILES[$field]['name']);
      $all = $directory . $filename;
      
      if ($this->HTTP_POST_FILES[$field]['error'] > 0)
      {
      	switch ($this->HTTP_POST_FILES[$field]['error'])
      	{
      		case "1":
      			$this->errors = "PHP ERROR: <strong>$filename</strong> exceeded upload_max_filesize directive in php.ini file.<BR>";
      			break;
      		case "2":
      			$this->errors = "PHP ERROR: <strong>$filename</strong> exceeded maximum file size.<BR>";
      			break;
      		case "3":
      			$this->errors = "PHP ERROR: <strong>$filename</strong> only partially uploaded.<BR>";
      			break;
      		case "4":
      			$this->errors = "PHP ERROR: <strong>$filename</strong> not uploaded.<BR>";
      			break;
      		default:
      			$this->errors = "PHP ERROR: Unknown error.<BR>";
      	}
      	
      	return false;	
    	}
      elseif (($this->HTTP_POST_FILES[$field]['size'] < $this->maxupload_size || $this->maxupload_size == -1) && 
          $this->HTTP_POST_FILES[$field]['size'] > 0)
      {
         $noerrors = true;
         $this->isPosted = true;
	
         if (file_exists($all))
         {
            if ($overwrite)
            {
               @unlink($all) || $noerrors = false; 
               $this->errors  = "ERROR: Unable to overwrite <strong>$filename</strong>.<BR>";

               @copy($tempName,$all) || $noerrors = false; 
               $this->errors .= "ERROR: Unable to copy to <strong>$filename</strong>.<BR>";

               @chmod($all,$mode) || $noerrors = false; 
               $this->errors .= "ERROR: Unable change permissions for: ";
               $this->errors .= "<strong>$filename</strong>.<BR>";

               $this->final_filename = $filename;
            }
						else if ($next_name)
						{
							$file_count = 0;
							
							while (file_exists($all)) {
								$file_count++;
			
								$basename = basename($filename, strrchr($filename, "."));
								$new_basename = $basename . "_" . $file_count;
								$new_filename = str_replace(basename($filename, strrchr($filename, ".")), $new_basename, $filename);
								
								$all = $directory.$new_filename;
							}
				
               @copy($tempName,$all) || $noerrors = false; 
               $this->errors .= "ERROR: Unable to copy to <strong>$filename</strong>.<BR>";

               @chmod($all,$mode) || $noerrors = false; 
               $this->errors .= "ERROR: Unable change permissions for: ";
               $this->errors .= "<strong>$filename</strong>.<BR>";

               $this->final_filename = $new_filename;
						}
            else 
            {
               $noerrors = false; 
               $this->errors  = "ERROR: File named <strong>$filename</strong> already ";
               $this->errors .= "exists.<BR>";
            }
         } 
         else 
         {
            @copy($tempName,$all) || $noerrors = false;
            $this->errors = "ERROR: Unable to copy to <strong>$filename</strong>.<BR>";

            @chmod($all,$mode) || $noerrors = false;
            $this->errors .= "ERROR: Unable to change permissions for: ";
            $this->errors .= "<strong>$filename</strong>.<BR>";

            $this->final_filename = $filename;
         }

         return $noerrors;
      } 
      elseif ($this->HTTP_POST_FILES[$field]['size'] > $this->maxupload_size) 
      {
         $this->errors = "ERROR: File size for <strong>$filename</strong> exceeds maximum allowed file size ";
         $this->errors .= "of ".$this->maxupload_size." bytes.";
         return false;
      } 
      elseif ($this->HTTP_POST_FILES[$field]['size'] == 0) 
      {
         $this->errors = "ERROR: File size for <strong>$filename</strong> is 0 bytes.";
         return false;
      }
   }
	

   //--------------------------------------------------------------------------
   // saveAs
   //--------------------------------------------------------------------------
   function saveAs($filename, $directory, $field, $overwrite, $next_name, $mode=0777)
   {
      if ($this->HTTP_POST_FILES[$field]['error'] > 0)
      {
      	switch ($this->HTTP_POST_FILES[$field]['error'])
      	{
      		case "1":
      			$this->errors = "PHP ERROR: <strong>$filename</strong> exceeded upload_max_filesize directive in php.ini file.<BR>";
      			break;
      		case "2":
      			$this->errors = "PHP ERROR: <strong>$filename</strong> exceeded maximum file size.<BR>";
      			break;
      		case "3":
      			$this->errors = "PHP ERROR: <strong>$filename</strong> only partially uploaded.<BR>";
      			break;
      		case "4":
      			$this->errors = "PHP ERROR: <strong>$filename</strong> not uploaded.<BR>";
      			break;
      		default:
      			$this->errors = "PHP ERROR: Unknown error.<BR>";
      	}
      	
      	return false;	
    	}
      elseif ($this->HTTP_POST_FILES[$field]['size'] < $this->maxupload_size && 
          $this->HTTP_POST_FILES[$field]['size'] >0)
      {
         $noerrors = true;

         $tempName = $this->HTTP_POST_FILES[$field]['tmp_name'];
         $all = $directory . $filename;
			
         if (file_exists($all))
         {
            if ($overwrite)
            {
               @unlink($all) || $noerrors = false; 
               $this->errors = "ERROR: Unable to overwrite <strong>$filename</strong>.<BR>";

               @copy($tempName,$all) || $noerrors = false; 
               $this->errors .= "ERROR: Unable to copy to <strong>$filename</strong>.<BR>";

               @chmod($all,$mode) || $noerrors = false; 
               $this->errors .= "ERROR: Unable to change permissions for: ";
               $this->errors .= "<strong>$filename</strong>.<BR>";

               $this->final_filename = $filename;
            } 
						else if ($next_name)
						{
							$file_count = 0;
							
							while (file_exists($all)) {
								$file_count++;
			
								$basename = basename($filename, strrchr($filename, "."));
								$new_basename = $basename . "_" . $file_count;
								$new_filename = str_replace(basename($filename, strrchr($filename, ".")), $new_basename, $filename);
								
								$all = $directory.$new_filename;
							}
				
               @copy($tempName,$all) || $noerrors = false; 
               $this->errors .= "ERROR: Unable to copy to <strong>$filename</strong>.<BR>";

               @chmod($all,$mode) || $noerrors = false; 
               $this->errors .= "ERROR: Unable change permissions for: ";
               $this->errors .= "<strong>$filename</strong>.<BR>";

               $this->final_filename = $new_filename;
						}
            else 
            {
               $noerrors = false; 
               $this->errors  = "ERROR: File named <strong>$filename</strong> already ";
               $this->errors .= "exists.<BR>";
            }
         } 
         else
         {
            @copy($tempName,$all) || $noerrors = false; 
            $this->errors = "ERROR: Unable to copy to <strong>$filename</strong>.<BR>";

            @chmod($all,$mode) || $noerrors = false; 
            $this->errors .= "ERROR: Unable to change permissions for: ";
            $this->errors .= "<strong>$filename</strong>.<BR>";

            $this->final_filename = $filename;
         }

         return $noerrors;
      } 
      elseif ($this->HTTP_POST_FILES[$field]['size'] > $this->maxupload_size) 
      {
         $this->errors = "ERROR: File size for <strong>$filename</strong> exceeds maximum allowed file size ";
         $this->errors .= "of " . $this->maxupload_size . " bytes.";
         return false;
      } 
      elseif ($this->HTTP_POST_FILES[$field]['size'] == 0) 
      {
         $this->errors = "ERROR: File size for <strong>$filename</strong> is 0 bytes.";
         return false;
      }
   }


   //--------------------------------------------------------------------------
   // getFilename
   //--------------------------------------------------------------------------
   function getFilename($field)
   {
      return $this->HTTP_POST_FILES[$field]['name'];
   }
	

   //--------------------------------------------------------------------------
   // getFileMimeType
   //--------------------------------------------------------------------------
   function getFileMimeType($field)
   {
      return $this->HTTP_POST_FILES[$field]['type'];
   }
	

   //--------------------------------------------------------------------------
   // getFileSize
   //--------------------------------------------------------------------------
   function getFileSize($field)
   {
      return $this->HTTP_POST_FILES[$field]['size'];
   }
}

?>