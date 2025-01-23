<?php
header("Access-Control-Allow-Origin: *");
$data = $_REQUEST;

if(!isset($data['phone'])){
    echo json_encode($data);
    die;
}

$client = new SoapClient('http://turbosms.in.ua/api/wsdl.html'); 

$sender = $data['sender'];

$phone = $data['phone'];
$sms = $data['sms'];

$auth = [   
    'login' => 'dieton_r',
    'password' => '7dR9dRnFfbE6'   
];

$result = $client->Auth($auth);

if(strlen($phone) == 12)
    $phone = '+'.$phone;
elseif(strlen($phone) == 10)
    $phone = '+38'.$phone;
else{

}

$sms = [   
    'sender' => $sender,
    'destination' => $phone,
    'text' => $sms
];


require_once 'inam.php';
$result = $client->SendSMS($sms);

$sms_ch = json_decode(file_get_contents('sms_ch.json'), 1);

$amo = new AM();

$notes[] = [
    'element_id'=> $data['id'],
    'element_type'=>'2',
    'note_type'=>'103',
    'params' => ['text' => $sms['sender'].': '.$sms['text'], 'PHONE' => $data['phone']]
];

if($result->SendSMSResult->ResultArray[0] != 'Сообщения успешно отправлены'){
    $tt = $result->SendSMSResult->ResultArray[0].'. '.$result->SendSMSResult->ResultArray[1];
    
    $notes[] = [
        'element_id'=> $value['lead']['id'],
	    'element_type'=>'2',
	    'note_type'=> 25,
	    'created_by' => 0,
	    'params' => ['text' => 'Произошла ошибка при отправке СМС: "'.$tt.'"', "service" => "TurboSMS"],
    ];
}else {
    $sms_ch[$value['lead']['id']] = ['type' => 2, 'id' => $result->SendSMSResult->ResultArray[1]];
}
    
$amo->createNotes($notes);

file_put_contents('sms_ch.json', json_encode($sms_ch));

echo json_encode('success');
die;
