<?php

class AM
{
    public function __construct()
    {
		require_once('settings.php'); 
		
        $this->account = $domain;
        $this->login = $login;
        $this->key = $api;
        
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        $this->host = $amoHost;
        $this->tokenPath = $tokenFile;
        $this->authCode = $authCode;
        
        $this->sleep = 0;
        
        
        $this->amo = new Amo($this->account, $this->login, $this->key, 
            $clientId, $clientSecret, $redirectUri, $amoHost, $tokenFile
        );
    } 
    
    public function getTokenByAuthCode(){
        $res = $this->amo->getTokenByAuthCode();
    }
	
	public function getLeadsByStatsusNew($status)
    {
        $start = -500;
        $count = 500;
        $response = [];

        for ($i=0; $count == 500; $i+=500){
            sleep(1);
            $res = $this->amo->getLeadsListNew('500', $i, null, null, null, $status);

            if(!isset($res['_embedded'])){
                break;
            }else{
                $count = count($res['_embedded']['items']);
                $response = array_merge($res['_embedded']['items'], $response);
            }
        }

        if(isset($response))
            return $response;
        else
            return [];
    }
	
	public function getAccountInfo()
    {
        if (!isset($this->accountInfo)) {
            $this->accountInfo = $this->amo->getAccountInfo();
        }

        return $this->accountInfo;
    }
    
    public function createNotes($notes)
    {
        
        $request = ['add' => $notes];
        
        $response = $this->amo->setNotes($request);

        return $response;
    }
    
    public function setAuthCode()
    {
        $res = $this->amo->setAuthCode($this->authCode);
    }
    
    public function updateLeadsNew($leads)
    {
        $request = ['update' => $leads];
        
        $response = $this->amo->setLeadsNew($request);

        return $response;
    }
    
    public function getContactsByIds($ids)
    {
        $response = [];
        
        $ids_new = [];
        
        $j = 0;
        $jj = 0;
        
        foreach($ids as $key => $value) {
            $ids_new[$jj][] = $value;
            
            $j++;
            
            if($j > 200){
                $j = 0;
                $jj++;
            }
        }
        
        $ids = [];
        
        foreach($ids_new as $key => $ids) {
            $start = -500;
            $count = 500;
            
            for ($i=0; $count == 500; $i+=500){
                sleep(1);
                $res = $this->amo->getContactsListNew('500', $i, $ids);
    
                if(!isset($res['_embedded'])){
                    break;
                }else{
                    $count = count($res['_embedded']['items']);
                    $response = array_merge($res['_embedded']['items'], $response);
                }
            }
        }

        if(isset($response))
            return $response;
        else
            return [];
    } 
    
    public function getLeadByIds($ids)
    {
        $response = [];
        $ids_new = [];
        
        $j = 0;
        $jj = 0;
        
        foreach($ids as $key => $value) {
            $ids_new[$jj][] = $value;
            
            $j++;
            
            if($j > 200){
                $j = 0;
                $jj++;
            }
        }
        
        $ids = [];
        
        foreach($ids_new as $key => $ids) {
            $start = -500;
            $count = 500;
            
            for ($i=0; $count == 500; $i+=500){
                if($this->sleep == 5){
                    sleep(1);
                    $this->sleep = 0;
                }
                
                $this->sleep++;
                
                $res = $this->amo->getLeadsListNew('500', $i, $ids);
    
                if(!isset($res['_embedded'])){
                    break;
                }else{
                    $count = count($res['_embedded']['items']);
                    $response = array_merge($res['_embedded']['items'], $response);
                }
            }
        }

        if(isset($response))
            return $response;
        else
            return [];    
    }
}

class Amo
{   
    const URL = 'https://%s.amocrm.ru/private/api/v2/json/';
	const URL_NEW = 'https://%s.amocrm.ru/api/v2/';
  
    const AUTH_URL = 'https://%s.amocrm.ru/private/api/';

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    protected $login;

    protected $key;

