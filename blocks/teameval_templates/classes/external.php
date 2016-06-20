<?php

namespace block_teameval_templates;

use external_api;
use external_function_parameters;
use external_value;
use external_format_value;
use external_single_structure;
use external_multiple_structure;
use invalid_parameter_exception;

use local_teameval\team_evaluation;
use stdClass;

class external extends external_api {

	public static function update_title_parameters() {
		return new external_function_parameters([
			'id' => new external_value(PARAM_INT, 'id of team eval'),
			'title' => new external_value(PARAM_RAW, 'title for team eval template')
		]);
	}

	public static function update_title_returns() {
		return null;
	}

	public static function update_title($id, $title) {
		require_login();

		$teameval = new team_evaluation($id);

		require_capability('local/teameval:createquestionnaire', $teameval->get_context());

		$r = new stdClass;
		$r->title = $title;
		$teameval->update_settings($r);
	}

	public static function update_title_is_allowed_from_ajax() { return true; }

}