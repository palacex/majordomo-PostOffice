<?php

require_once(DIR_MODULES . '/app_postoffice/lib/dal.russianpost.lib.php');
require_once(DIR_MODULES . '/app_postoffice/lib/russianpost.lib.php');
use DAL\RussianPostDAL as RussianPost;

/**
 * Application PostOffice - Check post from RussainPost
 *
 * @package PostOffice
 * @author LDV <dev@silvergate.ru>
 * @version 1.7
 */
class app_postoffice extends module
{
   /**
    * app_postoffice
    * Module class constructor
    * @access private
    */
   function app_postoffice()
   {
      $this->name            = "app_postoffice";
      $this->title           = "PostOffice";
      $this->module_category = "<#LANG_SECTION_APPLICATIONS#>";
      $this->checkInstalled();
   }

   /**
    * saveParams
    * Saving module parameters
    * @access public
    */
   function saveParams($data = 1)
   {
      $p = array();
      
      if (isset($this->id))
         $p["id"] = $this->id;
      
      if (isset($this->view_mode))
         $p["view_mode"] = $this->view_mode;
 
      if (isset($this->edit_mode))
         $p["edit_mode"] = $this->edit_mode;
 
      if (isset($this->tab))
         $p["tab"] = $this->tab;
 
      return parent::saveParams($p);
   }

   /**
    * Getting module parameters from query string
    * @access public
    */
   function getParams()
   {
      global $id;
      global $mode;
      global $view_mode;
      global $edit_mode;
      global $tab;
      
      if (isset($id))
         $this->id = $id;
      
      if (isset($mode))
         $this->mode = $mode;
      
      if (isset($view_mode))
         $this->view_mode = $view_mode;
      
      if (isset($edit_mode))
         $this->edit_mode = $edit_mode;
      
      if (isset($tab))
         $this->tab = $tab;
   }

   /**
    * Run
    * Description
    * @access public
    */
   function run()
   {
      global $session;
      $out = array();
      
      if ($this->action == 'admin')
         $this->admin($out);
      else
         $this->usual($out);
      
      if (isset($this->owner->action))
         $out['PARENT_ACTION'] = $this->owner->action;
      
      if (isset($this->owner->name))
         $out['PARENT_NAME'] = $this->owner->name;
  
      $out['VIEW_MODE'] = $this->view_mode;
      $out['EDIT_MODE'] = $this->edit_mode;
      $out['MODE']      = $this->mode;
      $out['ACTION']    = $this->action;
  
      if ($this->single_rec)
         $out['SINGLE_REC'] = 1;
  
      $this->data = $out;
      $p = new parser(DIR_TEMPLATES . $this->name . "/" . $this->name . ".html", $this->data, $this);
      $this->result = $p->result;
   }
   
