<?php

// change dir to root folder
chdir(dirname(__FILE__) . '/../..');

// load config & libs
include_once("./config.php");
include_once("./lib/loader.php");

// load postoffice class
require_once(DIR_MODULES . '/app_postoffice/app_postoffice.class.php');

// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME); 

// start logger
$log = Logger::getLogger("PostOffice");
try 
{
   $PostOffice = new app_postoffice();
   $result = $PostOffice->CheckPostTrack();
   if (!$result)
      throw new Exception("Check post error");
   else
      throw new Exception("ok");
}
catch(Exception $e)
{
   $log->error("PostOffice Error: " . $e->getMessage());
}

function GetTrackInfoForNotify()
{
   $message = "";
   
   $PostOffice = new app_postoffice();
   $tracks = $PostOffice->GetLastCheckedTracks();
   
   if (!is_array($tracks))
      return $message;
   
   if (count($tracks) == 0)
      return $message;
   
   $mailBody .= "<table>";
   $mailBody .= "<tr>";
   $mailBody .="<th>Трек номер</th>";
   $mailBody .="<th>Название трека</th>";
   $mailBody .="<th>Дата операции</th>";
   $mailBody .="<th>Наименование операции</th>";
   $mailBody .="<th>Местонахождение</th>";
   $mailBody .= "</tr>";
   foreach ($tracks as $track)
   {
      $mailBody .= "<tr>";
      $mailBody .="<td>" . $track['TRACK_ID']       . "</td>";
      $mailBody .="<td>" . $track['TRACK_NAME']     . "</td>";
      $mailBody .="<td>" . $track['OPER_DATE']      . "</td>";
      $mailBody .="<td>" . $track['ATTRIB_NAME']    . "</td>";
      $mailBody .="<td>" . $track['OPER_POSTPLACE'] . "</td>";
      $mailBody .= "</tr>";
   }
   
   $mailBody .= "</table>";
   
   return $message;
}

function SendResultToClient()
{
   try 
   {
      $mailBody = GetTrackInfoForNotify();
      
   }
   catch (Exception $e)
   {
      throw new Exception($e->getMessage());
   }
}


?>