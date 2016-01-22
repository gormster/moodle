<?php

namespace teamevalreport_feedback\output;

use renderer_base;
use stdClass;

class feedback_report implements \renderable, \templatable {

	protected $groups;

	protected $reports = [];

	public function __construct($groups, $questions) {

		$this->groups = $groups;

		foreach($groups as $gid => $group) {

			$this->reports[$gid] = [];

			foreach($questions as $q) {
				$reportinfo = new stdClass;
				$reportinfo->title = $q->question->get_title();
				$reportinfo->renderable = $q->question->render_for_report($gid);
				$reportinfo->renderername = 'teamevalquestion_' . $q->plugininfo->name;
				$this->reports[$gid][] = $reportinfo;
			}

		}

	}

	public function export_for_template(renderer_base $output) {
		global $PAGE;

		$c = new stdClass;

		$c->groups = [];

		foreach($this->groups as $gid => $group) {
			$g = new stdClass;
			$g->name = $group->name;
			$g->questions = [];

			foreach($this->reports[$gid] as $reportinfo) {
				$renderer = $PAGE->get_renderer($reportinfo->renderername);
				$q = new stdClass;
				$q->title = $reportinfo->title;
				$q->report = $renderer->render($reportinfo->renderable);
				$g->questions[] = $q;
			}

			$c->groups[] = $g;
		}

		return $c;
	}

}