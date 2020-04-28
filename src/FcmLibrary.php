<?php
namespace FcmLibrary;

use DateTime;
use DateTimeZone;
use FcmLibrary\Exceptions\FcmLibraryException;
use Google_Client;
use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;

class FcmLibrary {
    private $token;
    private $url;
    private $projectName;
    private $developerKey;
    
    private $to;
    private $toType;
    private $title;
    private $body;
    private $ttl;
    
    const TO_TYPE_CONDITION = "condition";
    const TO_TYPE_MULTIPLE = "multiple";
    const TO_TYPE_TOPIC = "topic";
    
    public function __construct() {
        $this->url = "https://fcm.googleapis.com/v1/projects/%s/messages:send";
    }
    
    public function send(){
        if(empty($this->token))
            throw new FcmLibraryException("Informe o arquivo JSON para gerar token de acesso", FcmLibraryException::TOKEN_EMPTY);
        
        if(empty($this->projectName))
            throw new FcmLibraryException("Informe o nome do projeto do firebase", FcmLibraryException::PROJECT_NAME_EMPTY);
        
        if(empty($this->to))
            throw new FcmLibraryException("Informe a destino da notificação", FcmLibraryException::TO_EMPTY);
        
        if(empty($this->title))
            throw new FcmLibraryException("Informe o titulo da notificação", FcmLibraryException::TITLE_EMPTY);
        
        if(empty($this->body))
            throw new FcmLibraryException("Informe o corpo da notificação", FcmLibraryException::BODY_EMPTY);
        
        $data = array(
            "message" => array(
                "android" => array(                    
                    "data" => array(
                        "title" => $this->title,
                        "body" => $this->body
                    )
                ),
                "apns" => array(
                    "payload" => array(
                        "aps" => array(
                            "alert" => array(
                                "title" => $this->title,
                                "body" => $this->body
                            ),
                            "content-available" => 1,
                            "category" => "FFNotification",
                            "mutable-content" => 1
                        )
                    )
                )
            )
        );
        
        if($this->ttl){
            $dateToTll = new DateTime($ttl);
            $today = new DateTime();

            $dateToTll->setTimezone(new DateTimeZone('America/Sao_Paulo'));
            $today->setTimezone(new DateTimeZone('America/Sao_Paulo'));

            $interval = $dateToTll->getTimestamp() - $today->getTimestamp();

            if($interval > 0){
                $data["message"]["android"]["ttl"] = $interval."s";
                $data["message"]["apns"]["headers"] = array(
                    "apns-expiration" => (string)$dateToTll->getTimestamp()
                );
            }
        }
        
        if($this->payload){
            $data["message"]["android"]["data"] = array_merge($data["message"]["android"]["data"], $this->payload);
            $data["message"]["apns"]["payload"] = array_merge($data["message"]["apns"]["payload"], $this->payload);
        }
        
        switch ($this->toType){
            case self::TO_TYPE_TOPIC:
                $data["message"]["topic"] = $this->to;                
                return $this->post($data);
            break;
        
            case self::TO_TYPE_MULTIPLE:                
                $multipleData = [];
                
                foreach($this->to as $token){
                    $data["message"]["token"] = $token;
                    $multipleData[] = $data;
                }
                                
                return $this->multiplePost($multipleData);
            break;
        
            case self::TO_TYPE_CONDITION:
                $data["message"]["condition"] = $this->to;
                return $this->post($data);
            break;
        }
                
        throw new FcmLibraryException("Destino da notificação em formato inválido", FcmLibraryException::INVALID_TO_FORMAT);
    }
    
    public function ttl($ttl){
        $this->ttl = $ttl;
        return $this;
    }
    
    public function payload(array $payload){
        $this->payload = $payload;
        return $this;
    }
    
    public function body($body){
        $this->body = $body;
        return $this;
    }
    
    public function title($title){
        $this->title = $title;
        return $this;
    }
        
    public function to($to){
        $this->to = $to;
        
        if(is_string($to)){
            $this->toType = self::TO_TYPE_TOPIC;
        }
        
        if(is_array($this->to)){
            $this->toType = self::TO_TYPE_MULTIPLE;
        }
                
        return $this;
    }
    
    public function condition($condition){
        $this->to = $condition;
        $this->toType = self::TO_TYPE_CONDITION;
        return $this;
    }
    
