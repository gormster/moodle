<?php

namespace teamevalreport_feedback;

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

	public static function update_states_parameters() {
		return new external_function_parameters([
			'cmid' => new external_value(PARAM_INT, 'cmid of teameval'),
			'states' => new external_multiple_structure(
				new external_single_structure([
					'questionid' => new external_value(PARAM_INT, 'id of question'),
					'markerid' => new external_value(PARAM_INT, 'user id of marker'),
					'targetid' => new external_value(PARAM_INT, 'user id of markee'),
					'state' => new external_value(PARAM_INT, '-1 for rescind, 0 for unset, 1 for approve')
				])
			)
		]);
	}

	public static function update_states_returns() {
		return null;
	}

	public static function update_states($cmid, $states) {

		$teameval = team_evaluation::from_cmid($cmid);

		global $USER;
		require_capability('local/teameval:invalidateassessment', $teameval->get_context(), $USER->id);

		foreach($states as $s) {
			$teameval->rescind_feedback_for($s['questionid'], $s['markerid'], $s['targetid'], $s['state']);
		}

		// now determine if we should release anyone's marks

		$rescinds = $teameval->all_rescind_states();

		$approves = [];
		foreach ($rescinds as $s) {
			$uid = $s->targetid;
			if (!isset($approves[$uid])) {
				$approves[$uid] = 0;
			}
			// either approve or reject counts	
			$approves[$uid]++;
		}

		error_log(print_r($approves, true));

		$qs = $teameval->get_questions();
		$qs = array_filter($qs, function($v) {
			return $v->question->has_feedback();
		});

		$requirednum = count($qs);

		error_log("Required num: $requirednum");

		foreach($approves as $uid => $n) {
			$p = count($teameval->teammates($uid));
			error_log("User $uid: P: $p, N: $n");
			if ($n >= $requirednum * $p) {
				$teameval->release_marks_for_user($uid);
				error_log("Markes released for $uid");
			}
		}

	}

	public static function update_states_is_allowed_from_ajax() {
		return true;
	}

}
