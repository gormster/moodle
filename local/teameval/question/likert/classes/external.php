<?php

namespace teamevalquestion_likert;

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

use question;
use response;

class external extends external_api {

	/* update_question */

	public static function update_question_parameters() {
		return new external_function_parameters([
			'cmid' => new external_value(PARAM_INT, 'cmid of teameval'),
			'ordinal' => new external_value(PARAM_INT, 'ordinal of question'),
			'id' => new external_value(PARAM_INT, 'id of question', VALUE_DEFAULT, 0),
			'test' => new external_value(PARAM_TEXT, 'test string')
		]);
	}

	public static function update_question_returns() {
		return new external_value(PARAM_INT, 'id of question');
	}

	public static function update_question($cmid, $ordinal, $id, $test) {
		global $DB, $USER;

		$teameval = new team_evaluation($cmid);
		$transaction = $teameval->should_update_question("likert", $id, $USER->id);

		if ($transaction == null) {
			throw new moodle_exception("cannotupdatequestion", "local_teameval");
		}

		if ($id > 0) {
			$record = $DB->get_record('teamevalquestion_likert', array('id' => $id));
			$record->test = $test;
			$DB->update_record('teamevalquestion_likert', $record);
		} else {
			$record = new stdClass;
			$record->test = $test;
			$id = $DB->insert_record('teamevalquestion_likert', $record);
		}
		
		$teameval->update_question($transaction, "likert", $id, $ordinal);

		return $id;

	}

	public static function update_question_is_allowed_from_ajax() { return true; }

	/* delete_question */

	public static function delete_question_parameters() {
		return new external_function_parameters([
			'cmid' => new external_value(PARAM_INT, 'cmid of teameval'),
			'id' => new external_value(PARAM_INT, 'id of question')
		]);
	}

	public static function delete_question_returns() {
		return null;
	}

	public static function delete_question($cmid, $id) {
		global $DB, $USER;

		$teameval = new team_evaluation($cmid);

		$transaction = $teameval->should_delete_question("likert", $id, $USER->id);
		if ($transaction == null) {
			throw new moodle_exception("cannotupdatequestion", "local_teameval");
		}

		$DB->delete_records('teamevalquestion_likert', array('id' => $id));

		$teameval->delete_question($transaction, "likert", $id);
	}

	public static function delete_question_is_allowed_from_ajax() { return true; }

}

?>