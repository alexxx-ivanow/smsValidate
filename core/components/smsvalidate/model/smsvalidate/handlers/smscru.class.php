<?
require_once dirname(__FILE__) . '/interfaces/sendSmsInterface.php';

class smscRu implements sendSmsInterface {

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
	public function __construct($modx, $config = [])
    {
    	$this->modx = $modx;

    	$this->config = array_merge([            
            'login' => '', // вводим логин
            'psw' => '', // вводим пароль
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
        $ch = curl_init('https://smsc.ru/rest/' .$this->config['method'] . '/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'login' => $this->config['login'],
            'psw' => $this->config['psw'],
            'phones' => $phone,
            'mes' => $code
        ]));
        $body = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($body, true);
        
        if ($json) { 
            if (!isset($json['error'])) {
                return true;
            } else {
                $this->modx->log(modX::LOG_LEVEL_ERROR, 'Запрос не выполнился. Код ошибки: ' . $json['error_code'] . ' Текст ошибки: ' . $json['error']);
            }
        }        
        return false;
    }
}