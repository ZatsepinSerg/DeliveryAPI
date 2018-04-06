<?php
/**
 * Created by PhpStorm.
 * User: secret
 * Date: 14.02.18
 * Time: 13:31
 */

class ConnectionAPI
{

    /**
     * @param string $data
     * @param $url
     * @param string $type
     * @param array $header
     * @return mixed
     */
    public function connect($data="", $url, $type = "POST", $header =array())
    {
        if(count( $header)){
            $headers= array(
                'Content-Type: application/json',
                'HMACAuthorization: '.$header['HMACAuthorization'],
            );
        }else{
            $headers= array(
                'Content-Type: application/json',
            );
        }
        
        if( $curl = curl_init()) {
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $type);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_HTTPHEADER,$headers);

            $response = curl_exec($curl);
            curl_close($curl);
            
            return $response;
        }
    }
}