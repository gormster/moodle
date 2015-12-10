<?php

$functions = [
    
    'local_teameval_get_settings' => [
        
        'classname'     => 'local_teameval\external',
        'methodname'    => 'get_settings',
        'type'          => 'read'
        
    ],
    
    'local_teameval_update_settings' => [
        
        'classname'     => 'local_teameval\external',
        'methodname'    => 'update_settings',
        'type'          => 'write'
        
    ],

    'local_teameval_questionnaire_set_order' => [

        'classname'     => 'local_teameval\external',
        'methodname'    => 'questionnaire_set_order',
        'type'          => 'write'

    ],

    'local_teameval_report' => [
    
        'classname'     => 'local_teameval\external',
        'methodname'    => 'report',
        'type'          => 'read'

    ],

    'local_teameval_release' => [

        'classname'     => 'local_teameval\external',
        'methodname'    => 'release',
        'type'          => 'write'

    ]
    
]
    
?>