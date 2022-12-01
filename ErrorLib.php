<?
/* -----------------------------------------------------------------------------------
Filename: ErrorLib.php
Author: MWM Consulting, Inc.
http://www.mwmconsulting.biz

Description: Error PHP functions

Copyright MWM Consulting, Inc., May 2008
All Rights Reserved
----------------------------------------------------------------------------------- */

// Function List
// -------------
// Error_Control		Handle errors and output error message

// Function Declarations
// ---------------------

// ---------------------------------------------------------------
// function Error_Control ($errno, $errstr, $errfile, $errline)
//	A helper subroutine to check for an error, output the error
//   	and specified output message.
//
//	Does not account for PHP Warnings or Notices.
//
// Variables:
//	$errno :	Error Number
//	$errstr :	Error Description
//	$errfile :	Error File
//	$errline :	Error Line
//
// Created by Matthew W Marcus
// ---------------------------------------------------------------

function Error_Control ($errno, $errstr, $errfile, $errline) {
	if ($errno != E_WARNING && $errno != E_NOTICE) {
		echo "<table border='0'>\n";
		echo "<tr><td colspan='3'><h2>System Error</h2></td></tr>\n";
		echo "<tr><td colspan='3'><h3>The following error has occured:</h3></td></tr>\n";
		echo "<tr><td valign='top'><b>Error Number:</b></td><td>&nbsp;</td><td>" . $errno . "</td></tr>\n";
		echo "<tr><td valign='top'><b>Error Source:</b></td><td>&nbsp;</td><td>File: " . $errfile . "<br>Line: " . $errline . "</td></tr>\n";
		echo "<tr><td valign='top'><b>Error Description:</b></td><td>&nbsp;</td><td>" . $errstr . "<br></td></tr>\n";
		echo "<tr><td>&nbsp;</td></tr>\n";
		echo "<tr><td colspan='3'><b>Please contact the <b>Metal Logos & More Webmaster</b> at <a href='mailto://webmaster@metallogos.com'>webmaster@metallogos.com</a> and report this error.  Thank you.</b></td></tr>\n";
		echo "</table>\n";
		exit();
	}
}

// Set error handler to error control function
set_error_handler("Error_Control");
?>