    protected $subDomain;
 
    protected $curl;
    
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $host;
    protected $tokenPath;
    protected $authCode;
    protected $tokenInfo;

    final public function __construct($subDomain, $login, $key, 
        $clientId, $clientSecret, $redirectUri, $amoHost, $tokenFile
    )
    {
        $this->subDomain = $subDomain;
        $this->login = $login;
        $this->key = $key;
        
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        $this->host = $amoHost;
        $this->tokenPath = $tokenFile;
        
        if (file_exists($this->tokenPath)) {
            $this->tokenInfo = json_decode(file_get_contents($this->tokenPath), true);
        }
    }
	
	final public function getAccountInfo()
    {
        $request = $this->curlRequest(sprintf(self::URL . 'accounts/current', $this->subDomain));

        if (is_array($request) && isset($request['account'])) {
            $this->accountInfo = $request['account'];
            return $this->accountInfo;
        } else {
            return false;
        }
    }
    
    final public function setNotes($notes = null)
    {
        if (is_null($notes)) {
            return false;
        }

        $notes = json_encode($notes);
        
        $headers = array('Content-Type: application/json');

        return $this->curlRequest(sprintf(self::URL_NEW . 'notes', $this->subDomain), self::METHOD_POST, $notes, $headers, 30, true);
    }
    
    final public function getLeadsListNew(
        $limitRows = null,
        $limitOffset = null,
        $ids = null,
        $query = null,
        $responsible = null,
        $status = null,
        $dateModified = null
    ) {
        $headers = null;
        if (is_null($dateModified) === false) {
            $headers = array('IF-MODIFIED-SINCE: ' . $dateModified.' UTC');
        }

        $parameters = array();
        if (is_null($limitRows) === false) {
            $parameters['limit_rows'] = $limitRows;
            if (is_null($limitRows) === false) {
                $parameters['limit_offset'] = $limitOffset;
            }
        }

        if (is_null($ids) === false) {
            $parameters['id'] = $ids;
        }

        if (is_null($query) === false) {
            $parameters['query'] = $query;
        }

        if (is_null($responsible) === false) {
            $parameters['responsible_user_id'] = $responsible;
        }

        if (is_null($status) === false) {
            $parameters['status'] = $status;
        }

        return $this->curlRequest(
            sprintf(self::URL_NEW.'leads', $this->subDomain),
            self::METHOD_GET,
            count($parameters) > 0 ? http_build_query($parameters) : null, null, 30, true);
    }
	
    final public function setLeadsNew($leads = null)
    {
        if (is_null($leads)) {
            return false;
        }
		
        $request = $leads;
        $request_json = json_encode($request);

        $headers = array('Content-Type: application/json');

        return $this->curlRequest(sprintf(self::URL_NEW . 'leads', $this->subDomain), self::METHOD_POST, $request_json, $headers, 30, true);
    }  

    protected function curlRequest($url, $method = 'GET', $parameters = null, $headers = null, $timeout = 30, $type = false, $tryCount = 1)
    {
        $old_parameters = $parameters;
        
        if ($tryCount > 2 || !isset($this->tokenInfo['access_token'])) {
            throw new Exception('refreshToken error', 401);
            die;
        }
        
        if ($method == self::METHOD_GET && is_null($parameters) == false) {
            $url .= "?$parameters";
        }

        if (!$this->curl) {
            $this->curl = curl_init();
        }

        $headers[] = 'Authorization: Bearer ' . $this->tokenInfo['access_token'];

        curl_setopt($this->curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_FAILONERROR, false);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($this->curl, CURLOPT_HEADER, false);
        curl_setopt($this->curl, CURLOPT_COOKIEFILE, '-');
        curl_setopt($this->curl, CURLOPT_COOKIEJAR, '-');

        curl_setopt($this->curl, CURLOPT_POST, false);

        if (is_null($headers) === false && count($headers) > 0) {
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        } else {
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, array());
        }

