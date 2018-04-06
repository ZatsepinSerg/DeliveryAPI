<?php
/**
 * Created by PhpStorm.
 * User: Zatsepin Serg
 * Date: 14.02.18
 * Time: 13:25
 */

require_once "libphp/delivery/deliveryAPI/connectionAPI.php";
require_once "libphp/delivery/deliveryAPI/DeliveryAPI.php";
include_once 'libphp/delivery.obj.inc.php';
include_once 'libphp/settings.obj.inc.php';

class DeliveryAPI extends ConnectionAPI
{

    const REMEMBER_ME = TRUE;

    public $key;
    public $utilSettings;
    public $delivery;
    public $cahsParam;
    public $apiKay;
    public $secretKay;

    /**
     * DeliveryAPI constructor.
     */

    public function __construct()
    {

        $this->delivery = new NeadsDeliveryUtil();
        $this->utilSettings = new NeadsSettingsUtil();
        $this->utilSettings->Retrieve();

        $this->secretKay = $this->utilSettings->Settings['DELIVERY_SECRET_KAY'];
        $this->apiKay = $this->utilSettings->Settings['DELIVERY_API_KEY'];

        $data['UserName']= DeliveryAPI::USER_NAME;
        $data['Password']= DeliveryAPI::PASSWORD;
        $data['RememberMe']= DeliveryAPI::REMEMBER_ME;

        $url = "http://www.delivery-auto.com.ua/api/v4/Public/PostLogin";
        $this->connect($data,$url);

    }

    public function generateKey(){
        $time = time();
        $hash = hash_hmac("sha1", $this->apiKay.$time,$this->secretKay);

        $this->key['HMACAuthorization'] = 'amx ' .$this->apiKay. ':' .$time.':' .$hash;
    }

    public function generateDocument($info){

        /**
         * size = объём
         * helf = вес
         *
         *
         */
        
        $recipient = $info->np_recipient_second_name." ".$info->np_recipient_first_name." ".$info->np_recipient_middle_name;


        $imposed = $info->np_sender_second_name." ".$info->np_sender_first_name." ".$info->np_sender_middle_name;

        $recipient = json_encode($recipient);

        $imposed = json_encode($imposed);

        $description =json_encode($info->np_Description);

        $recipient_phone = substr($info->np_recipient_phone, -10);

        $cityName = $this->utilSettings->Settings['DELIVERY_SENDER_CITY'];
        $warehoseName = $this->utilSettings->Settings['DELIVERY_SENDER_WAREHOUSE'];

        $this->delivery->getKeyWarehose($cityName,$warehoseName);

        $sender_city =$this->delivery->Items[0]->Ref;
        $sender_warehouse =$this->delivery->Items[0]->CityRef;

        WriteLog('logs/.txt',print_r($info,TRUE));


        if($info->np_PaymentType == "Cash"){
            
            $cahsParam= '"cashOnDeliveryType":2,
         
         "CashOnDeliveryValuta": 100000000,
         
         "CashOnDeliveryValue": '.$info->np_Cost.',
         
         "CashOnDeliverySenderFullName": '.$imposed.',
         
         "CashOnDeliverySenderPhone": "'.substr($info->np_sender_phone, -10).'",

         "CashOnDeliveryWarehouseId": "'.$sender_warehouse.'",

        "CashOnDeliveryReceiverFullName":"'.$info->np_sender_second_name.' '.$info->np_sender_first_name.' '.$info->np_sender_middle_name.'",
        
        "CashOnDeliveryReceiverPhone": "'. $recipient_phone .'",';

        }


        $data = ' { 

   "culture":"ru-RU",

   "flSave":"true",

   "debugMode":false,

   "receiptsList":[ 

      { 
         "receiverType":false,

         "areasSendId":"'. $sender_city.'",

         "areasResiveId":"'. $info->np_recipient_city_ref .'",

         "warehouseSendId":"'. $sender_warehouse.'",

         "warehouseResiveId":"'. $info->np_recipient_warehouse_ref.'",

         "dateSend":"' . date("Y-m-d H:i:s") . '",

         "deliveryScheme":0,

         "receiverName":'.$recipient.',

         "receiverPhone":"'. $recipient_phone .'",

         "currency":100000000,

         "InsuranceValue":'.$info->np_Cost.'.0,

         "senderId":"'.$this->utilSettings->Settings['DELIVERY_API_KEY'].'",
    
         "paymentType":0,

         "payerType":1,
         
         "InsuranceCost": '.$info->np_Cost.'.0,
         
         "paymentTypeInsuranse":0,

         "deliveryAddress":"",
         
          "deliveryContactName":"\u0414\u043c\u0438\u0442\u0440\u0438\u0439",
         "deliveryContactPhone":"'. $recipient_phone .'",

         "DeliveryComment":'. $description .',

         "ReturnDocuments":false,
         '.$cahsParam.'
         
         "climbingToFloor":1,
         
         "EconomDelivery":false,

         "IsOverSize":false,

         "IsGidrobort":false,

         "EconomPickUp":false,

         "ExpressPickUp":false,

         "CustomsCost":'.$info->np_Cost.'.0,

         "CustomsCurrency":100000000,
         
         "deliveryContactPhone": "'. $recipient_phone .'",

         "CustomsDocuments":false,

         "CustomsDescriptions":"\u041e\u043f\u0438\u0430\u043d\u0438\u0435 \u0434\u043b\u044f \u0442\u0430\u043c\u043e\u0436\u043d\u0438",

         "category":[{ 
         
            "cargoCategoryId":"d780e8ee-c22b-e411-b56e-000d3a200936",

            "countPlace":'. $info->np_SeatsAmount .',

            "helf":'. $info->np_WeightCounted .',

            "size":'. $info->np_VolumeGeneral .',

            "isEconom":false,

            "PartnerNumber":"123456"
         }]
      }
   ]
}';

        WriteLog('logs/koi.txt',print_r($data,TRUE));

        return $data;
    }

    public function createTTH($orderInfo){
        $url = "http://www.delivery-auto.com.ua/api/v4/Public/PostCreateReceipts";
        $type ="POST";

        $this->generateKey();
        $header = $this->key;

        $data = $this->generateDocument($orderInfo);

        $response = $this->connect($data,$url,$type,$header);

        return $response;
    }

    public function printTTH($receiptId){
        $data ='';
        $url = "http://www.delivery-auto.com.ua/api/v4/Public/GetPdfDocument?number=".$receiptId."&type=0";
        $type ="GET";

        $this->generateKey();
        $header = $this->key;

        $response = $this->connect($data,$url,$type,$header);

        $file = json_decode($response);
        $t = base64_decode($file->file);

        header('Content-Description: File Transfer');
        header("Content-type:application/pdf");
        header("Content-Disposition:attachment;filename=downloaded.pdf");
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($t));

        print($t);
    }

    public function category(){

        $data ='';
        $url = "http://www.delivery-auto.com.ua/api/v4/Public/GetCargoCategory?culture=ru-RU";
        $type ="GET";

        $this->generateKey();
        $header = $this->key;

        $response = $this->connect($data,$url,$type,$header);
        return $response;
    }



}





