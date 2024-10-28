<?

require_once dirname(__FILE__) . '/interfaces/sendSmsInterface.php';

class intisteleSms implements sendSmsInterface {

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
            'sms_login' => '',
            'sms_sender' => '',
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
     
        // создаем сигнатуру
        $params = [
            'timestamp' => time(),
            'login'     => $this->config['sms_login'],
            'phone'     => $phone,
            'sender'    => $this->config['sms_sender'],
            'text'      => $code
        ];
        ksort($params);
        reset($params);
        $sign = md5(implode($params) . $this->config['api_key']);
        
        // запрос в сервис
        $get = [
        	'login'  => $this->config['sms_login'],
        	'signature' => $sign,
        	'phone' => $phone,
        	'text' => $code,
        	'sender' => $this->config['sms_sender'],
        	'timestamp' => time(),
        ];
        
        $url = 'https://dashboard.intistele.com/external/get/' . $this->config['method'] . '.php?';
        $ch = curl_init($url . http_build_query($get));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $html = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($html, true);
        
        if($res[$phone]['error'] === '0') {
            return true;
        }
        return false;
    }
}