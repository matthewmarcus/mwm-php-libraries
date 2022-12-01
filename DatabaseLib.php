<?
/*-----------------------------------------------------------------------------
Filename: DatabaseLib.php
Author: MWM Consulting, Inc.
http://www.mwmconsulting.biz

Description: Database functions

Copyright MWM Consulting, Inc., May 2008
All Rights Reserved
-----------------------------------------------------------------------------*/

//-----------------------------------------------------------------------------
// function DB_Connect ($sHost, $sUser, $sPassword)
//
// Returns an integer pointer to the new connection link
//-----------------------------------------------------------------------------
function DB_Connect ($host, $sUser, $sPassword) 
{
   $dbLink = mysql_connect($host, $sUser, $sPassword) or 
               trigger_error("Could not connect to " . $host . ".\n<br>");
   return $dbLink;
}


//-----------------------------------------------------------------------------
// function DB_Disconnect ($dbLink)
//-----------------------------------------------------------------------------
function DB_Disconnect ($dbLink) 
{
   mysql_close($dbLink) or 
      trigger_error ("Could not close database connection.\n<br>");
}


//-----------------------------------------------------------------------------
// function DB_Query ($sDatabase, $sSQL, $iLink)
//
// Returns an integer pointer to the sql result set
//-----------------------------------------------------------------------------
function DB_Query ($sDatabase, $sSQL, $iLink) 
{
   $iResult = mysql_db_query($sDatabase, $sSQL, $iLink) or 
                 trigger_error("SQL Query \"" . $sSQL . "\" failed.\n<br>");
   return $iResult;
}


//-----------------------------------------------------------------------------
// function DB_Fetch ($sDatabase, $sSQL, $iLink)
//
// Returns a populated array with the entire sql result set
//-----------------------------------------------------------------------------
function DB_Fetch($sDatabase, $sSQL, $iLink)
{
   $iResult = DB_Query($sDatabase, $sSQL, $iLink);
   
   for ($i = 0; $i < mysql_num_rows($iResult); $i++)
   {
      $aResultMatrix[$i] = mysql_fetch_array($iResult);
   }

   return $aResultMatrix;
}
?>