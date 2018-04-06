<?php

/**
 * Created by PhpStorm.
 * User: secret
 * Date: 15.02.18
 * Time: 15:59
 */

//при изменениях в API очищать файл  wsdl в папке /tmp сервера
ini_set('soap.wsdl_cache_ttl', 157700000);
include_once 'libphp/db.obj.inc.php';
include_once 'libphp/settings.obj.inc.php';
include_once 'libphp/delivery.obj.inc.php';


class  IntimeAPI extends DB_Connection
{

    public $apiKay;
    public $cityName;
    public $warehoseName;
    public  $cityKey;
    public $warehoseKey;
    public $utilSettings;


    const URLS ="http://esb.intime.ua:8080/services/intime_api_3.0?wsdl";
 
    const ID_COUNTRY = '215';

    public function __construct()
    {
        $this->utilSettings = new NeadsSettingsUtil();
        $this->utilSettings->Retrieve();
        $this->soap = $this->connectAPI();
        $this->apiKay = $this->utilSettings->Settings['INTIME_API_KEY'];
        $this->cityName= $this->utilSettings->Settings['INTIME_SENDER_CITY'];
        $this->warehoseName = $this->utilSettings->Settings['INTIME_SENDER_WAREHOUSE'];
        $info=$this->getKeyWarehouse($this->cityName,$this->warehoseName);
        $this->cityKey = $info->Ref;
        $this->warehoseKey = $info->CityRef;
    }

    public function connectAPI()
    {
        $options = array(
            'api_key' =>  $this->apiKay,//ключ необходимый для регистрации и работы парсера через API intime SOAP
            'id' =>  IntimeAPI::ID_COUNTRY//ID украины
        );
        $soap = new SoapClient(IntimeAPI::URLS, $options);

        return $soap;
    }

    public function getKeyWarehouse($cityName,$warehoseName){
        $delivery = new NeadsDeliveryUtil();
        $delivery->getKeyWarehose($cityName,$warehoseName);
        return $delivery->Items[0];
    }

