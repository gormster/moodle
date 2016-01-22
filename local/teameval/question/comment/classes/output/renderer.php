<?php

namespace teamevalquestion_comment\output;

class renderer extends \plugin_renderer_base {

	public function render_response_report(response_report $report) {
		$data = $report->export_for_template($this);
        return parent::render_from_template('teamevalquestion_comment/response_report', $data);
	}

	public function render_question_report(question_report $report) {
		$data = $report->export_for_template($this);
        return parent::render_from_template('teamevalquestion_comment/question_report', $data);
	}

}