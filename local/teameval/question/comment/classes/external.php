<?php

namespace teamevalquestion_comment;

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_function_parameters;
use external_value;
use external_format_value;
use external_single_structure;
use external_multiple_structure;
use invalid_parameter_exception;
use context_module;
use stdClass;
use coding_exception;
use moodle_exception;

use local_teameval;
use local_teameval\team_evaluation;

class external extends external_api {

    /* update_question */

    public static function update_question_parameters() {
        return new external_function_parameters([
            'teamevalid' => new external_value(PARAM_INT, 'id of teameval'),
            'formdata' => new external_value(PARAM_RAW, 'encoded form data')
        ]);
    }

    public static function update_question_returns() {
        return new external_value(PARAM_INT, 'id of question');
    }

    public static function update_question($teamevalid, $formdata) {
        global $DB, $USER, $PAGE;

        $context = team_evaluation::guard_capability($teamevalid, ['local/teameval:createquestionnaire']);
        $PAGE->set_context($context);

        $teameval = new team_evaluation($teamevalid);

        $parsedformdata = [];
        parse_str($formdata, $parsedformdata);
        $form = new forms\edit_form(null, null, 'post', '', null, true, $parsedformdata);

        if (!$form->is_submitted()) {
            throw new moodle_exception('formnotsubmitted', '', '', $parsedformdata);
        }

        $data = $form->get_data();
        if ($form->validate_defined_fields(true) == false) {
            throw new moodle_exception("forminvalid", '', '', $parsedformdata);
        }

        $id = $data->id;

        $any_response_submitted = false;
        if ($id > 0) {
            $question = new question($teameval, $id);
            $any_response_submitted = $question->any_response_submitted();
        }

        $transaction = $teameval->should_update_question("comment", $id, $USER->id);

        if ($transaction == null) {
            throw new moodle_exception("cannotupdatequestion", "local_teameval");
        }

        $record = ($id > 0) ? $DB->get_record('teamevalquestion_comment', array('id' => $id)) : new stdClass;

        $record->title = $data->title;
        $record->description = $data->description['text'];
        if ($any_response_submitted == false) {
            $record->anonymous = $data->anonymous;
            $record->optional = $data->optional;
        }

        if ($id > 0) {
            $DB->update_record('teamevalquestion_comment', $record);
        } else {
            $transaction->id = $DB->insert_record('teamevalquestion_comment', $record);
        }

        $teameval->update_question($transaction, $data->ordinal);

        return $transaction->id;

    }

    /* delete_question */

    public static function delete_question_parameters() {
        return new external_function_parameters([
            'teamevalid' => new external_value(PARAM_INT, 'id of teameval'),
            'id' => new external_value(PARAM_INT, 'id of question')
        ]);
    }

    public static function delete_question_returns() {
        return null;
    }

    public static function delete_question($teamevalid, $id) {
        global $USER, $DB;
        
        team_evaluation::guard_capability($teamevalid, ['local/teameval:createquestionnaire']);

        $teameval = new team_evaluation($teamevalid);

        $transaction = $teameval->should_delete_question('comment', $id, $USER->id);

        if ($transaction) {
            $DB->delete_records('teamevalquestion_comment', ['id' => $id]);
            $DB->delete_records('teamevalquestion_comment_res', ['questionid' => $id]);

            $teameval->delete_question($transaction);
        } else {
            throw required_capability_exception($teameval->get_context(), 'local/teameval:createquestionnaire', 'nopermissions');
        }
    }

    /* submit_response */

    public static function submit_response_parameters() {
        return new external_function_parameters([
            'teamevalid' => new external_value(PARAM_INT, 'id of teameval'),
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

    public static function submit_response($teamevalid, $id, $comments) {
        global $DB, $USER;

        team_evaluation::guard_capability($teamevalid, ['local/teameval:submitquestionnaire'], ['doanything' => false]);

        $teameval = new team_evaluation($teamevalid);

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
}
