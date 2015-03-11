<?php
/**
 * Default language file for PostOffice application
 *
 * @package PostOffice
 * @author Lutsenko D.V. <palacex@gmail.com> http://silvergate.ru/
 * @version 1.0
 */

$dictionary = array(
'POSTOFFICE_APP_TITLE'=>'Почта России - Отслеживание посылок',
'POSTOFFICE_TAB_CHECK'=>'Проверка почты',
'POSTOFFICE_TAB_PROXY' => 'Настройка прокси',
'POSTOFFICE_TAB_NOTIFICATION' => 'Настройка уведомлений',
'POSTOFFICE_TRACK_INDEX' => '№',
'POSTOFFICE_TRACK_NUMBER' => 'Номер трека',
'POSTOFFICE_TRACK_NAME' => 'Название трека',
'POSTOFFICE_TRACK_URL' => 'Ссылка',
'POSTOFFICE_TRACK_ADD' => 'Добавить',
'POSTOFFICE_TRACK_CHECK' => 'Проверить',
'POSTOFFICE_TRACK_DATE' => 'Дата',
'POSTOFFICE_TRACK_LM_DATE' => 'Дата обновления',
'POSTOFFICE_TRACK_CONDITION' => 'Состояние',
'POSTOFFICE_TRACK_LOCATION' => 'Текущее местонахождение',
'POSTOFFICE_TRACK_STATUS' => 'Статус',
'POSTOFFICE_PROXY_USE' => 'Использовать прокси',
'POSTOFFICE_PROXY_ADDRESS' => 'Адрес прокси',
'POSTOFFICE_PROXY_PORT' => 'Порт',
'POSTOFFICE_PROXY_LOGIN' => 'Логин',
'POSTOFFICE_PROXY_PASSWORD' => 'Пароль',
'POSTOFFICE_ACTION_CHANGE' => 'Изменить',
'POSTOFFICE_ACTION_CLOSE' => 'Закрыть',
'POSTOFFICE_ACTION_CANCEL' => 'Отменить',
'POSTOFFICE_ACTION_DELETE' => 'Удалить',
'POSTOFFICE_NOTIFY_BY_EMAIL' => 'Оповещать о доставке посылки по email',
'POSTOFFICE_NOTIFY_EMAIL_ADDRESS' => 'Адрес для отправки уведомления',
'POSTOFFICE_NOTIFY_EMAIL_SUBJECT' => 'Тема сообщения',
'POSTOFFICE_CONFIRM_DELETE_TITLE' => 'Подтверждение удаления трек номера',
'POSTOFFICE_CONFIRM_DELETE_BODY' =>  'Вы уверены в том, что хотите удалить данный трек и всю информацию о нём?',
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