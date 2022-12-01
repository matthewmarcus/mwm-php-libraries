<?
/*-----------------------------------------------------------------------------
Filename: MiscLib.php
Author: MWM Consulting, Inc.
http://www.mwmconsulting.biz

Description: Email PHP functions

Copyright MWM Consulting, Inc., May 2008
All Rights Reserved
-----------------------------------------------------------------------------*/

// ---------------------------------------------------------------
// function Email_Hijack_Check($sMessage)
//
// Check email for injection headers
//
// Created by
//	MWM Consulting, Inc.
//	May 2008
// ---------------------------------------------------------------
function Email_Hijack_Check($sMessage) {
  // Create injection array
	$header_injection_attempts = array(
	"bcc:",
	"cc:",
	"to:",
	"content-type:",
	"mime-version:",
	"multipart/mixed",
	"content-transfer-encoding:",
	"charset="
	);

	// Lowercase the email
	$email_body_lower = strtolower($sMessage);

	// Innocent until proven guilty
	$injection_attempted = false;
	
	foreach($header_injection_attempts as $attempt){
		// Check the email for each possible attempt
		if(strpos($email_body_lower, $attempt) !== false) {
			// We found something bad being attempted
			$injection_attempted = true;
			break;
		}
	}
	
	return $injection_attempted;
}


// ---------------------------------------------------------------
// function Send_Email($sTo, $sCC, $sBCC, $sFrom, $sSubject, $sMessage, $sHeader, $aAttachment)
//
//	Send email w/ optional attachment
//
// Variables:
//	$sTo : Recepient's e-mail address(es)
//	$sCC : Carbon Copy e-mail address(es)
//		blank by default
//	$sBCC : Blind Carbon Copy e-mail address(es)
//		blank by default
//	$sFrom : Sender's e-mail address
//	$sSubject : Subject of message
//	$sMessage : Message
//	$sHeader : Extra e-mail header info (i.e. Reply-to, etc.)
//  $aAttachment : File to attach to email
//
// Created by
//	MWM Consulting, Inc.
//	May 2008
// ---------------------------------------------------------------
function Send_Email ($sTo, $sCC = "", $sBCC = "", $sFrom, $sSubject, $sMessage, $sHeader = "", $aAttachment = "") {
	// Set default return
	$bEmailSuccessful = "true";
	
  // Check for attempted hijacking
	$injection_attempted = Email_Hijack_Check($sMessage);

 	// Construct General E-mail header
	$sNewHeader = "From: " . $sFrom . "\n";
	if ($sCC != "") {
		$sNewHeader .= "Cc: " . $sCC . "\n";
	}
	if ($sBCC != "") {
		$sNewHeader .= "Bcc: " . $sBCC . "\n";
	}

	$sNewHeader .= "Return-Path: " . $sFrom . "\n";
	$sNewHeader .= "Reply-To: " . $sFrom . "\n";

	// Check if email has attachment
	if (($aAttachment != "") && ($aAttachment['file_attachment']['error'] != UPLOAD_ERR_NO_FILE)) {
		// Obtain file details
		$fileatt = $aAttachment['file_attachment']['tmp_name'];
		$fileatt_type = $aAttachment['file_attachment']['type'];
		$fileatt_name = $aAttachment['file_attachment']['name'];
		$fileatt_error = $aAttachment['file_attachment']['error'];

		// Check for file upload error
		if ($fileatt_error == UPLOAD_ERR_OK) {
			// Attach file to email
			if (is_uploaded_file($fileatt)) {
				// Read the file to be attached ('rb' = read binary)
			 	$file = fopen($fileatt,'rb');
			 	$data = fread($file,filesize($fileatt));
				fclose($file);
	
				// Base64 encode the file data
				$data = chunk_split(base64_encode($data));
		
		 	  // Generate a boundary string
				$semi_rand = md5(time());
			 	$mime_boundary = "==Multipart_Boundary_m{$semi_rand}m"; 
		
				// Construct Attachment E-mail header
				$sNewHeader .= "MIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\"";
				
			  // Add a multipart boundary above the plain message
			 	$message = "This is a multi-part message in MIME format.\n\n" . "--{$mime_boundary}\n" . "Content-Type: text/plain; charset=\"iso-8859-1\"\n" . "Content-Transfer-Encoding: 7bit\n\n" . $sMessage . "\n\n";
			 	
				// Add file attachment to the message
				$message .= "--{$mime_boundary}\n" . "Content-Type: {$fileatt_type};\n" . " name=\"{$fileatt_name}\"\n" . "Content-Disposition: attachment;\n" . " filename=\"{$fileatt_name}\"\n" . "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n" . "--{$mime_boundary}--\n";

			 	// Overwrite original message with new message for sending
			 	$sMessage = $message;
			}
		} else if ($fileatt_error == UPLOAD_ERR_FORM_SIZE) {
			$bEmailSuccessful = "File upload exceeds maximum file size.";
		} else {
			$bEmailSuccessful = "File upload error.";
		}
	} else {
		// Construct Message E-mail header
		$sNewHeader .= "Mime-Version: 1.0\n";
		$sNewHeader .= "Content-type: text/plain; charset=iso-8859-1\n";
		$sNewHeader .= "Content-Transfer-Encoding: 7bit\n";
	}
	
	if ($sHeader != "") {
		$sNewHeader .= $sHeader;
	}
		
//	echo "Header = " . $sNewHeader . "<br><br>";
//	echo "Message = " . $sMessage . "<br><br>";

	if ($bEmailSuccessful == "true") {
		// Send email if not being hijacked
		if($injection_attempted == false) {
			if (mail($sTo, $sSubject, $sMessage, $sNewHeader, "-f" . $sFrom) == false) {
				$bEmailSuccessful = "Send mail error.";
			}
		} else {
			$bEmailSuccessful = "Email hijacking attempt detected.";
		}
	}
	
	return $bEmailSuccessful;
}
?>