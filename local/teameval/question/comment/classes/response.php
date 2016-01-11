<?php

namespace teamevalquestion_comment;

use stdClass;

class response implements \local_teameval\response {

    public $question;

    protected $teameval;

    protected $userid;

    protected $comments;

    public function __construct(\local_teameval\team_evaluation $teameval, $question, $userid) {
        global $DB;

        $this->userid = $userid;
        $this->question = $question;
        $this->teameval = $teameval;

        $comments = $DB->get_records('teamevalquestion_comment_res', ['fromuser' => $userid, 'questionid' => $question->id]);
        $this->comments = [];
        foreach ($comments as $comment) {
            $this->comments[$comment->touser] = $comment;
        }
    }

    public function marks_given() {
        return (count($this->comments) > 0);
    }

    /**
     * Set comments
     * @param [int => string] $comments userid => comment text
     */
    public function update_comments($comments) {
        global $DB;

        foreach($comments as $touser => $comment) {
            if (isset($this->comments[$touser])) {
                $record = $this->comments[$touser];
                $record->comment = $comment;
                $DB->update_record('teamevalquestion_comment_res', $record);
            } else {
                $record = new stdClass;
                $record->questionid = $this->question->id;
                $record->fromuser = $this->userid;
                $record->touser = $touser;
                $record->comment = $comment;
                $DB->insert_record('teamevalquestion_comment_res', $record);
            }
        }
    }

    public function comment_on($userid) {
        if (isset($this->comments[$userid])) {
            return $this->comments[$userid]->comment;    
        }
        return null;
    }

    public function opinion_of($userid) {
        return null;
    }

    public function opinion_of_readable($userid) {
        if ($this->marks_given()) {
            return $this->comments[$userid]->comment;
        }
        return "No comment";
    }

}

?>