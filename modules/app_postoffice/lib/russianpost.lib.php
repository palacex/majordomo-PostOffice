<?php
/**
 * Russian Post tracking API PHP library
 * @author InJapan Corp. <max@injapan.ru>
 * @author LDV <palacex@gmail.com>
 *
 ************************************************************************
 * You MUST request usage access for this API through request account   *
 * at https://tracking.pochta.ru/                                       *
 ************************************************************************
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

$russianpostRequiredExtensions = array('SimpleXML', 'curl', 'pcre');

foreach($russianpostRequiredExtensions as $russianpostExt)
{
   if (!extension_loaded($russianpostExt))
   {
      throw new RussianPostSystemException('Required extension ' . $russianpostExt . ' is missing');
   }
}

class RussianPostAPI
{
   protected $proxyHost;
   protected $proxyPort;
   protected $proxyAuthUser;
   protected $proxyAuthPassword;
   protected $accName;
   protected $accPassword;
   protected $client;

   /**
    * Constructor. Pass proxy config here.
    * @param string $proxyHost
    * @param string $proxyPort
    * @param string $proxyAuthUser
    * @param string $proxyAuthPassword
    * @param string $accName
    * @param string $accPassword
    * @param string $lang default RUS
    */
   public function __construct($proxyHost = "", $proxyPort = "", $proxyAuthUser = "", $proxyAuthPassword = "", $accName = "", $accPassword = "", $lang = "RUS")
   {
      $this->proxyHost         = $proxyHost;
      $this->proxyPort         = $proxyPort;
      $this->proxyAuthUser     = $proxyAuthUser;
      $this->proxyAuthPassword = $proxyAuthPassword;
      $this->accName           = $accName;
      $this->accPassword       = $accPassword;
      $this->lang              = $lang;

      $options = array();

      if (!empty($this->proxyHost) && !empty($this->proxyPort) && !empty($this->proxyAuthUser) && !empty($this->proxyAuthPassword))
      {
         $options = array('proxy_host'     => $this->proxyHost,
                          'proxy_port'     => $this->proxyPort,
                          'proxy_login'    =>  $this->proxyAuthUser,
                          'proxy_password' => $this->proxyAuthPassword,
                          'trace'          => 1,
                          'soap_version'   => SOAP_1_2);

      }
      else
      {
         $options = array('trace'          => 1,
                          'soap_version'   => SOAP_1_2);
      }

      $this->client = new SoapClient("https://tracking.russianpost.ru/rtm34?wsdl",  $options);
   }

   /**
    * Returns tracking data
    * @param string $trackingNumber tracking number
    * @return array of RussianPostTrackingRecord
    */
   public function getOperationHistory($trackingNumber)
   {
      $trackingNumber = trim($trackingNumber);

      if (!preg_match('/^[0-9]{14}|[A-Z]{2}[0-9]{9}[A-Z]{2}$/', $trackingNumber))
         throw new RussianPostArgumentException('Incorrect format of tracking number: ' . $trackingNumber);

      $response = $this->makeRequest($trackingNumber);

      $data = $this->parseResponse($response);

      return $data;
   }

   protected function parseResponse($data)
   {
      if (!is_object($data))
         throw new RussianPostDataException("Failed to parse XML response");

      $records = $data->children('S', true)->Body->children('ns7', true)->getOperationHistoryResponse->children('ns3', true)->OperationHistoryData->historyRecord;
      if (!($records))
         throw new RussianPostDataException("There is no tracking data in XML response");

      $out = array();
      foreach($records as $rec)
      {
         $outRecord = new RussianPostTrackingRecord();
         $outRecord->operationType            = (string)$rec->OperationParameters->OperType->Name;
         $outRecord->operationTypeId          = (int) $rec->OperationParameters->OperType->Id;

         $outRecord->operationAttribute       = (string) $rec->OperationParameters->OperAttr->Name;
         $outRecord->operationAttributeId     = (int) $rec->OperationParameters->OperAttr->Id;

         $outRecord->operationPlacePostalCode = (string) $rec->AddressParameters->OperationAddress->Index;
         $outRecord->operationPlaceName       = (string) $rec->AddressParameters->OperationAddress->Description;

         $outRecord->destinationPostalCode    = (string) $rec->AddressParameters->DestinationAddress->Index;
         $outRecord->destinationAddress       = (string) $rec->AddressParameters->DestinationAddress->Description;

         $outRecord->operationDate            = (string) $rec->OperationParameters->OperDate;

         $outRecord->itemWeight               = round(floatval($rec->ItemParameters->Mass) / 1000, 3);
         $outRecord->declaredValue            = round(floatval($rec->FinanceParameters->Value) / 100, 2);
         $outRecord->collectOnDeliveryPrice   = round(floatval($rec->FinanceParameters->Payment) / 100, 2);

         $out[] = $outRecord;
      }
      return $out;
   }

   protected function makeRequest($trackingNumber)
   {
      $response = $this->client->__doRequest(
         '<?xml version="1.0" encoding="UTF-8"?>
                <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:oper="http://russianpost.org/operationhistory" xmlns:data="http://russianpost.org/operationhistory/data" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
                <soap:Header/>
                <soap:Body>
                   <oper:getOperationHistory>
                      <data:OperationHistoryRequest>
                         <data:Barcode>'. $trackingNumber . '</data:Barcode>
                         <data:MessageType>0</data:MessageType>
                         <data:Language>' . $this->lang . '</data:Language>
                      </data:OperationHistoryRequest>
                      <data:AuthorizationHeader soapenv:mustUnderstand="1">
                         <data:login>'. $this->accName . '</data:login>
                         <data:password>' . $this->accPassword . '</data:password>
                      </data:AuthorizationHeader>
                   </oper:getOperationHistory>
                </soap:Body>
             </soap:Envelope>',
          "https://tracking.russianpost.ru/rtm34",
          "getOperationHistory",
          SOAP_1_2
      );

      $xml = simplexml_load_string($response);

      $error =  $xml->children('S', true)->Body->Fault;
      if($error)
      {
         $error_title = $error->Reason->Text;

         $error_text = false;
         $error = $error->Detail->children('ns3', true);
         $error_text = $error->OperationHistoryFaultReason ? $error->OperationHistoryFaultReason : $error_text;
         $error_text = $error->AuthorizationFaultReason ? $error->AuthorizationFaultReason : $error_text;
         $error_text = $error->LanguageFaultReason ? $error->LanguageFaultReason : $error_text;

         $error_text = $error_text ? $error_text : $response;
         $error_title = $error_title ? $error_title : "Unknown error";

         throw new RussianPostException($error_title.": ".$error_text);
      }

      return $xml;
   }
}

