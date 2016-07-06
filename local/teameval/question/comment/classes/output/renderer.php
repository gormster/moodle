<?php

namespace teamevalquestion_comment\output;

use plugin_renderer_base;

class renderer extends plugin_renderer_base {

	public function render_response_report(response_report $report) {
		$data = $report->export_for_template($this);
        return parent::render_from_template('teamevalquestion_comment/response_report', $data);
	}

	public function render_question_report(question_report $report) {
		$data = $report->export_for_template($this);
        return parent::render_from_template('teamevalquestion_comment/question_report', $data);
	}

	public function render_feedback_readable(feedback_readable $report) {
		$data = $report->export_for_template($this);
        return parent::render_from_template('teamevalquestion_comment/feedback_readable', $data);
	}

	public function render_opinion_readable_short(opinion_readable_short $report) {
		$data = $report->export_for_template($this);
        return parent::render_from_template('teamevalquestion_comment/opinion_readable_short', $data);
	}

}