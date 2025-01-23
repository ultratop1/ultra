<?php
header("Access-Control-Allow-Origin: *");

$data = $_REQUEST;

if($data['method'] == 'save'){
    $file = json_decode(file_get_contents('temp_sms.json'), 1);
    
    foreach($data['data'] as $key => $value){
        $file[$key]['text'] = $value;
    }
    
    file_put_contents('temp_sms.json', json_encode($file));
    
    echo(json_encode($file));
    die;
}elseif($data['method'] == 'get') {
    $file = json_decode(file_get_contents('temp_sms.json'), 1);
    
    $res = [];
    
    $res['temp'] = $file;
    $res['senders'] = [
        'Dieton',
        'Slim Factor',
        'UltraTOP',
    ];
    
    echo(json_encode($res));
    die;
}

die;
