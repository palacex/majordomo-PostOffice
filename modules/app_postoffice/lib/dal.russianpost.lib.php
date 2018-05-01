<?php
namespace DAL
{
   /**
    * Post Office Data Access Layer
    *
    * @version 0.3
    * @author Lutsenko D.V.
    */
   class RussianPostDAL
   {
      const FLAG_ACTIVE_TRACK   = "Y";   // Отслеживать трек-номер
      const FLAG_INACTIVE_TRACK = "N";   // Не отслеживать трек-номер
      
      /**
       * Возвращает список треков
       * @return array
       */
      public static function SelectTrack()
      {
         $track = array();
         
         $query = "select TRACK_ID, TRACK_NAME, FLAG_CHECK, TRACK_DATE, LM_DATE, TRACK_URL
                     from POST_TRACK
                 order by LM_DATE desc;";
         
         $track = SQLSelect($query);
         
         return $track;
      }
      
      /**
       * Return tracks ordered by ckeck date on russian post
       * @return array
       */
      public static function SelectLastCheckedTracks()
      {
         $track = array();
         $query = "select *
                     from POST_TRACK
                     left  join (select a.TRACK_ID, a.TRACK_NAME,  a.FLAG_CHECK,  a.TRACK_DATE, a.LM_TRACK, a.TRACK_URL,
                                        a.OPER_DATE, a.ATTRIB_NAME, a.OPER_POSTPLACE, a.OPER_NAME, a.LM_INFO
                                   from (select t.TRACK_ID, t.TRACK_NAME,  t.FLAG_CHECK,  t.TRACK_DATE, t.LM_DATE LM_TRACK, t.TRACK_URL,
                                                i.OPER_DATE, i.ATTRIB_NAME, i.OPER_POSTPLACE, i.OPER_NAME, i.LM_DATE LM_INFO
                                           from POST_TRACKINFO i , POST_TRACK t
                                          where t.TRACK_ID = i.TRACK_ID
                                            and OPER_DATE = (select max(OPER_DATE) 
                                                               from POST_TRACKINFO 
                                                              where TRACK_ID = t.TRACK_ID
                                                            )
                                        ) a
                                ) as pinfo 
                          using (TRACK_ID, TRACK_NAME, FLAG_CHECK, TRACK_DATE, TRACK_URL)
                    where (OPER_NAME is null or OPER_NAME != 'Вручение')
                       or (OPER_NAME = 'Вручение' and OPER_DATE >= DATE_SUB(NOW(),INTERVAL 7 day))
                 order by FLAG_CHECK desc, OPER_DATE desc, LM_DATE desc";
         
         $track = SQLSelect($query);
         
         return $track;
      }
      
      /**
       * Return track numbers array with flag
       * @param $checkFlag char CheckFlag
       * @return array
       */
      public static function SelectTrackByFlag($checkFlag)
      {
         $query = "select TRACK_ID, TRACK_NAME, FLAG_CHECK, TRACK_DATE, TRACK_URL
                     from POST_TRACK
                    where FLAG_CHECK = '" . $checkFlag . "'
                 order by TRACK_DATE desc;";
         
         $track = SQLSelect($query);
         
         return $track;
      }
      
      /**
       * Return track status(active/inactive) by track number
       * @param $trackID string TrackNumber
       * @return Y/N check status
       */
      public static function GetTrackStatusByID($trackID)
      {
         $query = "select FLAG_CHECK
                     from POST_TRACK
                    where TRACK_ID = '" . $trackID . "'";
         
         $result = SQLSelectOne($query);
         
         $trackCheckFlag = $result['FLAG_CHECK'];
         
         return $trackCheckFlag;
      }
      
      /**
       * Update track check status
       * @param $trackID string TrackNumber
       * @return true/false
       */
      public static function UpdateTrackStatus($trackID)
      {
         if ($trackID == null) return false;                                    // track number not found
         
         $trackStatus = RussianPostDAL::GetTrackStatusByID($trackID);
         if (!isset($trackStatus) || count($trackStatus) == 0) return false;    // track status is undefined
         
         $trackStatus = $trackStatus == "Y" ? "N" : "Y";                        // new track status
         $RequestDate =  date('Y-m-d H:i:s');                                   // date was track statud was udated
         $rec = array();
         $rec["FLAG_CHECK"] = $trackStatus;
         $rec["LM_DATE"]    = $RequestDate;
         $rec["TRACK_ID"]   = $trackID;
         
         $result = SQLUpdate("POST_TRACK",$rec,"TRACK_ID");
         
         return $result == 1;
      }
      
