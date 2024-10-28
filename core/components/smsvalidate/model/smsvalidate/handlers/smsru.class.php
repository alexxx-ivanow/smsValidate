<?
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once dirname(__FILE__) . '/interfaces/sendSmsInterface.php';

class smsRu implements sendSmsInterface {

	/** @var modX $modx */
    public $modx;
    /** @var array $config */
    public $config = [];

    /**
     * intisteleSms constructor.
     *
     * @param modX $modx
     * @param array $config
     */
	public function __construct(modX $modx, array $config = [])
    {
    	$this->modx = $modx;

    	$this->config = array_merge([            
            'api_key' => '',
            'method' => 'send',
        ], $config);
    }

    /**
     * отправка СМС в сервис
     *
     * @param string $phone
     * @param string $code
     *
     * @return bool
     */
    public function send($phone, $code)
    {   

        if(!$phone || !$code) {
            return false;
        }
        // приводим телефон к нужному формату
        $phone = str_replace(['+', '(', ')', '-', ' '], '', $phone);            
        
        // запрос в сервис
        $ch = curl_init('https://sms.ru/sms/' . $this->config['method']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'api_id' => $this->config['api_key'],
            'to' => $phone,
            'msg' => $code,            
            'json' => 1 // Для получения более развернутого ответа от сервера
        ]));
        $body = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($body);
        if ($json) { 
            $this->modx->log(1, print_r($json, 1));
            if ($json->status == "OK") {
                return true;
            } else {
                $this->modx->log(1, 'Запрос не выполнился. Код ошибки: ' . $json->status_code . ' Текст ошибки: ' . $json->status_text);
            }
        }        
        return false;
    }
}