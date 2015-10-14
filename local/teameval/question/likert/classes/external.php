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
			'title' => new external_value(PARAM_TEXT, 'title of question'),
			'description' => new external_value(PARAM_RAW, 'description of question'),
			'minval' => new external_value(PARAM_INT, 'minimum value'),
			'maxval' => new external_value(PARAM_INT, 'maximum value')
		]);
	}

	public static function update_question_returns() {
		return new external_value(PARAM_INT, 'id of question');
	}

	public static function update_question($cmid, $ordinal, $id, $title, $description, $minval, $maxval) {
		global $DB, $USER;

		$teameval = new team_evaluation($cmid);
		$transaction = $teameval->should_update_question("likert", $id, $USER->id);

		if ($transaction == null) {
			throw new moodle_exception("cannotupdatequestion", "local_teameval");
		}

		//get or create the record
		$record = ($id > 0) ? $DB->get_record('teamevalquestion_likert', array('id' => $id)) : new stdClass;
		
		//update the values
		$record->title = $title;
		$record->description = $description;
		$record->minval = min(max(0, $minval), 1); //between 0 and 1
		$record->maxval = min(max(3, $maxval), 10); //between 3 and 10

		//save the record back to the DB
		if ($id > 0) {
			$DB->update_record('teamevalquestion_likert', $record);
		} else {
			$id = $DB->insert_record('teamevalquestion_likert', $record);
		}
		
		//finally tell the teameval we're done
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