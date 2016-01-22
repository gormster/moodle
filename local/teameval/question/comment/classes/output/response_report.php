<?php

namespace teamevalquestion_comment\output;

use teamevalquestion_comment;
use \local_teameval\team_evaluation;
use stdClass;

class response_report implements \renderable, \templatable {

	protected $teameval;

	protected $question;

	protected $response;

	protected $userid;

	public function __construct(team_evaluation $teameval, $userid, question $question, response $response) {
		$this->teameval = $teameval;
		$this->userid = $userid;
		$this->response = $response;
	}

	public function export_for_template(renderer_base $output) {
		$c = new stdClass;

		$c->title = $this->question->get_title();

		$teammates = $this->teameval->teammates($this->userid);
		$comments = [];
		foreach ($teammates as $userid => $user) {
			$comment = new stdClass;
			$comment->commenter = fullname($user);
			$comment->comment = $this->response->comment_on($userid);
			if ($userid == $this->userid) {
				$comment->self = true;
			}
			$comments[] = $comment;
		}
		$c->comments = $comments;

		return $c;
	}

}

?>