<?php
    $capabilities = array(
 
    'block/teameval_templates:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
 
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
 
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),

    'block/teameval_templates:viewtemplate' => array( 

        'captype' => 'read',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )

    ),

    'block/teameval_templates:deletetemplate' => array(

        'captype' => 'write',
        'riskbitmask' => RISK_DATALOSS,
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )

    )


);