    public function create($param = '')
    {

        $responce ='';
        $param->cash_on_delivery_sum = $param->np_Cost;


        if(!empty($param)) {
            if($param->np_PaymentType == "NoCach"){
                $param->np_PaymentType = 2;

            }else{
                $param->np_PaymentType = 1;
                $param->cash_on_delivery_sum_revert = $param->cash_on_delivery_sum ;
            }

            WriteLog('logs/intimeInfo.txt',print_r($param,TRUE));
            $main = array(
                'api_key' => $this->apiKay,//очень важный ключик
                'locality_id' => $this->cityKey,//откуда
                'sender_warehouse_id' => $this->warehoseKey ,//id отправки //Промрынок 7-ой км, Хар. площ, склад №440
                'sender_address' => '',//адресс отправки
                'receiver_okpo' => '',
                'receiver_company_name' => '',
                'receiver_cellphone' => $param->np_recipient_phone,
                'receiver_lastname' => $param->np_recipient_second_name,//получатель
                'receiver_firstname' => $param->np_recipient_first_name,//получатель
                'receiver_patronymic' => $param->np_recipient_middle_name,//получатель
                'receiver_locality_id' => $param->np_recipient_city_ref,//куда прислать
                'receiver_warehouse_id' => $param->np_recipient_warehouse_ref,//получатель
                'receiver_address' => '',
                'payment_type_id' => 1,//1 -готівкою; 2 - безготівково
                'payer_type_id' => 2,//Тип платника*. Значення: 1 – Відправник
                'return_day' => '',
                'cost_return' => $param->cash_on_delivery_sum,//Оголошена вартість* . Мінімальне значення 200 грн. для Донецької,Луганської областей – 500 грн
                'cash_on_delivery_sum' =>  $param->cash_on_delivery_sum_revert,//Сума післяплати
                'client_doc_id' => '',
                'cancel_packaging' => 1,
                'sender_paid_sum' => '',
                'third_party_okpo' => '',
                'third_party_company_name' => '',
                'third_party_lastname' => '',
                'third_party_firstname' => '',
                'third_party_patronymic' => '',
                'third_party_locality_id' => '',
                'third_party_store_id' => '',
                'third_party_address' => '',
                'seats' => '
<SEATS>
        <SEAT>
                <GOODS_TYPE_ID>1</GOODS_TYPE_ID><!--Кодформатувантажумісця*Значення:1 – Посилки та вантажі-->
                <WEIGHT_M>'.$param->np_Weight.'</WEIGHT_M><!--Вага, кг -->
                <LENGTH_M>'.$param->package_length.'</LENGTH_M><!--Довжина, см -->
                <WIDTH_M>'.$param->package_width.'</WIDTH_M><!--Ширина, см-->
                <HEIGHT_M>'.$param->package_height.'</HEIGHT_M><!--Висота, см-->
                <WEIGHT_R></WEIGHT_R>
                <GSIZE_R></GSIZE_R>
                <COUNT_M>'.$param->np_SeatsAmount.'</COUNT_M>
                <GOODS_TYPE_DESCR_ID>418</GOODS_TYPE_DESCR_ID><!--Код текстиля-->
                <BOX_ID></BOX_ID>
        </SEAT> 
</SEATS>'
            ,
                'commands' => '
                            <COMS>
                                <COM>
                                <COM_ID>50</COM_ID>
                                <COM_VAL>750</COM_VAL>
                                <PAYER_ID>2</PAYER_ID>
                                <PAYMENT_ID>1</PAYMENT_ID>
                                <PERC_SEND></PERC_SEND>
                                <PERC_REC></PERC_REC>
                                <LOC_P></LOC_P>
                                <WH_P></WH_P>
                                <ADDRESS_P></ADDRESS_P>
                                <THIRD_PARTY_OKPO_P></THIRD_PARTY_OKPO_P>
                                <THIRD_PARTY_СOMPANY_NAME_P></THIRD_PARTY_СOMPANY_NAME_P>
                                <THIRD_PARTY_CELLPHONE_P></THIRD_PARTY_CELLPHONE_P>
                                <THIRD_PARTY_LASTNAME_P></THIRD_PARTY_LASTNAME_P>
                                <THIRD_PARTY_FIRSTNAME_P></THIRD_PARTY_FIRSTNAME_P>
                                <THIRD_PARTY_PATRONYMIC_P></THIRD_PARTY_PATRONYMIC_P>
                                <LOC_REC></LOC_REC>
                                <WH_REC></WH_REC>
                                <ADR_REC></ADR_REC>
                                <THIRD_PARTY_OKPO_REC></THIRD_PARTY_OKPO_REC>
                                <THIRD_PARTY_СOMPANY_NAME_REC></THIRD_PARTY_СOMPANY_NAME_REC>
                                <THIRD_PARTY_CELLPHONE_REC></THIRD_PARTY_CELLPHONE_REC>
                                <THIRD_PARTY_LASTNAME_REC></THIRD_PARTY_LASTNAME_REC>
                                <THIRD_PARTY_FIRSTNAME_REC></THIRD_PARTY_FIRSTNAME_REC>
                                <THIRD_PARTY_PATRONYMIC_REC></THIRD_PARTY_PATRONYMIC_REC>
                                </COM>
                            </COMS>
    ',);

            $responce = $this->soap->DECLARATION_INSERT_UPDATE($main);
            WriteLog("logs/intime.txt",print_r($responce,TRUE));
            if( $responce->Entry_declaration_ins_upd->res =="OK"){

                $response['status'] = TRUE;
                $response['responce']= $responce;
            }else{

                $response['status'] = FALSE;
                $errorType = explode("-",$responce->Entry_declaration_ins_upd->res);
                $response['responce'] = $errorType[1];
                $errorType = trim($errorType);
                
                if(empty($errorType)){
                    $errorType = $responce->Entry_declaration_ins_upd->res;
                    $response['responce'] = $errorType;
                }
                
                WriteLog("logs/intime.txt",print_r($response,TRUE));
            }

        }else{
            $response['status'] = FALSE;
            $response['responce'] = "Ошибка при создании накладной";
        }

        return $response;
    }

}