   /**
    * BackEnd
    * Module backend
    * @access public
    */
   function admin(&$out)
   {
      if ($this->view_mode=='trackhistory_app_postoffice') 
         $this->trackhistory_app_postoffice($out, $this->id);
      
      $action = isset($_REQUEST['act']) ?  $_REQUEST['act'] : "show";
      
      if ($action == "del")
      {
         $resultMessage = "";
         try
         {
            // Get TrackNumber form request
            $trackID = isset($_REQUEST['trackid']) ? $_REQUEST['trackid'] : null; 
            // Exit then TrackNumber does't exist.
            if ($trackID == null)
               throw new Exception("TrackNumber not found.");
         
            //remove TrackNumber From Database
            RussianPost::DeleteTrackDetailByID($trackID);
            $isTrackID =  RussianPost::DeleteTrack($trackID);
            
            $resultMessage = "TrackNumber was delete from database";
         }
         catch(Exception $e)
         {
            $resultMessage = "Oops! We have error: " . $e->getMessage();
         }
         
         echo $resultMessage;
         $action = "";
         exit();
         return;
      }
      else if ($action == "add")
      {
         $resultMessage = "";
         try
         {
            // Get TrackNumber form request
            $trackID   = isset($_REQUEST['trackid'])   ? $_REQUEST['trackid']    : null;
            $trackName = isset($_REQUEST['trackname']) ? $_REQUEST['trackname']  : null;
            $trackInfoUrl = isset($_REQUEST['trackurl']) ? $_REQUEST['trackurl']  : null;
            // Exit then TrackNumber does't exist.
            if ($trackID == null)
               throw new Exception("TrackNumber not found.");
            
            $trackName = $trackName == null ? $trackID : $trackName;
           
            //add TrackNumber to Database
            RussianPost::AddTrack($trackID, $trackName, $trackInfoUrl);
            $url = isset($_REQUEST['backurl']) ? $_REQUEST['backurl']  : "admin.php?pd=&md=panel&inst=&action=app_postoffice";
            if ($url != "none")
            {
               header_remove();
               header("Location: " . $url, true);
            }
            die();
         }
         catch(Exception $e)
         {
            $resultMessage = "Oops! We have error: " . $e->getMessage();
         }
         
         //echo $resultMessage;
         $action = "";
         exit();
         return;
      }
      else if ($action == "check")
      {
         $result = $this->CheckPostTrack() ? "Russian Post ckeck is complete" : "Error! Error message in log file.";
         echo $result;
         exit();
         return;
      }
      else if ($action == "changestatus")
      {
         $resultMessage = "";
         try
         {
            // Get TrackNumber form request
            $trackID = isset($_REQUEST['trackid']) ? $_REQUEST['trackid'] : null; 
            // Exit then TrackNumber does't exist.
            if ($trackID == null)
               throw new Exception("TrackNumber not found.");
            
            $res = RussianPost::UpdateTrackStatus($trackID);
            
            $resultMessage = $res == true ? "Track status was changed" : "Track status can't change";
         }
         catch(Exception $e)
         {
            $resultMessage = "Oops! We have error: " . $e->getMessage();
         }
         
         echo $resultMessage;
         $action = "";
         exit();
         return;
      }
      else if ($action == "proxy")
      {
         $resultMessage = "";
         try
         {
            // Get TrackNumber form request
            $proxyFlag     = isset($_REQUEST['proxy_flag'])   ? $_REQUEST['proxy_flag']    : null;
            $proxyFlag     = $proxyFlag == null ? "N" : "Y";
            $proxyHost     = isset($_REQUEST['proxy_host'])   ? $_REQUEST['proxy_host']    : null;
            $proxyPort     = isset($_REQUEST['proxy_port'])   ? $_REQUEST['proxy_port']    : null;
            $proxyUser     = isset($_REQUEST['proxy_user'])   ? $_REQUEST['proxy_user']    : null;
            $proxyPassword = isset($_REQUEST['proxy_passwd']) ? $_REQUEST['proxy_passwd']  : null;
            $currFlag      = isset($_REQUEST['curr_flag'])    ? $_REQUEST['curr_flag']     : null; 
            
            //add proxy settings to Database
            $res = RussianPost::SetProxySettings($proxyFlag, $proxyHost, $proxyPort, $proxyUser, $proxyPassword, $currFlag);
            $url = "admin.php?pd=&md=panel&inst=&action=app_postoffice#proxy";
            header_remove();
            header("Location: " . $url, true);
            die();
         }
         catch(Exception $e)
         {
            $resultMessage = "Oops! We have error: " . $e->getMessage();
         }
         
         //echo $resultMessage;
         $action = "";
         exit();
         return;
      }
      else if ($action == "notify")
      {
         $resultMessage = "";
         try
         {
            // Get TrackNumber form request
            $notifyFlag  = isset($_REQUEST['notify_flag'])   ? $_REQUEST['notify_flag']    : null;
            $notifyFlag  = $notifyFlag == null ? "N" : "Y";
            $currFlag    = isset($_REQUEST['curr_notify_flag']) ? $_REQUEST['curr_notify_flag']  : null;
            $notifyEmail = isset($_REQUEST['notify_email'])     ? $_REQUEST['notify_email']      : null;
            $notifySubj  = isset($_REQUEST['notify_subj'])      ? $_REQUEST['notify_subj']       : null;
            $accName     = isset($_REQUEST['pochta_login'])     ? $_REQUEST['pochta_login']      : null;
            $accPassword = isset($_REQUEST['pochta_passwd'])    ? $_REQUEST['pochta_passwd']     : null;
            
            //add proxy settings to Database
            $res = RussianPost::SetNotificationSettings($notifyFlag, $currFlag, $notifyEmail, $notifySubj, $accName, $accPassword);
            $url = "admin.php?pd=&md=panel&inst=&action=app_postoffice#email";
            header_remove();
            header("Location: " . $url, true);
            die();
         }
         catch(Exception $e)
         {
            $resultMessage = "Oops! We have error: " . $e->getMessage();
         }
         
         //echo $resultMessage;
         $action = "";
         exit();
         return;
      }
      else
      {
         $trackArray     = $this->GetLastCheckedTracks();
         $proxySettings  = RussianPost::SelectProxySettings();
         $notifySettings = $this->SelectNotifySettings();
         $trackHistoryArray = $this->SelectHistoryTracks();
         
         $out['TRACK_LIST']  = $trackArray;
         $out['PROXY_LIST']  = $proxySettings;
         $out['NOTIFY_LIST'] = $notifySettings;
         $out['TRACK_HISTORY_LIST']  = $trackHistoryArray;
      }
   }

