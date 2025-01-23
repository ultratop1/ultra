<?php
header("Access-Control-Allow-Origin: *");
$data = $_REQUEST;

require_once 'inam.php';

$amo = new AM();

$phones_mas = [];

$c_c = [];

if($data['type'] == 1){
    $phones_mas = [];
    
    if(!empty($data['datas']['cont'])){
        $conts = $amo->getContactsByIds($data['datas']['cont']);
        
        foreach($conts as $key => $value){
            if(!empty($value['custom_fields']))
            foreach($value['custom_fields'] as $k => $val){
                if($val['id'] == '64337'){
                    foreach($val['values'] as $kk => $v){
                        $v['value'] = preg_replace("/\D+/", "", $v['value']);
                        
                        $phones_mas[$v['value']] = $v['value'];
                        $c_c[$v['value']] = $value['id'];
                    }
                }
            }
        }
    }
}elseif($data['type'] == 2){
    $leads = $amo->getLeadByIds($data['datas']['lead']);
    
    $c_ids = [];
    $c_l = [];
    
    $l_all = [];
    
    $m_mess = [];
    
    foreach($leads as $key => $value){
        $l_all[$value['id']] = $value;        
        
        if(!empty($value['main_contact'])){
            $c_ids[$value['main_contact']['id']] = $value['main_contact']['id'];
            $c_l[$value['main_contact']['id']] = $value['id'];
        }
    }
    
    if(!empty($c_ids)){
        $conts = $amo->getContactsByIds($c_ids);
        
        foreach($conts as $key => $value){
            if(!empty($value['custom_fields']))
            foreach($value['custom_fields'] as $k => $val){
                if($val['id'] == '64337'){
                    foreach($val['values'] as $kk => $v){
                        $v['value'] = preg_replace("/\D+/", "", $v['value']);
                        
                        $phones_mas[$v['value']] = $v['value'];
                        $c_c[$v['value']] = $value['id'];
                    }
                }
            }
        }
    }
}

$sms_ch = json_decode(file_get_contents('sms_ch.json'), 1);

if(!empty($phones_mas)){
    $client = new SoapClient('http://turbosms.in.ua/api/wsdl.html');
    
    $auth = [   
        'login' => 'dieton_r',
        'password' => '7dR9dRnFfbE6'   
    ];
    
    $result = $client->Auth($auth);
    
    $notes = [];
    
    foreach($phones_mas as $key => $value){
        $phone = $value;
     
        if(strlen($phone) == 12)
            $phone = '+'.$phone;
        elseif(strlen($phone) == 10)
            $phone = '+38'.$phone;
        else{
            
        }
        
        $sms = [   
            'sender' => $data['sender'],
            'destination' => $phone,
            'text' => $data['mess']
        ];
        
        $result = $client->SendSMS($sms);
       
        if($result->SendSMSResult->ResultArray[0] != 'Сообщения успешно отправлены'){
            $tt = $result->SendSMSResult->ResultArray[0].'. '.$result->SendSMSResult->ResultArray[1];
            
            $notes[] = [
                'element_id'=> $c_c[$value],
    		    'element_type'=> 1,
    		    'note_type'=>25,
    		    'created_by' => 0,
    		    'params' => ['text' => 'Произошла ошибка при отправке СМС: "'.$tt.'"', "service" => "TurboSMS"],
    		    
            ];
        }else {
            $sms_ch[$c_c[$value]] = ['type' => 1, 'id' => $result->SendSMSResult->ResultArray[1]];
        }       
        
        $notes[] = [
            'element_id'=> $c_c[$value],
            'element_type'=>'1',
            'note_type'=>'103',
            'params' => ['text' => $data['sender'].': '.$data['mess'], 'PHONE' => $value]
        ];
    }
    
    file_put_contents('sms_ch.json', json_encode($sms_ch));
    
    $amo->createNotes($notes);
}


echo json_encode('success');
die;
