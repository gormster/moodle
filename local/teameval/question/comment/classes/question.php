<?php

namespace teamevalquestion_comment;
    
class question implements \local_teameval\question {

    public $id;

    protected $teameval;

    protected $title;

    protected $description;

    public function __construct(\local_teameval\team_evaluation $teameval, $questionid = null) {
        global $DB;

        $this->id               = $questionid;
        $this->teameval         = $teameval;

        if ($questionid > 0) {
            $record = $DB->get_record('teamevalquestion_comment', array("id" => $questionid));

            $this->title            = $record->title;
            $this->description      = $record->description;
        }
    }

    public function submission_view($userid, $locked = false) {
        $context = ['id' => $this->id, 'title' => $this->title, 'description' => $this->description];

        if(has_capability('local/teameval:createquestionnaire', $this->teameval->get_context(), $userid)) {
            $context['users'] = [['userid' => 0, 'name' => 'Example User']];
        } else {
            $teammates = $this->teameval->teammates($userid);
            $context['users'] = [];

            foreach($teammates as $t) {
                $response = new response($this->teameval, $this, $userid);
                $comment = $response->comment_on($t->id);

                $c = ['userid' => $t->id, 'name' => fullname($t)];
                if (! is_null($comment)) { 
                    $c['comment'] = $comment;
                }
                if ($t->id == $userid) {
                    $c['self'] = true;
                    $c['name'] = get_string('yourself', 'local_teameval');
                }
                $context['users'][] = $c;
            }
        }

        $context['locked'] = $locked;

        return $context;
    }

    public function editing_view() {
        $context = ['id' => $this->id, 'title' => $this->title, 'description' => $this->description];
    }

    public function plugin_name() {
        return 'comment';
    }

    public function has_value() {
        return false;
    }

    public function has_feedback() {
        return true;
    }

    public function minimum_value() {
        return 0;
    }

    public function maximum_value() {
        return 0;
    }

    public function get_title() {
        return $this->title;
    }

    public function render_for_report($groupid = null) {
        return new output\question_report($this->teameval, $this, $groupid);
    }

}