   /**
    * FrontEnd
    * Module frontend
    * @access public
    */
   function usual(&$out)
   {
      $this->admin($out);
   }
   
  
   /**
    * Install
    * Module installation routine
    * @access private
    */
   function install($parent_name = '')
   {
      
      $val = SQLSelectOne("select count(*)+2 CNT from information_schema.tables where table_schema = '" . DB_NAME . "' and table_name = 'POST_TRACK'");
      $val = $val["CNT"] == 2 ? FALSE : TRUE;
      
      
      if (!file_exists(DIR_MODULES . $this->name . "/installed") && $val == FALSE) 
      {
         SQLExec("drop table if exists POST_PROXY");
         SQLExec("drop table if exists POST_MAIL");
         SQLExec("drop table if exists POST_TRACKINFO");
         SQLExec("drop table if exists POST_TRACK");

         $query = "create table POST_PROXY(";
         $query .= "  FLAG_PROXY           VARCHAR(1) not null default 'N',";
         $query .= "  PROXY_HOST           VARCHAR(64),";
         $query .= "  PROXY_PORT           VARCHAR(4),";
         $query .= "  PROXY_USER           VARCHAR(64),";
         $query .= "  PROXY_PASSWD         VARCHAR(64),";
         $query .= "  LM_DATE              DATETIME not null,";
         $query .= "  primary key (FLAG_PROXY)";
         $query .= "  ) ENGINE=InnoDB CHARACTER SET=utf8;";
         SQLExec($query);

         $query = "insert into POST_PROXY(FLAG_PROXY, PROXY_HOST, PROXY_PORT, PROXY_USER, PROXY_PASSWD, LM_DATE)";
         $query .= "values('N', NULL, NULL, NULL, NULL, NOW());";
         SQLExec($query);

         $query = "create table POST_MAIL(";
         $query .= " FLAG_SEND            VARCHAR(1) not null default 'N',";
         $query .= " LM_DATE              DATETIME not null,";
         $query .= " NOTIFY_EMAIL         VARCHAR(64),";
         $query .= " NOTIFY_SUBJ          VARCHAR(255),";
         $query .= " ACC_NAME             VARCHAR(64),";
         $query .= " ACC_PASSWD           VARCHAR(64),";
         $query .= " primary key (FLAG_SEND)";
         $query .= ") ENGINE=InnoDB CHARACTER SET=utf8;";
         SQLExec($query);

         $query = "insert into POST_MAIL(FLAG_SEND, LM_DATE, NOTIFY_EMAIL, NOTIFY_SUBJ)";
         $query .= " values('N', NOW(), NULL, NULL);";
         SQLExec($query);

         $query = "create table POST_TRACK(";
         $query .= " TRACK_ID             VARCHAR(14) not null,";
         $query .= " TRACK_NAME           VARCHAR(64) not null,";
         $query .= " FLAG_CHECK           VARCHAR(1) not null default 'Y',";
         $query .= " TRACK_DATE           DATETIME not null,";
         $query .= " LM_DATE              DATETIME not null,";
         $query .= " TRACK_URL            VARCHAR(255),";
         $query .= " primary key (TRACK_ID)";
         $query .= ") ENGINE=InnoDB CHARACTER SET=utf8;";
         SQLExec($query);

         $query = "create table POST_TRACKINFO(";
         $query .= " TRACK_ID             VARCHAR(14) not null,";
         $query .= " OPER_DATE            DATETIME not null,";
         $query .= " OPER_TYPE            INT(10) not null,";
         $query .= " OPER_NAME            VARCHAR(64) not null,";
         $query .= " ATTRIB_ID            INT(10),";
         $query .= " ATTRIB_NAME          VARCHAR(64),";
         $query .= " OPER_POSTCODE        INT(10),";
         $query .= " OPER_POSTPLACE       VARCHAR(64) not null,";
         $query .= " ITEM_WEIGHT          DECIMAL(10,6),";
         $query .= " DECLARED_VALUE       DECIMAL(10,6),";
         $query .= " DELIVERY_PRICE       DECIMAL(10,6),";
         $query .= " DESTINATION_POSTCODE INT(10),";
         $query .= " DELIVERY_ADDRESS     VARCHAR(255),";
         $query .= " LM_DATE              DATETIME not null,";
         $query .= " primary key (TRACK_ID, OPER_DATE)";
         $query .= ") ENGINE=InnoDB CHARACTER SET=utf8;";
         SQLExec($query);
         
         SaveFile(DIR_MODULES . $this->name  ."/installed", date("H:m d.M.Y"));
      }
      else
      {
         SQLExec("drop table if exists TMP_POST_PROXY");
         SQLExec("drop table if exists TMP_POST_MAIL");
         SQLExec("drop table if exists TMP_POST_TRACK");
         SQLExec("drop table if exists TMP_POST_TRACKINFO");
         
         SQLExec("create table TMP_POST_PROXY as select * from POST_PROXY");
         SQLExec("create table TMP_POST_MAIL as select * from POST_MAIL");
         SQLExec("create table TMP_POST_TRACK as select * from POST_TRACK");
         SQLExec("create table TMP_POST_TRACKINFO as select * from POST_TRACKINFO");

         SQLExec("drop table if exists POST_PROXY");
         SQLExec("drop table if exists POST_MAIL");
         SQLExec("drop table if exists POST_TRACKINFO");
         SQLExec("drop table if exists POST_TRACK");

         $query = "create table POST_PROXY(";
         $query .= " FLAG_PROXY           VARCHAR(1) not null default 'N',";
         $query .= " PROXY_HOST           VARCHAR(64),";
         $query .= " PROXY_PORT           VARCHAR(4),";
         $query .= " PROXY_USER           VARCHAR(64),";
         $query .= " PROXY_PASSWD         VARCHAR(64),";
         $query .= " LM_DATE              DATETIME not null,";
         $query .= " primary key (FLAG_PROXY)";
         $query .= " ) ENGINE=InnoDB CHARACTER SET=utf8;";
         SQLExec($query);

         $query = "insert into POST_PROXY(FLAG_PROXY, PROXY_HOST, PROXY_PORT, PROXY_USER, PROXY_PASSWD, LM_DATE)";
         $query .= " select * from TMP_POST_PROXY;";
         SQLExec($query);

         $query = "create table POST_MAIL(";
         $query .= " FLAG_SEND            VARCHAR(1) not null default 'N',";
         $query .= " LM_DATE              DATETIME not null,";
         $query .= " NOTIFY_EMAIL         VARCHAR(64),";
         $query .= " NOTIFY_SUBJ          VARCHAR(255),";
         $query .= " ACC_NAME             VARCHAR(64),";
         $query .= " ACC_PASSWD           VARCHAR(64),";
         $query .= " primary key (FLAG_SEND)";
         $query .= " ) ENGINE=InnoDB CHARACTER SET=utf8;";
         SQLExec($query);

         $query = "insert into POST_MAIL(FLAG_SEND, LM_DATE, NOTIFY_EMAIL, NOTIFY_SUBJ)";
         $query .= " select * from TMP_POST_MAIL;";
         SQLExec($query);

         $query = " create table POST_TRACK(";
         $query .= " TRACK_ID             VARCHAR(14) not null,";
         $query .= " TRACK_NAME           VARCHAR(64) not null,";
         $query .= " FLAG_CHECK           VARCHAR(1) not null default 'Y',";
         $query .= " TRACK_DATE           DATETIME not null,";
         $query .= " LM_DATE              DATETIME not null,";
         $query .= " TRACK_URL            VARCHAR(255),";
         $query .= " primary key (TRACK_ID)";
         $query .= " ) ENGINE=InnoDB CHARACTER SET=utf8;";
         SQLExec($query);

         
         $isTrackUrlExists = RussianPost::isTableColumnExists("TMP_POST_TRACK", "TRACK_URL");
         if (!$isTrackUrlExists)
         {
            $query = "alter table TMP_POST_TRACK add column TRACK_URL varchar(255) after LM_DATE;";
            SQLExec($query);
         }
         
         $query = " insert into POST_TRACK(TRACK_ID, TRACK_NAME, FLAG_CHECK, TRACK_DATE, LM_DATE, TRACK_URL)";
         $query .= " select * from TMP_POST_TRACK;";
         SQLExec($query);

         $query = "create table POST_TRACKINFO(";
         $query .= " TRACK_ID             VARCHAR(14) not null,";
         $query .= " OPER_DATE            DATETIME not null,";
         $query .= " OPER_TYPE            INT(10) not null,";
         $query .= " OPER_NAME            VARCHAR(64) not null,";
         $query .= " ATTRIB_ID            INT(10),";
         $query .= " ATTRIB_NAME          VARCHAR(64),";
         $query .= " OPER_POSTCODE        INT(10),";
         $query .= " OPER_POSTPLACE       VARCHAR(64) not null,";
         $query .= " ITEM_WEIGHT          DECIMAL(10,6),";
         $query .= " DECLARED_VALUE       DECIMAL(10,6),";
         $query .= " DELIVERY_PRICE       DECIMAL(10,6),";
         $query .= " DESTINATION_POSTCODE INT(10),";
         $query .= " DELIVERY_ADDRESS     VARCHAR(255),";
         $query .= " LM_DATE              DATETIME not null,";
         $query .= " primary key (TRACK_ID, OPER_DATE)";
         $query .= " ) ENGINE=InnoDB CHARACTER SET=utf8;";
         SQLExec($query);
         
         $query = "insert into POST_TRACKINFO(TRACK_ID, OPER_DATE, OPER_TYPE, OPER_NAME, ATTRIB_ID, ATTRIB_NAME, OPER_POSTCODE, OPER_POSTPLACE, ITEM_WEIGHT, DECLARED_VALUE, DELIVERY_PRICE, DESTINATION_POSTCODE, DELIVERY_ADDRESS, LM_DATE)";
         $query .= " select * from TMP_POST_TRACKINFO;";
         SQLExec($query);
         
         SQLExec("drop table if exists TMP_POST_PROXY");
         SQLExec("drop table if exists TMP_POST_MAIL");
         SQLExec("drop table if exists TMP_POST_TRACK");
         SQLExec("drop table if exists TMP_POST_TRACKINFO");
      }
      
      parent::install(); 
   
   }
   
