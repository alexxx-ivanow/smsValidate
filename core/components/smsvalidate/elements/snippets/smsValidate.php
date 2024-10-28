<?php
if (!$modx->loadClass('SmsValidate', MODX_CORE_PATH . 'components/smsvalidate/model/smsvalidate/', false, true)) {
    return true;
}
if(isset($param))
    $scriptProperties['phoneField'] = $param;
    
if(isset($validate))
    $scriptProperties['validate'] = $validate;

$SmsValidate = new SmsValidate($modx, $scriptProperties);

$process = $SmsValidate->run($_REQUEST, $value);
if(!$process['success']) {
    $validator->addError($key, $process['message']);
}
return true;