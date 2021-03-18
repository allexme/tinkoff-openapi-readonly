<?php
/* 
 * Тинькофф Инвестиции OpenAPI - Proxy & Readonly Token
 * 
 * Описание и принцип работы:
 * В API Тинькофф Инвестиций на данный момент отсутствует возможность разграничивать права доступа для токенов. Это является проблемой для тех, кто хотел бы пользоваться различными сервисами, например сервисами статистики. 
 * Данный скрипт является решением данной проблемы, поскольку при его использовании торговый токен находится на вашем сервере, и вы контролируете все операции. По запросу сервиса скрипт подгружает данные из вашего аккаунта API Тинькофф, после чего отправляет их в сервис статистики.
 * Общение между вашим сервером и сервисом статистики защищено отдельным токеном. В контексте сервиса, для которого создавался данный скрипт, токен можно получить в настройках (https://allex.me/invest/settings).
 * 
 * Создано для сервиса статистики Тинькофф Инвестиций:
 * 📌 О сервисе: https://allex.me/invest/help
 * 📌 О боте: https://allex.me/invest/help_bot
 * 📌 Обсуждение в Telegram: @TinkoffInvestStatChat
 * 
 * @author allex
 * @version 1.0
 * @url https://github.com/allexme/tinkoff-openapi-readonly
 * 
 */

/* Укажите торговый API Token */
define('_TINKOFF_API_TOKEN', "");

/* Укажите Token из настроек сервиса */
define('_SERVICE_API_TOKEN', "");

if (_TINKOFF_API_TOKEN == '' || _SERVICE_API_TOKEN == '') {
	echo 'See code...';exit;
}

class TIProxyClient {
    
    private $apiToken;
    private $serviceApiToken;
    private $url="https://api-invest.tinkoff.ru/openapi";
    public $brokerAccountId = null;
    
    function __construct($apiToken, $serviceApiToken) {
        $this->apiToken=$apiToken;
        $this->serviceApiToken=$serviceApiToken;
        if (!empty($_GET['brokerAccountId'])) {
            $this->brokerAccountId = intval($_GET['brokerAccountId']);
        }
        //Проверим авторизацию
        $this->_testAuth();
    }
    
    private function _testAuth(){
        $tkn=$this->getBearerToken();
        if (!$tkn || $tkn!=$this->serviceApiToken) {
            $this->_401();
        }
    }
    
    public function _request($action, $method, $params=array(), $postFields=null) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->url.$action);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json',
            'Authorization: Bearer '.$this->apiToken,
        ));
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        if (count($params)>0) {
            curl_setopt($curl, CURLOPT_URL, $this->url.$action.'?'.http_build_query($params));
        }
        if ($method!=="GET") {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $return = curl_exec($curl); 
        //result
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            $error_message='';
            switch ($httpCode) {
                case 401:
                    $error_message = "Authorization error";
                    break;
                case 429:
                    $error_message = "Too Many Requests";
                    break;
                case 500:
                    $error_message = "Order Not Available (500)";
                    break;
                default:
                    $error_message = "Unkown error";
                    break;
            }
            $curlError = curl_error($curl);
            
            //save log
            $logToFile=date("Y-m-d H:i:s")." / ".$_SERVER['REQUEST_URI']." / ".$httpCode." / ".$curlError." / ".$error_message." / ".$this->url . $action."\n";
            if (!empty($params)) {
                $logToFile.="params: ".print_R($params,1)."\n";
            }
            if (!empty($postFields)) {
                $logToFile.="postFields: ".print_R($postFields,1)."\n";
            }
            $logToFile.="return: ".$return."\n";
            file_put_contents(__DIR__."/".basename(__FILE__,".php").".log", $logToFile, FILE_APPEND);
            exit;
        }
        curl_close($curl);
        return $return;
    }

    /**
     * Get header Authorization
     * */
    private function getAuthorizationHeader(){
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }
    
    /**
     * get access token from header
     * */
    private function getBearerToken() {
        $headers = $this->getAuthorizationHeader();
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/ui', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
    
    private function _401() {
        header($_SERVER['SERVER_PROTOCOL']." 401 Authorization error");
        exit;
    }
    
    public function _404() {
        header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
        exit;
    }
    
}

