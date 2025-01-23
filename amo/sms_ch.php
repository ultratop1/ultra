<?php

$sms_ch = json_decode(file_get_contents('sms_ch.json'), 1);

$client = new SoapClient('http://turbosms.in.ua/api/wsdl.html');
    
$auth = [   
    'login' => 'dieton_r',
    'password' => '7dR9dRnFfbE6'   
];

$result = $client->Auth($auth);
$notes = [];

foreach($sms_ch as $key => $value){
    $sms = [];
    $sms['MessageId'] = $value['id'];
    
    $result = $client->GetMessageStatus($sms);
    
    if($result->GetMessageStatusResult == 'В очереди' || $result->GetMessageStatusResult == 'В очереди' || $result->GetMessageStatusResult == 'Сообщение передано в мобильную сеть' || $result->GetMessageStatusResult == 'Сообщение доставлено на сервер'){
        
    }else{
        $tt = $result->GetMessageStatusResult;
        
        unset($sms_ch[$key]);
        
        $notes[] = [
            'element_id'=> $key,
		    'element_type'=> $value['type'],
		    'note_type'=>25,
		    'created_by' => 0,
		    'params' => ['text' => $tt, "service" => "TurboSMS"],
        ];
    }
}

if(!empty($notes)){
    require_once 'inam.php';
    
    $amo = new AM();
    
    $res = $amo->createNotes($notes);
    file_put_contents('sms_ch.json', json_encode($sms_ch));    
}

