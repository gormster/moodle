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
			'maxval' => new external_value(PARAM_INT, 'maximum value'),
			'meanings' => new external_multiple_structure(
				new external_single_structure([
					'value' => new external_value(PARAM_INT, 'value meaning represents'),
					'meaning' => new external_value(PARAM_TEXT, 'meaning of value')
				])
			)
		]);
	}

	public static function update_question_returns() {
		return new external_value(PARAM_INT, 'id of question');
	}

	public static function update_question($cmid, $ordinal, $id, $title, $description, $minval, $maxval, $meanings) {
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

		$record->meanings = [];
		foreach ($meanings as $m) {
			$record->meanings[$m['value']] = $m['meaning'];
		}

		$record->meanings = json_encode($record->meanings);

		error_log(print_r($record->meanings,true));

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

	/* submit_response */

	public static function submit_response_parameters() {
		return new external_function_parameters([
			'cmid' => new external_value(PARAM_INT, 'cmid of teameval'),
			'id' => new external_value(PARAM_INT, 'id of question'),
			'marks' => new external_multiple_structure(
				new external_single_structure([
					'touser' => new external_value(PARAM_INT, 'userid of user being rated'),
					'value' => new external_value(PARAM_INT, 'selected value')	
				])
			)
		]);
	}

	public static function submit_response_returns() {
		return null;
	}

	public static function submit_response($cmid, $id, $marks) {
		global $DB, $USER;

		$teameval = new team_evaluation($cmid);

		if ($teameval->can_submit_response('likert', $id, $USER->id)) {
			$formdata = [];
			
			foreach($marks as $m) {
				$touser = $m['touser'];
				$value = $m['value'];
				$formdata[$touser] = $value;
			}

			$response = new response($id, $USER->id);
			$response->update_response($formdata);
		}
	}

	public static function submit_response_is_allowed_from_ajax() { return true; }

}

?>