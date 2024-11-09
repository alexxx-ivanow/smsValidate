<?php

return [
    'smsvalidate.sms_test' => [
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'smsvalidate',
    ],
    'smsvalidate.sms_handler_class' => [
        'xtype' => 'textfield',
        'value' => 'smsRu',
        'area' => 'smsvalidate',
    ],
    'smsvalidate.sms_time_limit' => [
        'xtype' => 'textfield',
        'value' => '30',
        'area' => 'smsvalidate',
    ],
    'smsvalidate.sms_button_repeat_class' => [
        'xtype' => 'textfield',
        'value' => 'btn',
        'area' => 'smsvalidate',
    ],
    'smsvalidate.sms_code_length' => [
        'xtype' => 'textfield',
        'value' => '6',
        'area' => 'smsvalidate',
    ],
];