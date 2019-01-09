<?php

require(dirname(dirname(__FILE__)) . '/vendor/autoload.php');

use WebSocket\Client;

$prefix = 'http://192.168.9.230';
/**
 * Curl请求
 * Created by：Mp_Lxj
 * @date 2018/10/22 9:54
 * @param $url-为请求地址,
 * @param $data-为请求参数内容(可以是数组也可以是字符串),
 * @param $header-为请求报头,
 * @param $method-为请求方式,
 * @param $ssl-为是否https安全连接,默认不是false
 * @return mixed
 */
function sendCurl($url,$data=[],$header=[],$method='POST',$ssl=false){
	$ch = curl_init($url);
	curl_setopt($ch , CURLOPT_CUSTOMREQUEST , $method);  //设置请求方式为POST
	curl_setopt($ch , CURLOPT_POSTFIELDS , $data);  //设置请求发送参数内容,参数值为关联数组
	curl_setopt($ch , CURLOPT_HTTPHEADER , $header );  //设置请求报头的请求格式为json, 参数值为非关联数组
	curl_setopt($ch , CURLOPT_RETURNTRANSFER , true);
	if($ssl){
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   //服务器要求使用安全链接https请求时，不验证证书和hosts
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	}

	$result = curl_exec($ch);  //发送请求并获取结果

	curl_close($ch); //关闭curl
	return $result;
}

$client = new Client("wss://wss.singlewindow.cn:61231");
echo $client->receive();

$url = $prefix . '/customs/signCustomData';
$sign = sendCurl($url);
echo $sign;
$data = json_decode($sign,true);

if($data['status'] == 200 && $data['datas']){
	$session_id = $data['datas'][0]['sessionId'];
	$arr = [
		'_method' => 'cus-sec_SpcSignDataAsPEM',
		'_id' => 0,
		'args' => [
			'inData' => $data['datas'][0]['data'],
			'passwd' => '88888888'
		]
	];

	$request = str_replace('\\/','/',json_encode($arr));
	$client->send($request);
	$res = $client->receive();
	echo $res;

	$param = json_decode($res,true);
	$send_url = $prefix . '/customs/pushCustomData';
	if($param['_args']['Result']){
		$send = [
			'sessionId' => $session_id,
			'signValue' => $param['_args']['Data'][0],
			'certNo' => $param['_args']['Data'][1],
		];

		$result = sendCurl($send_url,$send);
		echo $result;
	}
}