      /**
       * Delete track number
       * @param $trackID string TrackNumber
       * @return true/false
       */
      public static function DeleteTrack($trackID)
      {
         $query = "delete  
                     from POST_TRACK
                    where TRACK_ID = '" . $trackID . "';";
         $result = SQLExec($query);
         return $result;
      }
      
      /**
       * Delete all track info by track number
       * @param $trackID Track number
       * @return
       */
      public static function DeleteTrackDetailByID($trackID)
      {
         $query = "delete  
                     from POST_TRACKINFO
                    where TRACK_ID = '" . $trackID . "';";
         $result = SQLExec($query);
         return $result;
      }
      
      /**
       * Add track to database
       * @param $trackID string TrackNumber
       * @param $trackName string TrackName
       * @param $trackInfoUrl string Информация о посылке
       * @return
       */
      public static function AddTrack($trackID, $trackName, $trackInfoUrl)
      {
         if ($trackID       == null) return false;                    // трек номер не указан
         if ($trackName     == null) return false;                    // название трека не указано
         
         $RequestDate =  date('Y-m-d H:i:s');
         
         $rec = array();
         $rec["TRACK_ID"]   = $trackID;
         $rec["TRACK_NAME"] = $trackName;
         $rec["FLAG_CHECK"] = "Y";
         $rec["TRACK_DATE"] = $RequestDate;
         $rec["LM_DATE"]    = $RequestDate;
         $rec["TRACK_URL"]  = urlencode($trackInfoUrl);
         
         $res = SQLInsert("POST_TRACK", $rec);
         
         return $res;
      }
      
      /**
       * Add track info from russian post to database
       * @param $trackID                     TrackNumber
       * @param $operationDate               OperationDate
       * @param $operationTypeID             OperationTypeID
       * @param $operationTypeName           OperationTypeName
       * @param $operationAttributeID        OperationAttribureID
       * @param $operationAttribute          OperationAttribte
       * @param $operationPlacePostalCode    OperationPlacePostalCode
       * @param $operationPlaceName          OperationPlaceName
       * @param $itemWeight                  ItemWeight
       * @param $declaredValue               DeclaredItemValue
       * @param $collectOnDeliveryPrice      CollectOnDeliveryPrice
       * @param $destinationPostalCode       DestinationPostalCode
       * @param $destinationAddress          DestinationAddress
       * @return                             Result(true/false)
       */
      public static function AddTrackDetail($trackID, $operationDate, $operationTypeID, $operationTypeName, $operationAttributeID, $operationAttribute, $operationPlacePostalCode, $operationPlaceName, $itemWeight, 
         $declaredValue, $collectOnDeliveryPrice, $destinationPostalCode, $destinationAddress)
      {
         if (!isset($trackID))            return false;                    // track number not exist
         if (!isset($operationDate))      return false;                    // operation date not exist
         if (is_null($operationTypeID))   return false;                    // operation type id not exist
         if (!isset($operationTypeName))  return false;                    // operation type name not exist
         if (!isset($operationPlaceName)) return false;                    // operation place name not exist
         
         $rec = array();
         $RequestDate =  date('Y-m-d H:i:s');
         
         $rec["TRACK_ID"]             = $trackID;
         $rec["OPER_DATE"]            = $operationDate;
         $rec["OPER_TYPE"]            = $operationTypeID;
         $rec["OPER_NAME"]            = $operationTypeName;
         $rec["ATTRIB_ID"]            = $operationAttributeID;
         $rec["ATTRIB_NAME"]          = $operationAttribute;
         $rec["OPER_POSTCODE"]        = $operationPlacePostalCode;
         $rec["OPER_POSTPLACE"]       = $operationPlaceName;
         $rec["ITEM_WEIGHT"]          = $itemWeight;
         $rec["DECLARED_VALUE"]       = $declaredValue;
         $rec["DELIVERY_PRICE"]       = $collectOnDeliveryPrice;
         $rec["DESTINATION_POSTCODE"] = $destinationPostalCode;
         $rec["DELIVERY_ADDRESS"]     = $destinationAddress;
         $rec["LM_DATE"]              = $RequestDate;
         
         $res = SQLInsert("POST_TRACKINFO", $rec);
         
         if($res)
         {  
            $rec = array();
            $rec["LM_DATE"]              = $RequestDate;
            
            $res = SQLUpdate("POST_TRACK", $rec, "TRACK_ID");
         }
         
         return $res;
      }
      