//Инициализация
$client=new TIProxyClient(_TINKOFF_API_TOKEN, _SERVICE_API_TOKEN);
$action=trim($_GET['action']);
$method=$_SERVER['REQUEST_METHOD'];
if ($method!="GET") { //в текущей реализации доступен только метод "GET", что и проверяем.
    $client->_404();
}
//Поехали
switch ($method.$action) {

    /** Функции aналоги в Tinkoff API **/ 
    
    //Список доступных аккаунтов
    case "GET/user/accounts":
        echo $client->_request("/user/accounts", $method);
        break;

    //Получение баланса счета
    case "GET/portfolio/currencies":
        if (!$client->brokerAccountId) $client->_404();
        echo $client->_request("/portfolio/currencies", $method, array('brokerAccountId' => $client->brokerAccountId));
        break;

    //Получение текущего портфолио (список бумаг в портфеле)
    case "GET/portfolio":
        if (!$client->brokerAccountId) $client->_404();
        echo $client->_request("/portfolio", $method, array('brokerAccountId' => $client->brokerAccountId));
        break;

    //Получение стакана по FIGI
    case "GET/market/orderbook":
        $figi=trim($_GET['figi']);
        $depth=intval($_GET['depth']);
        if ($depth<1) { $depth=1; }
        if ($depth>20) { $depth=20; }
        echo $client->_request("/market/orderbook", $method, array(
            'figi' => $figi,
            'depth' => $depth
        ));
        break;
    
    //Получение списка заявок
    case "GET/orders":
        //Разрешить (true) или запретить (false). В сервисе статистики используется для вывода частично исполненных заявок.
        $showOrders = true;
        if ($showOrders) {
            echo $client->_request("/orders", $method);
        } else {
            echo json_encode(array(
                'trackingId' => -1,
                'payload' => array(),
                'status' => 'Ok'
            ));
        }
        break;
    
    //Получение списка операций за период
    case "GET/operations":
        if (!$client->brokerAccountId) $client->_404();
        if (empty($_GET['from']) || empty($_GET['to'])) $client->_404();
        $fromDate = new DateTime($_GET['from']);
        if ($fromDate->format("c")!=$_GET['from']) $client->_404();
        $toDate = new DateTime($_GET['to']);
        if ($toDate->format("c")!=$_GET['to']) $client->_404();
        $figi=(!empty($_GET['figi']) ? trim($_GET['figi']) : null);
        $ret=$client->_request("/operations", $method, array(
            "brokerAccountId" => $client->brokerAccountId,
            "from" => $fromDate->format("c"),
            "to" => $toDate->format("c"),
            "figi" => $figi
        ));
        //Удалить операции с информацией о комиссии для снижения трафика
        if (isset($_GET['clean_comission'])) {
            $ret=json_decode($ret,true);
            $needSort=false;
            foreach ($ret['payload']['operations'] as $k=>$r) {
                if ($r['operationType']=='BrokerCommission') {
                    unset($ret['payload']['operations'][$k]);
                    $needSort=true;
                }
            }
            if ($needSort) {
                $ret['payload']['operations']=array_values($ret['payload']['operations']);
            }
            echo json_encode($ret);
        } else {
            echo $ret;
        }
        break;
    
    /** Функции для упрощения взаимодействия с сервисом статистики **/
    
    //Получение баланса и портфолио одним запросом
    case "GET/portfolio_summary":
        if (!$client->brokerAccountId) $client->_404();
        $ret=array(
            'currencies'=>$client->_request("/portfolio/currencies", $method, array('brokerAccountId' => $client->brokerAccountId)),
            'portfolio'=>$client->_request("/portfolio", $method, array('brokerAccountId' => $client->brokerAccountId))
        );
        echo json_encode($ret);
        break;
	
    //Отбой 
    default:
        $client->_404();
        break;
}

?>