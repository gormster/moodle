<?php

namespace teamevalquestion_comment\output;

use stdClass;
use renderable;
use templatable;
use renderer_base;

class opinion_readable_short implements renderable, templatable {

	protected $comment;

	public function __construct($comment) {
		$this->comment = $comment;
	}

	public function export_for_template(renderer_base $output) {
		$c = new stdClass;
		if (is_string($this->comment)) {
			if (strlen($this->comment) > 25) {
				$c->short = substr($this->comment, 0, 25) . '...';
				$c->comment = $this->comment;
			} else {
				$c->short = $this->comment;
			}
		}
		//since the uniqid helper doesn't actually work...
		$c->uniqid = uniqid();
		return $c;
	}

}