      /**
       * Select short detail about last track position
       * @param string $trackID TrackNumber
       * @return array
       */
      public static function SelectTrackLastInfoByID($trackID)
      {
         $query = "select OPER_DATE, ATTRIB_NAME, OPER_POSTPLACE, OPER_NAME
                     from POST_TRACKINFO
                    where TRACK_ID = '". $trackID . "'
                      and OPER_DATE = (select max(OPER_DATE) 
                                         from POST_TRACKINFO 
                                        where TRACK_ID = '". $trackID . "');";
         
         $track = SQLSelect($query);
         
         return $track;
      }
      
      /**
       * Return true then trackinfo extist on current date
       * @param string $trackID TrackNumber
       * @param string $operDate  OperationDate
       * @return boolean
       */
      public static function isTrackInfoExist($trackID, $operDate)
      {
         $query = "select count(*) CNT
                           from POST_TRACKINFO 
                          where TRACK_ID  = '" . $trackID  . "'
                            and OPER_DATE = '" . $operDate . "'";
         $result = SQLSelectOne($query);
         
         $infoCnt = $result['CNT'];
         
         return $infoCnt > 0;
      }
      
      /**
       * Return delivery info by track id
       * @param string $trackID Track number
       * @return array
       */
      public static function SelectTrackHistoryByID($trackID)
      {
         $query = "select t.TRACK_ID, t.TRACK_NAME, t.TRACK_URL, 
                          i.OPER_DATE, i.OPER_TYPE, i.OPER_NAME, i.ATTRIB_ID, i.ATTRIB_NAME, i.OPER_POSTCODE, i.OPER_POSTPLACE, 
                          i.ITEM_WEIGHT, i.DECLARED_VALUE, i.DELIVERY_PRICE, i.DESTINATION_POSTCODE, i.DELIVERY_ADDRESS, i.LM_DATE
                     from POST_TRACKINFO i, POST_TRACK t
                    where i.TRACK_ID = t.TRACK_ID
                      and t.TRACK_ID = '". $trackID . "';";
         
         $track = SQLSelect($query);
         
         return $track;
      }
      
      /**
       * Return true if we use proxy
       * @return true/false
       */
      public static function isProxy()
      {
         $flagUseProxy = "Y";
         
         $query = "select count(*) CNT
                           from POST_PROXY 
                          where FLAG_PROXY = '" . $flagUseProxy . "'";
         $result = SQLSelectOne($query);
         
         $proxyCount = $result['CNT'];
         
         return $proxyCount > 0;
      }
      
      /**
       * Return current proxy settings
       * @return array
       */
      public static function SelectProxySettings()
      {
         $query = "select FLAG_PROXY, PROXY_HOST, PROXY_PORT, PROXY_USER, PROXY_PASSWD
                     from POST_PROXY;";
         
         $proxy = SQLSelect($query);
         $proxy= $proxy[0];
         return $proxy;
      }
      
      /**
       * Set proxy settings for check post
       * @param string $proxyFlag Flag
       * @param string $proxyHost Host
       * @param string $proxyPort Port
       * @param string $proxyUser User
       * @param string $proxyPassword Password
       * @return boolean
       */
      public static function SetProxySettings($proxyFlag, $proxyHost, $proxyPort, $proxyUser, $proxyPassword, $currFlag)
      {
         $RequestDate =  date('Y-m-d H:i:s');
         
         $proxyHost     = DbSafe($proxyHost);
         $proxyPort     = DbSafe($proxyPort);
         $proxyUser     = DbSafe($proxyUser);
         $proxyPassword = DbSafe($proxyPassword);
         
         if ($currFlag == null)
         {
            $query = "insert into POST_PROXY(FLAG_PROXY, PROXY_HOST, PROXY_PORT, PROXY_USER, PROXY_PASSWD, LM_DATE)
                      values('" . $proxyFlag . "', '" . $proxyHost . "', '" . $proxyPort . "', '" . $proxyUser . "', '" . $proxyPassword . "', '" . $RequestDate . "');";

         }
         else
         {
            $query = "update POST_PROXY
                         set FLAG_PROXY   = '" . $proxyFlag . "',
                             PROXY_HOST   = '" . $proxyHost . "',
                             PROXY_PORT   = '" . $proxyPort . "',
                             PROXY_USER   = '" . $proxyUser . "',
                             PROXY_PASSWD = '" . $proxyPassword . "',
                             LM_DATE      = '" . $RequestDate . "'
                       where FLAG_PROXY   = '" . $currFlag . "'";
         }
         
         $res = SQLExec($query);
         
         return $res;
      }
      