        if ($method == self::METHOD_POST && is_null($parameters) === false) {
            curl_setopt($this->curl, CURLOPT_POST, true);

            if ($this->isJson($parameters) == false) {
                $parameters = http_build_query($parameters);
            }

            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $parameters);
        }

        $response = curl_exec($this->curl);
        $statusCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        $errno = curl_errno($this->curl);
        $error = curl_error($this->curl);
        
        if ($statusCode === 401) {
            $tryCount++;
            $this->refreshToken();
            return $this->curlRequest($url, $method, $old_parameters, $headers, $timeout, $type, $tryCount);
        }

        if ($errno) {
            $result = json_decode($response, true);
            if (isset($result['response']['error'])) {
                $error .= $result['response']['error'];
            }
            
            throw new Exception($error, $errno);
        }

        $result = json_decode($response, true);

        if ($statusCode >= 400) {
            $message = @$result['message'];
            if (isset($result['response']['error'])) {
                $message .= $result['response']['error'];
            }
            $message = $statusCode . ': ' . $message . '; url: ' . $url .
                '; parameters: ' . json_encode($parameters);
            
            throw new Exception($message, $statusCode);
        }

		if($type == true)
			return $result;
		
        return isset($result['response']) && count($result['response']) == 0 ? true : $result['response'];
    }

    protected function isJson($string)
    {
        if (is_string($string) == false) {
            return false;
        }
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
    
    final public function getContactsListNew(
        $limitRows = null,
        $limitOffset = null,
        $ids = null,
        $query = null,
        $responsible = null,
        $type = null,
        DateTime $dateModified = null
    ) {
        $headers = null;
        if (is_null($dateModified) === false) {
            $headers = array('if-modified-since: ' . $dateModified->format('D, d M Y H:i:s'));
        }

        $parameters = array();
        if (is_null($limitRows) === false) {
            $parameters['limit_rows'] = $limitRows;
            if (is_null($limitRows) === false) {
                $parameters['limit_offset'] = $limitOffset;
            }
        }

        if (is_null($ids) === false) {
            $parameters['id'] = $ids;
        }

        if (is_null($query) === false) {
            $parameters['query'] = $query;
        }

        if (is_null($responsible) === false) {
            $parameters['responsible_user_id'] = $responsible;
        }

        if (is_null($type) === false) {
            $parameters['type'] = $type;
        }

        return $this->curlRequest(
            sprintf(self::URL_NEW . 'contacts', $this->subDomain),
            self::METHOD_GET,
            count($parameters) > 0 ? http_build_query($parameters) : null,  $headers, 30, true);
    }
	
    public function setAuthCode($authCode)
    {
        $this->authCode = $authCode;
    }

    public function getTokenByAuthCode()
    {
        $requestData = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'code' => $this->authCode,
            'redirect_uri' => $this->redirectUri,
        ];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-Example-API-client/1.0');
        curl_setopt($curl, CURLOPT_URL, $this->host . 'oauth2/access_token');
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        $response = curl_exec($curl);
        $decodedResponse = json_decode($response, true);
        
        if (isset($decodedResponse['access_token'])) {
            file_put_contents($this->tokenPath, $response);
            $this->tokenInfo = $decodedResponse;
        }
        curl_close($curl);
    }

    public function refreshToken()
    {
        if (!file_exists($this->tokenPath)) {
            return;
        }

        $tokenInfo = json_decode(file_get_contents($this->tokenPath), true);

        if (!isset($tokenInfo['refresh_token'])) {
            return;
        }

        $authLink = $this->host . 'oauth2/access_token';
        $authData = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $tokenInfo['refresh_token'],
            'redirect_uri' => $this->redirectUri
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-Example-API-client/1.0');
        curl_setopt($curl, CURLOPT_URL, $authLink);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($authData));
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        $response = curl_exec($curl);
        file_put_contents($this->tokenPath, $response);
        $this->tokenInfo = json_decode($response, true);
        curl_close($curl);
    }
    
    
}
?>