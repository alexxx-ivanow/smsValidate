<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

class SmsValidate
{
    /** @var modX $modx */
    public $modx;

    /** @var array $config */
    public $config;
    
    /** @var array $requiredFields */
    public $requiredFields = [];

    /** @var string $phoneField */
    public $phoneField = '';

    /** @var int $timeLimit */
    public $timeLimit;

    /** @var string $buttonClass */
    public $buttonClass;

    /** @var bool $isTest */
    public $isTest = false;

     /** @var sendSmsInterface $handler */
    public $handler;

    /**
     * @param modX $modx
     * @param array $config
     */
    function __construct(modX &$modx, array $config = array())
    {
        $this->modx =& $modx;
        $this->modx->lexicon->load('smsvalidate:default');

        $assetsUrl = $this->modx->getOption('smsvalidate_assets_path', $config,
            MODX_ASSETS_URL . 'components/smsvalidate/');

        if($this->modx->getOption('smsTest', null, false, true)) {
        	$this->isTest = true;
        }
        $this->timeLimit = $this->modx->getOption('smsTimeLimit', null, 30, true);
        $this->buttonClass = $this->modx->getOption('smsButtonRepeatClass', null, '', true);

        $this->requiredFields = $this->getRequiredFields($config['validate']);
        $this->phoneField = $config['phoneField'] ?: 'phone';        

        $corePath = MODX_CORE_PATH . 'components/smsvalidate/model/smsvalidate/';
        $this->config = array_merge([
            'handlerPath' => $corePath . 'handlers/',
            'assetsUrl' => $assetsUrl,
            'frontend_js' => '[[+assetsUrl]]js/default.js',
        ], $config);    
    }

    /**
     * Загрузка js-скрипта
     */
    public function loadJs()
    {        

        if ($js = trim($this->config['frontend_js'])) {
            if (preg_match('/\.js/i', $js)) {
                $this->modx->regClientScript(str_replace('[[+assetsUrl]]', $this->config['assetsUrl'], $js));
            }
        }
    }

    /**
     * Вычисляем обязательные поля формы
     * @param string $string
     */
    public function getRequiredFields($string) {
    	$output = [];
    	$string_arr = explode(',', $string);
    	foreach ($string_arr as $key => $value) {

    		$tmp = explode(':', $value);
    		$tmp_name = array_shift($tmp);
    		if(in_array('required', $tmp)) {
    			$output[] = $tmp_name;
    		}    		
    	}
    	return $output;
    }

    /**
     * Генерация уникального кода для СМС
     * @param integer $length
     */
    public function generateCode($length = 6)
    {
        $max = '';
        for($i = 0; $i < $length; $i++){
            $max .= '9';
        }
        return random_int(1 * 10 ** ($length - 1), (int)$max);
    }

    /**
     * подключаем кастомные классы отправки СМС
     */
    public function loadCustomClasses()
    {
        $files = scandir($this->config['handlerPath']);
        foreach ($files as $file) {
            if (preg_match('/.*?\.class\.php$/i', $file)) {
                include_once($this->config['handlerPath'] . '/' . $file);
            }
        }    
    }

    /**
     * подключаем класс СМС-провайдера
     */
    public function loadHandler()
    {
        require_once dirname(__FILE__) . '/handlers/smsru.class.php';
        
        $class = $this->modx->getOption('smsHandlerClass', null, 'smsRu', true);

        if ($class != 'smsRu') {
            $this->loadCustomClasses();
        }

        if (!class_exists($class)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'SMS handler class "' . $class . '" not found.');
            $class = 'smsRu';
        }

        $this->handler = new $class($this->modx, []);
        if (!($this->handler instanceof sendSmsInterface)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not initialize SMS handler class: "' . $class . '"');

            return false;
        }

        return true;
    }

    // отправка СМС
    public function send($phone, $code)
    {   
        if (!is_object($this->handler) || !($this->handler instanceof sendSmsInterface)) {
            if (!$this->loadHandler()) {
                return false;
            }
        }

        return $this->handler->send($phone, $code);
    }
    
    // экранирование входящего массива
    public function arraySanitise($array) 
    {
        $output = [];
        foreach($array as $key => $item) {
            $output[$key] = strip_tags($item);
        }
        return $output;
    }

    // менеджер валидации телефона по СМС
    public function run($request, $value)
    {
    	
        $success = false;
        $message = '';

        $request = $this->arraySanitise($request);

        // если не заполнены обязательные поля - выходим
        foreach($request as $key => $req) {
        	if($req === '' && in_array($key, $this->requiredFields)) {
        		return [
	                'success' => true,
	                'message' => '',
	            ];
        	}
        }

        // проверка заполненного поля телефона
        if(!$this->phoneField || !isset($request[$this->phoneField])) {
        	return [
                'success' => true,
                'message' => '',
            ];
        }

        // логика проверки СМС
        if(!isset($_SESSION['sms_code']) 
          || (isset($request['repeat_sms']) && $request['repeat_sms'] == 1 && time() - $_SESSION['exec_time'] >= $this->timeLimit)) {

            $_SESSION['sms_code'] = $this->generateCode();
            $_SESSION['exec_time'] = time();

            // отправка СМС на сервис
            if($this->isTest) { // тестовый режим
            	$message = $this->modx->lexicon('sms_validate_test_mode', ['code' => $_SESSION['sms_code']]);
            	$this->modx->log(1, $_SESSION['sms_code']);
            } else {

            	if(!$this->send($request[$this->phoneField], $_SESSION['sms_code'])) {                	
                    $message = $this->modx->lexicon('sms_validate_service_error_l');
                } else {
                    if((time() - $_SESSION['exec_time']) < $this->timeLimit) {
                        $message = $this->modx->lexicon('sms_validate_send_limit_seconds', ['limit' => $this->timeLimit - (time() - $_SESSION['exec_time'])]);
                    } else {
                        $message = $this->modx->lexicon('sms_validate_send_with_repeat') . '<button class="' . $this->buttonClass . ' jsSmsRepeat">' . $this->modx->lexicon('sms_validate_button_repeat_title') . '</button>';
                    }
                }
            }            

        }
        elseif ($value == '' && $_SESSION['sms_code']) {

            if((time() - $_SESSION['exec_time']) < $this->timeLimit) {
                $message = $this->modx->lexicon('sms_validate_send_limit_seconds', ['limit' => $this->timeLimit - (time() - $_SESSION['exec_time'])]);
            } else {
                $message = $this->modx->lexicon('sms_validate_send_with_repeat') . '<button class="' . $this->buttonClass . ' jsSmsRepeat">' . $this->modx->lexicon('sms_validate_button_repeat_title') . '</button>';
            }

        }
        elseif ($_SESSION['sms_code'] && $value != $_SESSION['sms_code']) {  

            if(time() - $_SESSION['exec_time'] < $this->timeLimit) {
                $message = $this->modx->lexicon('sms_validate_send_incorrect_limit_seconds', ['limit' => $this->timeLimit - (time() - $_SESSION['exec_time'])]);
            } else {
                $message = $this->modx->lexicon('sms_validate_send_incorrect_with_repeat') . '<button class="' . $this->buttonClass . ' jsSmsRepeat">' . $this->modx->lexicon('sms_validate_button_repeat_title') . '</button>';
            }

        } else {

            unset($_SESSION['sms_code']);
            unset($_SESSION['exec_time']);
            $success = true;

        }
        $output = [
            'success' => $success,
            'message' => $message,
        ];
        
        return $output;
    }
}