    public function sendToTopic($topic, $title, $body, array $payload = array(), $ttl = null){
        if(empty($this->token))
            throw new FcmLibraryException("Informe o arquivo JSON para gerar token de acesso", FcmLibraryException::TOKEN_EMPTY);
        
        if(empty($this->projectName))
            throw new FcmLibraryException("Informe o nome do projeto do firebase", FcmLibraryException::PROJECT_NAME_EMPTY);
        
        $data = array(
            "message" => array(
                "android" => array(                    
                    "data" => array(
                        "title" => $title,
                        "body" => $body
                    )
                ),
                "apns" => array(
                    "payload" => array(
                        "aps" => array(
                            "alert" => array(
                                "title" => $title,
                                "body" => $body
                            ),
                            "content-available" => 1,
                            "category" => "FFNotification",
                            "mutable-content" => 1
                        )
                    )
                )
            )
        );
        
        if($ttl){            
            $dateToTll = new DateTime($ttl);
            $today = new DateTime();
            
            $dateToTll->setTimezone(new DateTimeZone('America/Sao_Paulo'));
            $today->setTimezone(new DateTimeZone('America/Sao_Paulo'));
            
            $interval = $dateToTll->getTimestamp() - $today->getTimestamp();

            if($interval > 0){
                $data["message"]["android"]["ttl"] = $interval."s";
                $data["message"]["apns"]["headers"] = array(
                    "apns-expiration" => (string)$dateToTll->getTimestamp()
                );
            }
        }
        
        $data["message"]["android"]["data"] = array_merge($data["message"]["android"]["data"], $payload);
        $data["message"]["apns"]["payload"] = array_merge($data["message"]["apns"]["payload"], $payload);
        
        if(is_string($topic)){
            $data["message"]["topic"] = $topic;
            return $this->post($data);
        }
        
        $multipleData = [];
        
        foreach($topic as $target){
            $data["message"]["token"] = $target;
            $multipleData[] = $data;
        }
        
        return $this->multiplePost($multipleData);
    }
    
    public function setConfigJson($pathToFileJson){
        if(empty($this->developerKey))
            throw new FcmLibraryException("Informe o codigo de desenvolvedor antes de setar o config json", FcmLibraryException::DEVELOPER_KEY_EMPTY);        
        
        try{
            $client = new Google_Client();
            $client->setAuthConfig($pathToFileJson);
            $client->addScope("https://www.googleapis.com/auth/firebase.messaging");
            $client->setDeveloperKey($this->developerKey);
            $client->refreshTokenWithAssertion();
            $data = $client->getAccessToken();        
            $this->token = $data["access_token"];
        } catch (Exception $ex) {
            throw new FcmLibraryException("[Google_Client] Erro ao gerar o token: " . $ex->getMessage(), FcmLibraryException::ERROR_GENERATE_TOKEN);
        }
        
        return $this;
    }
    
    public function setProjectName($name){
        $this->projectName = $name;
        return $this;
    }
    
    public function setDeveloperKey($key){
        $this->developerKey = $key;
        return $this;
    }
    
