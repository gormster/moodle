<?php

namespace teamevalquestion_comment\output;

use user_picture;
use stdClass;
use local_teameval\team_evaluation;
use teamevalquestion_comment\question;
use teamevalquestion_comment\response;

class question_report implements \renderable, \templatable {

	protected $users;

	protected $responses = [];

	public function __construct(team_evaluation $teameval, question $question, $groupid) {

		if ($groupid != null) {
			$this->users = $teameval->group_members($groupid);
		} else {
			$this->users = $teameval->get_evaluation_context()->marking_users();
		}

		foreach($this->users as $marker) {
			$response = new response($teameval, $question, $marker->id);
			$this->responses[$marker->id] = $response;
		}

	}

	public function export_for_template(\renderer_base $output) {

		$c = new stdClass;

		$c->responses = [];

		foreach($this->responses as $markerid => $response) {
			$marker = $this->users[$markerid];

			foreach($this->users as $markee) {
				if (! isset($comments[$markee->id])) {
					$comments[$markee->id] = [];
				}

				$comment = new stdClass;
				$comment->from = fullname($marker);
				$comment->comment = $response->comment_on($markee->id);

				//if self assessment, stick it on the front of the array, otherwise tack it on the end
				if (! is_null($comment->comment) && strlen($comment->comment) > 0) {
					if ($marker->id == $markee->id) {
						$comment->self = true;
						array_unshift($comments[$markee->id], $comment);
					} else {
						$comments[$markee->id][] = $comment;
					}
				}
			}
		}

		foreach($comments as $uid => $comments) {
			$markee = $this->users[$uid];

			$r = new stdClass;
			$r->userpic = $output->render(new user_picture($markee));
			$r->fullname = fullname($markee);
			$r->comments = $comments;

			$c->responses[] = $r;
		}

		return $c;
	}

}

?>