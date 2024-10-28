<?php
if (!$modx->loadClass('SmsValidate', MODX_CORE_PATH . 'components/smsvalidate/model/smsvalidate/', false, true)) {
    return true;
}

$SmsValidate = new SmsValidate($modx, $scriptProperties);
$SmsValidate->loadJs();

return true;