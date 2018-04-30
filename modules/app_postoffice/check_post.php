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
//$log = Logger::getLogger("PostOffice");
$log = getLogger();

try 
{
   $PostOffice = new app_postoffice();
   $result = $PostOffice->CheckPostTrack();
   if (!$result)
      throw new Exception("Check post error");
   
   SendResultToClient();
   
}
catch(Exception $e)
{
   $log->error("PostOffice Error: " . $e->getMessage());
}

function GetMailTemplateHeader($template, $beginLoop)
{
   return substr($template, 0, strpos($template,$beginLoop));
}

function GetMailTemplateLoop($template, $beginLoop, $endLoop)
{
   
   $from = strpos($template, $beginLoop) + strlen($beginLoop);
   $to   = strpos($template, $endLoop);

   $loopStr = substr($template, $from, $to);
   $to      = strpos($loopStr, $endLoop);
   $loopStr = substr($loopStr, 0, $to);

   return $loopStr;
}

function GetMailTemplateFooter($template, $endLoop)
{
   return substr($template,  strpos($template, $endLoop) + strlen($endLoop), strlen($template));
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
   

   $template = file_get_contents(dirname(__FILE__) . "/mail_template.html");
   $beginLoop = "{{loop}}";
   $endLoop = "{{end_loop}}";

   $header = GetMailTemplateHeader($template, $beginLoop);
   $footer = GetMailTemplateFooter($template, $endLoop);
   $loopString = GetMailTemplateLoop($template, $beginLoop, $endLoop);
   $list = "";
   
   foreach ($tracks as $track)
   {
      $curStr = str_replace("{{TRACK_ID}}", $track['TRACK_ID'], $loopString);
      $curStr = str_replace("{{TRACK_NAME}}", $track['TRACK_NAME'], $curStr);
      $curStr = str_replace("{{OPER_DATE}}", $track['OPER_DATE'], $curStr);
      $curStr = str_replace("{{ATTRIB_NAME}}", $track['ATTRIB_NAME'], $curStr);
      $curStr = str_replace("{{OPER_POSTPLACE}}", $track['OPER_POSTPLACE'], $curStr);
      $list .= $curStr;
   }
   
   $message = $header . $list . $footer;
   
   return $message;
}


function SendResultToClient()
{
   try 
   {
      $PostOffice = new app_postoffice();
      $notifySettings = $PostOffice->SelectNotifySettings();
      if (!isset($notifySettings))
         return;
      
      if ($notifySettings["FLAG_SEND"] == "N")
         return;
      
      $mailTo   = $notifySettings["NOTIFY_EMAIL"];
      
      if (!isset($mailTo))
         return;
      
      $mailFrom = $mailTo;
      
      $mailSubject = !isset($notifySettings["NOTIFY_SUBJ"]) ? "[PostOffice] Post notification" : $notifySettings["NOTIFY_SUBJ"];
      
      $mailBody = GetTrackInfoForNotify();
      
      if (!isset($mailBody))
         return;
      
      SendMail_html($mailFrom, $mailTo, utf2win($mailSubject), utf2win($mailBody));
   }
   catch (Exception $e)
   {
      throw new Exception($e->getMessage());
   }
}

?>