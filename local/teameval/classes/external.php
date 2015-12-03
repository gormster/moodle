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
        $settingsform = new \local_teameval\forms\settings_form;
        return $settingsform->external_parameters();
        
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'coursemodule id for the teameval'),
            'settings' => new external_single_structure([
                'enabled' => new external_value(PARAM_BOOL, 'is teameval enabled for this module'),
                'selfassessment' => new external_value(PARAM_BOOL, 'is self assessment enabled for this module'),
                'public' => new external_value(PARAM_BOOL, 'is the questionnaire for this teameval publicly available'),
                'fraction' => new external_value(PARAM_INT, 'how much does evaluation affect the final grade'),
                'noncompletionpenalty' => new external_value(PARAM_INT, 'how much does non completion of the questionnaire reduce final grade'),
                'deadline' => new external_value(PARAM_INT, 'timestamp - datetime of questionnaire deadline')
            ])
        ]);
    }

    public static function update_settings_returns() {
        return null;
    }

    public static function update_settings($form) {

        $settingsform = new \local_teameval\forms\settings_form();
        $settingsform->process_data($form);
        $settings = $settingsform->get_data();

        $settings->public = $settings->public ? true : false;
        $settings->enabled = $settings->enabled ? true : false;

        $settings->fraction = $settings->fraction / 100.0;
        $settings->noncompletionpenalty = $settings->noncompletionpenalty / 100.0;

        $teameval = new team_evaluation($settings->cmid);
        $teameval->update_settings($settings);
    }

    public static function update_settings_is_allowed_from_ajax() { return true; }

    /* questionnaire_set_order */

    public static function questionnaire_set_order_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'coursemodule id for the teameval'),
            'order' => new external_multiple_structure(
                new external_value(PARAM_INT, 'list of question IDs')
            )
        ]);
    }

    public static function questionnaire_set_order_returns() {
        return null;
    }

    public static function questionnaire_set_order($cmid, $order) {
        $teameval = new team_evaluation($cmid);
        $teameval->questionnaire_set_order($order);
    }

    public static function questionnaire_set_order_is_allowed_from_ajax() { return true; }

    /* report */

    public static function report_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'coursemodule id for the teameval'),
            'plugin' => new external_value(PARAM_PLUGIN, 'report plugin name')
        ]);
    }

    public static function report_returns() {
        return new external_single_structure([
            'html' => new external_value(PARAM_RAW, 'rendered HTML code for the report', VALUE_OPTIONAL),
            'template' => new external_value(PARAM_PATH, 'template name to be used for rendering report', VALUE_OPTIONAL),
            'data' => new external_value(PARAM_RAW, 'JSON encoded data to be used for rendering report', VALUE_OPTIONAL)
        ]);
    }

    public static function report($cmid, $plugin) {
        global $USER, $PAGE;

        $teameval = new team_evaluation($cmid);
        $teameval->set_report_plugin($plugin);
        $report = $teameval->get_report();

        $renderer = $PAGE->get_renderer("teamevalreport_$plugin");

        // Reports can optionally be templatable. If they are, return just the template and context data.
        if ($report instanceof \templatable) {
            $data = json_encode( $report->export_for_template($renderer) );
            if (method_exists($report, "template_name")) {
                $template = $report->template_name();
            } else {
                $template = "teamevalreport_$plugin/report";
            }

            return ['template' => $template, 'data' => $data];
        } else {
            $html = $renderer->render($report);
            return ['html' => $html];
        }

    }

    public static function report_is_allowed_from_ajax() { return true; }

}

?>