      /**
       * Check table exist or not
       * @param $tableName TableName
       * @return boolean
       */
      public static function isDbTableExists($tableName)
      {
         $sql = "desc " + $tableName;
         
         return SQLExec($sql);
      }
      
      /**
       * Check table for column exist
       * @param $tableName string Table Name
       * @param $tableColumn string Table Column
       * @return bool
       */
      public static function isTableColumnExists($tableName, $tableColumn)
      {
         $query = "select count(*) CNT
                     from information_schema.COLUMNS 
                    where TABLE_SCHEMA = '" . DB_NAME . "' 
                      and TABLE_NAME   = '" . $tableName . "' 
                      and COLUMN_NAME  = '" . $tableColumn . "'";
         
         $result = SQLSelectOne($query);
         
         $columnCount = $result['CNT'];
         
         return $columnCount > 0;
      }
   
      
      /**
       * Get notify settings
       * @return array
       */
      public static function SelectNotifySettings()
      {
         $query = "select FLAG_SEND, NOTIFY_EMAIL, NOTIFY_SUBJ, ACC_NAME POCHTA_LOGIN, ACC_PASSWD POCHTA_PASSWORD
                     from POST_MAIL;";
         
         $notify = SQLSelect($query);
         $notify= $notify[0];
         return $notify;
      }
      
      /**
       * Summary of SetNotificationSettings
       * @param string $notifyFlag    Send email notification or not (Y/N)
       * @param string $currFlag      Current notification flag (Y/N,null)
       * @param string $notifyEmail   Email to send notify
       * @param string $notifySubject Email subject
       * @param string $notifySubject Pochta.ru API login
       * @param string $notifySubject Pochta.ru API password
       * @return int|resource
       */
      public static function SetNotificationSettings($notifyFlag, $currFlag, $notifyEmail, $notifySubject, $accName, $accPassword)
      {
         $RequestDate =  date('Y-m-d H:i:s');
        
         if ($currFlag == null)
         {
            $query = "insert into POST_MAIL(FLAG_SEND, LM_DATE)
                      values('" . $notifyFlag . "', '" . $RequestDate . "');";

         }
         else
         {
            $query = "update POST_MAIL
                         set FLAG_SEND    = '" . $notifyFlag    . "',
                             LM_DATE      = '" . $RequestDate   . "',
                             NOTIFY_EMAIL = '" . $notifyEmail   . "',
                             NOTIFY_SUBJ  = '" . $notifySubject . "',
                             ACC_NAME     = '" . $accName       . "',
                             ACC_PASSWD   = '" . $accPassword   . "'
                       where FLAG_SEND    = '" . $currFlag      . "'";
         }
         
         $res = SQLExec($query);
         
         return $res;
      }
      
      /**
       * Return tracks ordered by ckeck date on russian post
       * @return array
       */
      public static function SelectHistoryTracks()
      {
         $track = array();
         $query = "select t.TRACK_ID, t.TRACK_NAME,  t.FLAG_CHECK,  t.TRACK_DATE, t.TRACK_URL,
                          i.OPER_DATE, i.ATTRIB_NAME, i.OPER_POSTPLACE, i.OPER_NAME
                     from POST_TRACKINFO i , POST_TRACK t
                    where t.TRACK_ID  = i.TRACK_ID
                      and i.OPER_NAME = 'Вручение'";
         
         $track = SQLSelect($query);
         
         return $track;
      }
   }
}
?>