/**
 * One record in tracking history
 */
class RussianPostTrackingRecord {
   /**
    * Operation type, e.g. Импорт, Экспорт and so on
    * @var string
    */
   public $operationType;

   /**
    * Operation type ID
    * @var int
    */
   public $operationTypeId;

   /**
    * Operation attribute, e.g. Выпущено таможней
    * @var string
    */
   public $operationAttribute;

   /**
    * Operation attribute ID
    * @var int
    */
   public $operationAttributeId;

   /**
    * ZIP code of the postal office where operation took place
    * @var string
    */
   public $operationPlacePostalCode;

   /**
    * Name of the postal office where operation took place
    * @var [type]
    */
   public $operationPlaceName;

   /**
    * Operation date in ISO 8601 format
    * @var string
    */
   public $operationDate;

   /**
    * Item wight (kg)
    * @var float
    */
   public $itemWeight;

   /**
    * Declared value of the item in rubles
    * @var float
    */
   public $declaredValue;

   /**
    * COD price of the item in rubles
    * @var float
    */
   public $collectOnDeliveryPrice;

   /**
    * Postal code of the place item addressed to
    * @var string
    */
   public $destinationPostalCode;

   /**
    * Destination address of the place item addressed to
    * @var string
    */
   public $destinationAddress;
}

class RussianPostException         extends Exception { }
class RussianPostArgumentException extends RussianPostException { }
class RussianPostSystemException   extends RussianPostException { }
class RussianPostChannelException  extends RussianPostException { }
class RussianPostDataException     extends RussianPostException { }
