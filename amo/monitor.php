<?php
set_time_limit(9999999);
ini_set('memory_limit', -1);

require_once 'inam.php';

class WO
{
    public function __construct()
    {       
        $this->amo = new AM();
    }   
    
    public function start($data)
    {
        $statuses = [];
		
        $status_ms = [
            '1' => '36004924',
            
            '2' => '36217918',
            '3' => '36217918',
            
            '4' => '36004927',
            '41' => '36004927',
            
            '7' => '36004930',
            '8' => '36004930',
            
            '+3' => '36169093',
            
            '10' => '36004933',
            
            '9' => '142',
            '11' => '142',
            
            '102' => '143',
            '103' => '143',
            '108' => '143',
        ];
        
        $sms = [
            
        ];
        
        $client = new SoapClient('http://turbosms.in.ua/api/wsdl.html'); 
        
        $auth = [   
            'login' => 'dieton_r',
            'password' => '7dR9dRnFfbE6'   
        ];
        
        $result = $client->Auth($auth);
        
        $status_sort = [];
        
        $account = $this->amo->getAccountInfo();
        var_dump($account);die;
        foreach ($account['pipelines'] as $key => $value) {
            foreach ($value['statuses'] as $k => $val) {
                $status_sort[$val['id']] = $val['sort'];
                
                if($val['id'] == '142' || $val['id'] == '143')
                    continue;
                
                $statuses[] = $val['id'];
                
            }
        }
        
        $now = strtotime('now 00:00:00');
        
        $leads = $this->amo->getLeadsByStatsusNew(array_values($statuses));
        
        $ttns = [];
        $l_ttn = [];
        
        $i = 0;
        $ii = 0;
        
        $l_all = [];
        
        $file = json_decode(file_get_contents('check.json'), 1);
        
        foreach($leads as $key => $value){
            if(!empty($value['custom_fields']))
            foreach($value['custom_fields'] as $k => $val){
                if($val['id'] == '315697'){
                    $i++;
                    $ttns[$ii][]['DocumentNumber'] = $val['values'][0]['value'];
                    $l_ttn[$val['values'][0]['value']] = $value['id'];
                    
                    $l_all[$value['id']] = $value;
                    
                    if($i >= 95){
                        $i = 0;
                        $ii++;
                    }
                    
                    break;
                }
            }
        }
		
        $ups = [];
        
        $i = 0;
        $ii = 0;
        
        $smss = [];
        
        foreach($ttns as $key => $value){
            $send = [];
        
            $send['apiKey'] = 'ffe3e2ba82c7a109c96a6617a2894b97';
            $send['modelName'] = 'TrackingDocument';
            $send['calledMethod'] = 'getStatusDocuments';
            $send['methodProperties']['Documents'] = $value;
            
            $res = $this->getTracking($send);
            
            if(!isset($res['data'])) continue;
            
            sleep(1);
            
            foreach($res['data'] as $k => $val){
                if(!isset($status_ms[$val['StatusCode']]))
                    continue;
                
                if(isset($file[$l_ttn[$val['Number']].'_'.$status_ms[$val['StatusCode']]])){
                    if($l_ttn[$val['Number']].'_'.$status_ms[$val['StatusCode']] == $l_ttn[$val['Number']].'_36004930' && (time() - $file[$l_ttn[$val['Number']].'_36004930']) >= (3*24*60*60)){
                          $ups[] = [
                            'id' => $l_ttn[$val['Number']],
                            'updated_at' => time(),
                            'updated_by' => 0,
                            'status_id' => 36169093
                        ];                        
                        
                        $smss[$l_ttn[$val['Number']]]['lead'] = $l_all[$l_ttn[$val['Number']]];
                        $smss[$l_ttn[$val['Number']]]['mess'] = '{{Имя [Контакт]}}, здравствуйте! Напоминаем – Вас ждет посылка в отделении НП {{Отделение [Новая почта]}} {{Город [Новая почта]}}. Просим забрать, т.к. ее могут отправить обратно. Спасибо!';
                        
                        unset($file[$l_ttn[$val['Number']].'_'.$status_ms[$val['StatusCode']]]);
                        
                        continue;
                    }else
                        continue;
                }
                    
                $file[$l_ttn[$val['Number']].'_'.$status_ms[$val['StatusCode']]] = $now;
                
                if($status_sort[$l_all[$l_ttn[$val['Number']]]['status_id']] >= $status_sort[$status_ms[$val['StatusCode']]])
                    continue;
                
                $ups[] = [
                    'id' => $l_ttn[$val['Number']],
                    'updated_at' => time(),
                    'updated_by' => 0,
                    'status_id' => $status_ms[$val['StatusCode']]
                ];
                
                
                
                if($status_ms[$val['StatusCode']] == '36004927'){
                    $smss[$l_ttn[$val['Number']]]['lead'] = $l_all[$l_ttn[$val['Number']]];
                    $smss[$l_ttn[$val['Number']]]['mess'] = 'Ваш заказ отправлен. ТТН: {{Экспресс-накладная [Новая почта]}}';
                }
            }
			
            if(!empty($ups)){
                $this->amo->updateLeadsNew($ups);
            }
        }
        
        file_put_contents('check.json', json_encode($file));
        $sms_ch = json_decode(file_get_contents('sms_ch.json'), 1);
        
        if(!empty($smss)){
            $ids_c = [];
            
            foreach($smss as $key => $value){
                if(!empty($value['lead']['main_contact']))
                    $ids_c[$value['lead']['main_contact']['id']] = $value['lead']['main_contact']['id'];
            }
            
            $conts_r = [];
            $conts = [];
            
            if(!empty($ids_c))
                $conts_r = $this->amo->getContactsByIds(array_values($ids_c));
                
            if(!empty($conts_r))
            foreach($conts_r as $key => $value){
                if(!empty($value['custom_fields']))
                foreach($value['custom_fields'] as $k => $val){
                    if($val['id'] == '64337'){
                        $conts[$value['id']]['phone'] = $val['values'][0]['value'];
                        $conts[$value['id']]['name'] = $value['name'];
                        
                        break;
                    }
                }
            }
			
            foreach($smss as $key => $value){
                $id_c = '';
            
                if(!empty($value['lead']['main_contact']))
                    $ids_c = $value['lead']['main_contact']['id'];
                
                if(!isset($conts[$ids_c]))
                    continue;
                
                $phone = $conts[$ids_c]['phone'];
                
                if(strlen($phone) == 12)
                    $phone = '+'.$phone;
                elseif(strlen($phone) == 10)
                    $phone = '+38'.$phone;
                else{
                    
                }
                
                $th_city = '';
                $th_wr = '';
                $th_ttn = '';
            
                $sender = 'UltraTOP';
                
                $fl_l = false;
            
                if(!empty($value['lead']['custom_fields']))
                foreach($value['lead']['custom_fields'] as $k => $val){
                    if($val['id'] == '315665'){
                        $th_city = $val['values'][0]['value'];
                        $th_city = explode(',', $th_city)[0];
                    }elseif($val['id'] == '315667'){
                        $th_wr = $val['values'][0]['value'];
                        $th_wr = explode(':', $th_wr)[0];
                    }elseif($val['id'] == '315697'){
                        $th_ttn = $val['values'][0]['value'];
                    }elseif($val['id'] == '318579'){
                        $sender = $val['values'][0]['value'];                        
                    }elseif($val['id'] == '319279' && $val['values'][0]['value'] == 1){
                        $fl_l = true;
                    }
                }
                
                if($fl_l == false){
                    continue;
                }
                
                $th_name = $conts[$ids_c]['name'];
                
                $th_name_ex = explode(' ', $conts[$ids_c]['name']);
                
                if(isset($th_name_ex[1])){
                    $th_name = $th_name_ex[1];
                }
                
                $th_wr = str_replace('Відділення', '', $th_wr);
                $th_wr = explode('(', $th_wr)[0];
				
				$th_city = trim(explode(',', $th_city)[0]);
                
                $th_wr = trim($th_wr);
                
                $value['mess'] = str_replace('{{Имя [Контакт]}}', $th_name, $value['mess']);
                
                $value['mess'] = str_replace('{{Город [Новая почта]}}', $th_city, $value['mess']);
                $value['mess'] = str_replace('{{Отделение [Новая почта]}}', $th_wr, $value['mess']);
                $value['mess'] = str_replace('{{Экспресс-накладная [Новая почта]}}', $th_ttn, $value['mess']);                              
                
                
                $sms = [   
                    'sender' => $sender,   
                    'destination' => $phone,   
                    'text' => $value['mess']
                ];
				
                $result = [];
                
                $result = $client->SendSMS($sms);                
                
                $notes = [];
                
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
                
                $notes[] = [
                    'element_id'=> $value['lead']['id'],
        		    'element_type'=>'2',
        		    'note_type'=>'103',
        		    'params' => ['text' => $sms['sender'].': '.$sms['text'], 'PHONE' => $conts[$ids_c]['phone']]
                ];
                
                $this->amo->createNotes($notes);
            }
        }
        
        file_put_contents('sms_ch.json', json_encode($sms_ch));
    }
    
    private function getTracking($data) {
            $url = 'https://api.novaposhta.ua/v2.0/json/en/documentsTracking/';
            
            $headers = array('Content-Type: application/json');
        
        	$curl = curl_init($url);
    	    curl_setopt($curl, CURLOPT_POST, true);
    	    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    	    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    	    $output = curl_exec($curl);
    	    curl_close($curl);
    
            $output = json_decode($output, 1);
    
    	    return $output;     
        }
}

function run()
{
    try {
        $hook = new WO();
        $hook->start();

    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        print $e->getMessage();
        trigger_error($e->getMessage()."\r\nFile: ".$e->getFile()."\r\nLine: ".$e->getLine(). "\r\nCode:".$e->getCode(), E_USER_ERROR);        
        exit;
    }
}

run();