<?php
/**
 * Default language file for PostOffice application
 *
 * @package PostOffice
 * @author Lutsenko D.V. <palacex@gmail.com> http://silvergate.ru/
 * @version 1.0
 */

$dictionary = array(
'POSTOFFICE_APP_TITLE'=>'Russian Post - Post tracking',
'POSTOFFICE_TAB_CHECK'=>'Check parcels',
'POSTOFFICE_TAB_PROXY' => 'Proxy',
'POSTOFFICE_TAB_NOTIFICATION' => 'Notification',
'POSTOFFICE_TRACK_INDEX' => '#',
'POSTOFFICE_TRACK_NUMBER' => 'Track number',
'POSTOFFICE_TRACK_NAME' => 'Track name',
'POSTOFFICE_TRACK_URL' => 'Link',
'POSTOFFICE_TRACK_ADD' => 'Add',
'POSTOFFICE_TRACK_CHECK' => 'Check',
'POSTOFFICE_TRACK_DATE' => 'Date',
'POSTOFFICE_TRACK_LM_DATE' => 'Operation Date',
'POSTOFFICE_TRACK_CONDITION' => 'Condition',
'POSTOFFICE_TRACK_LOCATION' => 'Current location',
'POSTOFFICE_TRACK_STATUS' => 'Status',
'POSTOFFICE_PROXY_USE' => 'Use proxy',
'POSTOFFICE_PROXY_ADDRESS' => 'Proxy address',
'POSTOFFICE_PROXY_PORT' => 'Port',
'POSTOFFICE_PROXY_LOGIN' => 'Login',
'POSTOFFICE_PROXY_PASSWORD' => 'Password',
'POSTOFFICE_ACTION_CHANGE' => 'Change',
'POSTOFFICE_ACTION_CLOSE' => 'Close',
'POSTOFFICE_ACTION_CANCEL' => 'Cancel',
'POSTOFFICE_ACTION_DELETE' => 'Delete',
'POSTOFFICE_NOTIFY_BY_EMAIL' => 'Notify sending parcels via email',
'POSTOFFICE_NOTIFY_EMAIL_ADDRESS' => 'Notify address',
'POSTOFFICE_NOTIFY_EMAIL_SUBJECT' => 'Subject',
'POSTOFFICE_CONFIRM_DELETE_TITLE' => 'Removal Confirmation track numbers',
'POSTOFFICE_CONFIRM_DELETE_BODY' =>  'Are you sure you want to delete this track and all the information about it?',
'POSTOFFICE_TRACK_ADD_FORM_TITLE' => 'Дабавление трек номера',
);


foreach ($dictionary as $k=>$v)
{
   if (!defined('LANG_' . $k))
   {
      define('LANG_'. $k, $v);
   }
}
?>