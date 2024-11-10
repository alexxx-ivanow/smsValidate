<?php

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

    /** @var int $codeLength */
    public $codeLength;

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

        if($this->modx->getOption('smsvalidate.sms_test', null, false, true)) {
            $this->isTest = true;
        }
        $this->timeLimit = $this->modx->getOption('smsvalidate.sms_time_limit', null, 30, true);
        $this->buttonClass = $this->modx->getOption('smsvalidate.sms_button_repeat_class', null, '', true);
        $this->codeLength = $this->modx->getOption('smsvalidate.sms_code_length', null, 6, true);

        if($this->codeLength > 10) {
            $this->codeLength = 10;
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[sms_validate] в настройках выбрана длина кода, превышающую допустимый лимит');
        }

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
    public function getRequiredFields($string)
    {
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
     * @param string $length
     */
    public function generateCode($length)
    {
        $max = '';
        for($i = 0; $i < $length; $i++){
            $max .= '9';
        }
        return (string)random_int(1 * 10 ** ($length - 1), (int)$max);
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

        $class = $this->modx->getOption('smsvalidate.sms_handler_class', null, 'smsRu', true);

        if ($class != 'smsRu') {
            $this->loadCustomClasses();
        }

        if (!class_exists($class)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[sms_validate] класс СМС-провайдера "' . $class . '" не найден');
            $class = 'smsRu';
        }

        $this->handler = new $class($this->modx, []);
        if (!($this->handler instanceof sendSmsInterface)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[sms_validate] не получается инициализировать класс СМС-провайдера: "' . $class . '"');

            return false;
        }

        return true;
    }

    /**
     * отправка СМС через класс провайдера
     * @param string $phone
     * @param string $code
     */
    public function send($phone, $code)
    {
        if (!is_object($this->handler) || !($this->handler instanceof sendSmsInterface)) {
            if (!$this->loadHandler()) {
                return false;
            }
        }

        return $this->handler->send($phone, $code);
    }

    /**
     * экранирование входящего массива
     * @param array $array
     */
    public function arraySanitise($array)
    {
        $output = [];
        foreach($array as $key => $item) {
            $output[$key] = strip_tags($item);
        }
        return $output;
    }

    /**
     * менеджер валидации телефона по СМС
     * @param array $request
     * @param string $value
     */
    public function run($request, $value)
    {

        $success = false;
        $message = '';

        $request = $this->arraySanitise($request);

        // если не заполнены обязательные поля - выходим, нет смысла отправлять СМС
        foreach($request as $key => $req) {
            if($req === '' && in_array($key, $this->requiredFields)) {
                return [
                    'success' => true,
                    'message' => '',
                ];
            }
        }

        // проверка заполненного поля телефона: если нет поля или оно не обязательное - отправляем форму без СМС
        if(!$this->phoneField || !isset($request[$this->phoneField])) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, $this->modx->lexicon('sms_validate_empty_phone_field'));
            return [
                'success' => true,
                'message' => '',
            ];
        }

        // логика проверки СМС
        if(!isset($_SESSION['sms_validate_sms_code_' . $request['af_action']])
            || (isset($request['repeat_sms']) && $request['repeat_sms'] == 1 && time() - $_SESSION['sms_validate_exec_time_' . $request['af_action']] >= $this->timeLimit)) {

            $_SESSION['sms_validate_sms_code_' . $request['af_action']] = $this->generateCode($this->codeLength);
            $_SESSION['sms_validate_exec_time_' . $request['af_action']] = time();

            // отправка СМС на сервис
            if($this->isTest) { // тестовый режим
                $message = $this->modx->lexicon('sms_validate_test_mode', ['code' => $_SESSION['sms_validate_sms_code_' . $request['af_action']]]);
                $this->modx->log(modX::LOG_LEVEL_ERROR, $_SESSION['sms_validate_sms_code_' . $request['af_action']]);
            } else {

                if(!$this->send($request[$this->phoneField], $_SESSION['sms_validate_sms_code_' . $request['af_action']])) {
                    $message = $this->modx->lexicon('sms_validate_service_error');
                } else {
                    if((time() - $_SESSION['sms_validate_exec_time_' . $request['af_action']]) < $this->timeLimit) {
                        $message = $this->modx->lexicon('sms_validate_send_limit_seconds', ['limit' => $this->timeLimit - (time() - $_SESSION['sms_validate_exec_time_' . $request['af_action']])]);
                    } else {
                        $message = $this->modx->lexicon('sms_validate_send_with_repeat') . '<button class="' . $this->buttonClass . ' jsSmsRepeat">' . $this->modx->lexicon('sms_validate_button_repeat_title') . '</button>';
                    }
                }
            }

        }
        elseif ($value == '' && $_SESSION['sms_validate_sms_code_' . $request['af_action']]) {

            if((time() - $_SESSION['sms_validate_exec_time_' . $request['af_action']]) < $this->timeLimit) {
                $message = $this->modx->lexicon('sms_validate_send_limit_seconds', ['limit' => $this->timeLimit - (time() - $_SESSION['sms_validate_exec_time_' . $request['af_action']])]);
            } else {
                $message = $this->modx->lexicon('sms_validate_send_with_repeat') . '<button class="' . $this->buttonClass . ' jsSmsRepeat">' . $this->modx->lexicon('sms_validate_button_repeat_title') . '</button>';
            }

        }
        elseif ($_SESSION['sms_validate_sms_code_' . $request['af_action']] && $value != $_SESSION['sms_validate_sms_code_' . $request['af_action']]) {

            if(time() - $_SESSION['sms_validate_exec_time_' . $request['af_action']] < $this->timeLimit) {
                $message = $this->modx->lexicon('sms_validate_send_incorrect_limit_seconds', ['limit' => $this->timeLimit - (time() - $_SESSION['sms_validate_exec_time_' . $request['af_action']])]);
            } else {
                $message = $this->modx->lexicon('sms_validate_send_incorrect_with_repeat') . '<button class="' . $this->buttonClass . ' jsSmsRepeat">' . $this->modx->lexicon('sms_validate_button_repeat_title') . '</button>';
            }

        } elseif($value == $_SESSION['sms_validate_sms_code_' . $request['af_action']]) {

            unset($_SESSION['sms_validate_sms_code_' . $request['af_action']]);
            unset($_SESSION['sms_validate_exec_time_' . $request['af_action']]);
            $success = true;

        }
        $output = [
            'success' => $success,
            'message' => $message,
        ];

        return $output;
    }
}
