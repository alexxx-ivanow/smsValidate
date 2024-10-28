<?php

return [
    'smsTest' => [
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'smsvalidate',
    ],
    'smsHandlerClass' => [
        'xtype' => 'textfield',
        'value' => 'smsRu',
        'area' => 'smsvalidate',
    ],
    'smsTimeLimit' => [
        'xtype' => 'textfield',
        'value' => '30',
        'area' => 'smsvalidate',
    ],
    'smsButtonRepeatClass' => [
        'xtype' => 'textfield',
        'value' => 'btn',
        'area' => 'smsvalidate',
    ],
];