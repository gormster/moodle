<?php

namespace local_teameval;

use external_api;
use external_function_parameters;
use external_value;
use external_format_value;
use external_single_structure;
use external_multiple_structure;
use invalid_parameter_exception;

require_once(dirname(dirname(__FILE__)) . '/lib.php');

class external extends external_api {

    /* get_settings */

    public static function get_settings_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'coursemodule id for the teameval')
        ]);
    }
    
    public static function get_settings_returns() {
        return new external_single_structure([
            'enabled' => new external_value(PARAM_BOOL, 'is teameval enabled for this module'),
            'public' => new external_value(PARAM_BOOL, 'is the questionnaire for this teameval publicly available'),
            'fraction' => new external_value(PARAM_FLOAT, 'how much does evaluation affect the final grade'),
            'noncompletionpenalty' => new external_value(PARAM_FLOAT, 'how much does non completion of the questionnaire reduce final grade'),
            'deadline' => new external_value(PARAM_INT, 'timestamp - datetime of questionnaire deadline')
        ]);
    }

    public static function get_settings($cmid) {
        //todo: this should 100% go through some kind of output thing. needs permissions checks, for starters.
        $teameval = new team_evaluation($cmid);
        return $teameval->get_settings();
    }

    public static function get_settings_is_allowed_from_ajax() { return true; }

    /* update_settings */

    public static function update_settings_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'coursemodule id for the teameval'),
            'settings' => new external_single_structure([
                'enabled' => new external_value(PARAM_BOOL, 'is teameval enabled for this module'),
                'public' => new external_value(PARAM_BOOL, 'is the questionnaire for this teameval publicly available'),
                'fraction' => new external_value(PARAM_FLOAT, 'how much does evaluation affect the final grade'),
                'noncompletionpenalty' => new external_value(PARAM_FLOAT, 'how much does non completion of the questionnaire reduce final grade'),
                'deadline' => new external_value(PARAM_INT, 'timestamp - datetime of questionnaire deadline')
            ])
        ]);
    }

    public static function update_settings_returns() {
        return null;
    }

    public static function update_settings($cmid, $settings) {
        $teameval = new team_evaluation($cmid);
        $teameval->update_settings($settings);
    }

    public static function update_settings_is_allowed_from_ajax() { return true; }

}

?>