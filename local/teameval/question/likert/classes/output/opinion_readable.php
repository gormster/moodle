<?php

namespace teamevalquestion_likert\output;

use stdClass;
use renderable;
use templatable;
use renderer_base;
use teamevalquestion_likert;

class opinion_readable implements renderable, templatable {

	protected $val;
	protected $max;

	public function __construct($val, $max) {
        $this->val = $val;
        $this->max = $max;
	}

	public function export_for_template(renderer_base $output) {
		$c = new stdClass;
		$c->val = $this->val;
		$c->max = $this->max;
		return $c;
	}

}