   /**
    * Uninstall
    * Module uninstall routine
    * @access public
    */
   function uninstall() 
   {
      SQLExec("drop table if exists POST_PROXY");
      SQLExec("drop table if exists POST_MAIL");
      SQLExec("drop table if exists POST_TRACKINFO");
      SQLExec("drop table if exists POST_TRACK");
   }
   
   /**
    * dbInstall
    * Database installation routine
    * @access private
    */
   function dbInstall($data) 
   {
     
   }
   
   function GetLastCheckedTracks()
   {
      $trackArray = array();
      $trackNum   = 1;
      
      $tracks  = RussianPost::SelectLastCheckedTracks();
     
      foreach ($tracks as $track)
      {
         $trackID = $track['TRACK_ID'];
         $arr = array();
         $arr['TRACK_NUM']       = $trackNum;
         $arr['TRACK_ID']        = $trackID;
         $arr['TRACK_NAME']      = $track['TRACK_NAME'];
         $arr['FLAG_CHECK']      = $track['FLAG_CHECK'];
         $arr['TRACK_DATE']      = $track['TRACK_DATE'];
         $arr['TRACK_URL']       = urldecode($track['TRACK_URL']);
         $arr['OPER_DATE']       = $track['OPER_DATE']; 
         $arr['OPER_NAME']       = $track['OPER_NAME']; 
         $arr['ATTRIB_NAME']     = $track['ATTRIB_NAME'];
         $arr['OPER_POSTPLACE']  = $track['OPER_POSTPLACE'];
      
         $trackArray[] = $arr;
         $trackNum++;
      }
      
      return $trackArray;
   }
   
   
   /**
    * Check tracks on russan post
    * @return operation message
    */
   function CheckPostTrack()
   {
      // start logger
      $log = Logger::getLogger(__METHOD__);
      
      try
      {
         // returned message
         $resultMessage = "";
         // get tracks
         $tracks = RussianPost::SelectTrackByFlag(RussianPost::FLAG_ACTIVE_TRACK);
        
         // if tracks not found then quit
         if(count($tracks) == 0)
            throw new Exception("Track numbers not found!");

         $postSettings = RussianPost::SelectNotifySettings();
         $postAccName = $postSettings["POCHTA_LOGIN"];
         $postAccPassword = $postSettings["POCHTA_PASSWORD"];

         if (isEmpty($postAccName) || isEmpty($postAccPassword))
            throw new Exception("Pochta.ru account not found");
         
         // init the client with or without proxy
         // Check proxy settings
         $proxySettings = RussianPost::SelectProxySettings();
         if (isset($proxySettings))
         {
            if ($proxySettings["FLAG_PROXY"] == "Y")
            {
               $client = new RussianPostAPI($proxySettings["PROXY_HOST"], $proxySettings["PROXY_PORT"], $proxySettings["PROXY_USER"], $proxySettings["PROXY_PASSWD"], $postAccName, $postAccPassword, "RUS");
            }
            else
            {
               $client = new RussianPostAPI("", "", "", "", $postAccName, $postAccPassword, "RUS");
            }
         }
         else
         {
            $client = new RussianPostAPI("", "", "", "", $postAccName, $postAccPassword, "RUS");
         }
         $timeSeparator  = 'T';   //$separator1
         $timeSeparator2 = '.';   //$separator2
         
         // check post tracks
         foreach ($tracks as $track)
         {
            // track id
            $trackID = $track['TRACK_ID'];
            // get track info from russian post
            
            try
            {
               $trackInfo = $client->getOperationHistory($trackID);
               // skip track if no info
               $cnt = count($trackInfo);
               if($cnt == 0) continue;
              
               foreach ($trackInfo as $track)
               {
                  $operationDate             = '';  // Operation Date
                  $operationTypeID           = '';  // Operation Type ID
                  $operationTypeName         = '';  // Operation Type Name
                  $operationAttributeID      = '';  // Operation Attribure ID
                  $operationAttribute        = '';  // Operation Attribte
                  $operationPlacePostalCode  = '';  // Operation Place Postal Code
                  $operationPlaceName        = '';  // Operation Place Name
                  $itemWeight                = '';  // Item Weight
                  $declaredValue             = '';  // Declared Item Value
                  $collectOnDeliveryPrice    = '';  // Collect On Delivery Price
                  $destinationPostalCode     = '';  // Destination Postal Code
                  $destinationAddress        = '';  // Destination Address
                  
                  foreach ($track as $key => $value)
                  {
                     if($key == 'operationType')            { $operationTypeName          = $value; }
                     if($key == 'operationTypeId')          { $operationTypeID            = $value; }
                     if($key == 'operationAttribute')       { $operationAttribute         = $value; }
                     if($key == 'operationAttributeId')     { $operationAttributeID       = $value; }
                     if($key == 'operationPlacePostalCode') { $operationPlacePostalCode   = $value; }
                     if($key == 'operationPlaceName')       { $operationPlaceName         = $value; }
                     if($key == 'operationDate')            { $operationDate              = $value; }
                     if($key == 'itemWeight')               { $itemWeight                 = $value; }
                     if($key == 'declaredValue')            { $declaredValue              = $value; }
                     if($key == 'collectOnDeliveryPrice')   { $collectOnDeliveryPrice     = $value; }
                     if($key == 'destinationPostalCode')    { $destinationPostalCode      = $value; }
                     if($key == 'destinationAddress')       { $destinationAddress         = $value; }
                  }
                  
                  if ($operationTypeID == 2)                   // change track status to inactive then post is arrived to your postoffice;
                     RussianPost::UpdateTrackStatus($trackID);
                  
                  $trackExist = RussianPost::isTrackInfoExist($trackID,$operationDate);
                  
                  if (!$trackExist)
                  {
                     $res = RussianPost::AddTrackDetail
                           ($trackID, $operationDate,$operationTypeID,$operationTypeName,$operationAttributeID, $operationAttribute,
                            $operationPlacePostalCode,$operationPlaceName,$itemWeight,$declaredValue,$collectOnDeliveryPrice,$destinationPostalCode,$destinationAddress);
                  }
               }
            }
            catch(Exception $e)
            {
               // TODO: сделать обработчик исключений для каждого трека.
            }
         }
         
         return true;
      }
      catch(RussianPostException $e)
      {
         $log->error("Error: " . $e->getMessage());
         return false;
      }
      
      //return $resultMessage;
   }
   
