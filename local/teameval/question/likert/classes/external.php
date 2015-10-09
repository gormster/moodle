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

	/* update */

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

		$context = context_module::instance($cmid);
        self::validate_context($context);
		require_capability('local/teameval:createquestionnaire', $context);

		if ($id > 0) {
			$record = $DB->get_record('teamevalquestion_likert', array('id' => $id));
			$record->test = $test;
			$DB->update_record('teamevalquestion_likert', $record);
		} else {
			$record = new stdClass;
			$record->test = $test;
			$id = $DB->insert_record('teamevalquestion_likert', $record);
		}

		$teameval = new team_evaluation($cmid);
		$teameval->update_question("likert", $id, $ordinal);

		return $id;

	}

	public static function update_question_is_allowed_from_ajax() { return true; }

}

?>