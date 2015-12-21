<?php

namespace teamevalquestion_comment;

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/local/teameval/lib.php");

use external_api;
use external_function_parameters;
use external_value;
use external_format_value;
use external_single_structure;
use external_multiple_structure;
use invalid_parameter_exception;
use context_module;
use stdClass;

use local_teameval\team_evaluation;

class external extends external_api {

    /* update_question */

    public static function update_question_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'cmid of teameval'),
            'ordinal' => new external_value(PARAM_INT, 'ordinal of question'),
            'id' => new external_value(PARAM_INT, 'id of question', VALUE_DEFAULT, 0),
            'title' => new external_value(PARAM_TEXT, 'title of question'),
            'description' => new external_value(PARAM_RAW, 'description of question'),
        ]);
    }

    public static function update_question_returns() {
        return new external_value(PARAM_INT, 'id of question');
    }

    public static function update_question($cmid, $ordinal, $id, $title, $description) {
        global $DB, $USER;

        $teameval = new team_evaluation($cmid);
        $transaction = $teameval->should_update_question("comment", $id, $USER->id);

        if ($transaction == null) {
            throw new moodle_exception("cannotupdatequestion", "local_teameval");
        }

        $record = ($id > 0) ? $DB->get_record('teamevalquestion_comment', array('id' => $id)) : new stdClass;

        $record->title = $title;
        $record->description = $description;

        if ($id > 0) {
            $DB->update_record('teamevalquestion_comment', $record);
        } else {
            $id = $DB->insert_record('teamevalquestion_comment', $record);
        }

        $teameval->update_question($transaction, "comment", $id, $ordinal);

        return $id;

    }

    public static function update_question_is_allowed_from_ajax() { return true; }


    /* submit_response */

    public static function submit_response_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'cmid of teameval'),
            'id' => new external_value(PARAM_INT, 'id of question'),
            'comments' => new external_multiple_structure(
                new external_single_structure([
                    'touser' => new external_value(PARAM_INT, 'id of target user'),
                    'comment' => new external_value(PARAM_RAW, 'comments made by user')
                ])
            )
        ]);
    }

    public static function submit_response_returns() {
        return null;
    }

    public static function submit_response($cmid, $id, $comments) {

        global $DB, $USER;

        $teameval = new team_evaluation($cmid);

        if ($teameval->can_submit_response('comment', $id, $USER->id)) {
            $question = new question($teameval, $id);
            $response = new response($teameval, $question, $USER->id);

            $formdata = [];

            foreach($comments as $c) {
                $touser = $c['touser'];
                $comment = $c['comment'];
                $formdata[$touser] = $comment;
            }

            $response->update_comments($formdata);
        }
    }

    public static function submit_response_is_allowed_from_ajax() { return true; }
}