    public function multiplePost(array $data){
        $mh = curl_multi_init();
        
        $multiCurl = array();
        $result = array();
        
        foreach($data as $i => $post){            
            $url = \sprintf($this->url, $this->projectName);
        
            $multiCurl[$i] = curl_init();

            $header = array(
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json'
            );

            $post = json_encode($post);

            curl_setopt($multiCurl[$i], CURLOPT_URL, $url);
            curl_setopt($multiCurl[$i], CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($multiCurl[$i], CURLOPT_POSTFIELDS, $post);
            curl_setopt($multiCurl[$i], CURLOPT_HTTPHEADER, $header);
            curl_setopt($multiCurl[$i], CURLOPT_SSLVERSION, 1);
            curl_setopt($multiCurl[$i], CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($multiCurl[$i], CURLOPT_SSL_VERIFYPEER, false);

            curl_multi_add_handle($mh, $multiCurl[$i]);
        }
        
        $index = -1;
        
        do {
          
            curl_multi_exec($mh, $index);
          
        } while($index > 0);
        
        foreach($multiCurl as $k => $ch) {            
            $jsonRetorno = trim(curl_multi_getcontent($ch));
            $resposta = json_decode($jsonRetorno);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErrorCode = curl_errno($ch);

            $result[$k] =  array(
                "code" => $code,
                "data" => $resposta,
                "jsonData" => $jsonRetorno,
                "error" => $this->errorCurl[$curlErrorCode]
            );
            
            curl_multi_remove_handle($mh, $ch);
        }
        
        curl_multi_close($mh);
        
        return $result;
    }
    
    private function post($data){
        $url = \sprintf($this->url, $this->projectName);
        
        $ch = curl_init();
        
        $header = array(
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json'
        );
        
        $post = json_encode($data);
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // JSON de retorno 
        $jsonRetorno = trim(curl_exec($ch));
        $resposta = json_decode($jsonRetorno);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrorCode = curl_errno($ch);

        curl_close($ch);
        
        return array(
            "code" => $code,
            "data" => $resposta,
            "jsonData" => $jsonRetorno,
            "error" => $this->errorCurl[$curlErrorCode]
        );
    }
    
    private $errorCurl = array(
        0 => 'NO_ERROR',
        1 => 'CURLE_UNSUPPORTED_PROTOCOL',
        2 => 'CURLE_FAILED_INIT',
        3 => 'CURLE_URL_MALFORMAT',
        4 => 'CURLE_URL_MALFORMAT_USER',
        5 => 'CURLE_COULDNT_RESOLVE_PROXY',
        6 => 'CURLE_COULDNT_RESOLVE_HOST',
        7 => 'CURLE_COULDNT_CONNECT',
        8 => 'CURLE_FTP_WEIRD_SERVER_REPLY',
        9 => 'CURLE_REMOTE_ACCESS_DENIED',
        11 => 'CURLE_FTP_WEIRD_PASS_REPLY',
        13 => 'CURLE_FTP_WEIRD_PASV_REPLY',
        14 => 'CURLE_FTP_WEIRD_227_FORMAT',
        15 => 'CURLE_FTP_CANT_GET_HOST',
        17 => 'CURLE_FTP_COULDNT_SET_TYPE',
        18 => 'CURLE_PARTIAL_FILE',
        19 => 'CURLE_FTP_COULDNT_RETR_FILE',
        21 => 'CURLE_QUOTE_ERROR',
        22 => 'CURLE_HTTP_RETURNED_ERROR',
        23 => 'CURLE_WRITE_ERROR',
        25 => 'CURLE_UPLOAD_FAILED',
        26 => 'CURLE_READ_ERROR',
        27 => 'CURLE_OUT_OF_MEMORY',
        28 => 'CURLE_OPERATION_TIMEDOUT',
        30 => 'CURLE_FTP_PORT_FAILED',
        31 => 'CURLE_FTP_COULDNT_USE_REST',
        33 => 'CURLE_RANGE_ERROR',
        34 => 'CURLE_HTTP_POST_ERROR',
        35 => 'CURLE_SSL_CONNECT_ERROR',
        36 => 'CURLE_BAD_DOWNLOAD_RESUME',
        37 => 'CURLE_FILE_COULDNT_READ_FILE',
        38 => 'CURLE_LDAP_CANNOT_BIND',
        39 => 'CURLE_LDAP_SEARCH_FAILED',
        41 => 'CURLE_FUNCTION_NOT_FOUND',
        42 => 'CURLE_ABORTED_BY_CALLBACK',
        43 => 'CURLE_BAD_FUNCTION_ARGUMENT',
        45 => 'CURLE_INTERFACE_FAILED',
        47 => 'CURLE_TOO_MANY_REDIRECTS',
        48 => 'CURLE_UNKNOWN_TELNET_OPTION',
        49 => 'CURLE_TELNET_OPTION_SYNTAX',
        51 => 'CURLE_PEER_FAILED_VERIFICATION',
        52 => 'CURLE_GOT_NOTHING',
        53 => 'CURLE_SSL_ENGINE_NOTFOUND',
        54 => 'CURLE_SSL_ENGINE_SETFAILED',
        55 => 'CURLE_SEND_ERROR',
        56 => 'CURLE_RECV_ERROR',
        58 => 'CURLE_SSL_CERTPROBLEM',
        59 => 'CURLE_SSL_CIPHER',
        60 => 'CURLE_SSL_CACERT',
        61 => 'CURLE_BAD_CONTENT_ENCODING',
        62 => 'CURLE_LDAP_INVALID_URL',
        63 => 'CURLE_FILESIZE_EXCEEDED',
        64 => 'CURLE_USE_SSL_FAILED',
        65 => 'CURLE_SEND_FAIL_REWIND',
        66 => 'CURLE_SSL_ENGINE_INITFAILED',
        67 => 'CURLE_LOGIN_DENIED',
        68 => 'CURLE_TFTP_NOTFOUND',
        69 => 'CURLE_TFTP_PERM',
        70 => 'CURLE_REMOTE_DISK_FULL',
        71 => 'CURLE_TFTP_ILLEGAL',
        72 => 'CURLE_TFTP_UNKNOWNID',
        73 => 'CURLE_REMOTE_FILE_EXISTS',
        74 => 'CURLE_TFTP_NOSUCHUSER',
        75 => 'CURLE_CONV_FAILED',
        76 => 'CURLE_CONV_REQD',
        77 => 'CURLE_SSL_CACERT_BADFILE',
        78 => 'CURLE_REMOTE_FILE_NOT_FOUND',
        79 => 'CURLE_SSH',
        80 => 'CURLE_SSL_SHUTDOWN_FAILED',
        81 => 'CURLE_AGAIN',
        82 => 'CURLE_SSL_CRL_BADFILE',
        83 => 'CURLE_SSL_ISSUER_ERROR',
        84 => 'CURLE_FTP_PRET_FAILED',
        84 => 'CURLE_FTP_PRET_FAILED',
        85 => 'CURLE_RTSP_CSEQ_ERROR',
        86 => 'CURLE_RTSP_SESSION_ERROR',
        87 => 'CURLE_FTP_BAD_FILE_LIST',
        88 => 'CURLE_CHUNK_FAILED'
    );    
}