   /**
    * app_quotes edit/add
    *
    * @access public
    */
   function trackhistory_app_postoffice(&$out, $id)
   {
      $resultMessage = "";
      try
      {
         // Get TrackNumber form request
         $trackID = $id; 
         // Exit then TrackNumber does't exist.
         if ($trackID == null)
            throw new Exception("TrackNumber not found.");
         
         $res = RussianPost::SelectTrackHistoryByID($trackID);
         $out['TRACK_HISTORY']  = $res;
         $out['TRACK_URL'] = empty($res[0]['TRACK_URL']) ? '' : urldecode($res[0]['TRACK_URL']);
         $out['TRACK_NAME'] = $res[0]['TRACK_NAME'];
         $out['TRACK_ID'] = $id;
      }
      catch(Exception $e)
      {
         $resultMessage = "Oops! We have error: " . $e->getMessage();
      }
   }
   
   /**
    * Return current notify settings
    * @return array
    */
   function SelectNotifySettings()
   {
      return RussianPost::SelectNotifySettings();
   }
   
   /**
    * Select deliveried tracks
    * @return array
    */
   function SelectHistoryTracks()
   {
      $trackArray = array();
      $trackNum   = 1;
      
      $tracks  = RussianPost::SelectHistoryTracks();
      
      foreach ($tracks as $track)
      {
         $trackID = $track['TRACK_ID'];
         $arr = array();
         $arr['TRACK_NUM']       = $trackNum;
         $arr['TRACK_ID']        = $trackID;
         $arr['TRACK_NAME']      = $track['TRACK_NAME'];
         $arr['FLAG_CHECK']      = $track['FLAG_CHECK'];
         $arr['TRACK_DATE']      = $track['TRACK_DATE'];
         $arr['TRACK_URL']       = urldecode($track['TRACK_URL']);
         $arr['OPER_DATE']       = $track['OPER_DATE']; 
         $arr['OPER_NAME']       = $track['OPER_NAME']; 
         $arr['ATTRIB_NAME']     = $track['ATTRIB_NAME'];
         $arr['OPER_POSTPLACE']  = $track['OPER_POSTPLACE'];
         
         $trackArray[] = $arr;
         $trackNum++;
      }
      
      return $trackArray;
   }
}
?>