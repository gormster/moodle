<?php

namespace teamevalquestion_likert;
    
class question implements \local_teameval\question {
    
    public $id;
    
    protected $teameval;
    protected $title;
    protected $description;
    protected $minval;
    protected $maxval;

    public function __construct(\local_teameval\team_evaluation $teameval, $questionid = null) {
        global $DB;

        $this->id               = $questionid;
        $this->teameval         = $teameval;

        if ($questionid > 0) {
            $record = $DB->get_record('teamevalquestion_likert', array("id" => $questionid));

            $this->title            = $record->title;
            $this->description      = $record->description;
            $this->minval           = $record->minval;
            $this->maxval           = $record->maxval;
            $this->meanings         = json_decode($record->meanings);
        }
    }
    
    public function submission_view($userid) {
        global $DB;

        $context = ["id" => $this->id, "title" => $this->title, "description" => $this->description];

        // if the user can respond to this teameval

        $options = [];
        for ($i=$this->minval; $i <= $this->maxval; $i++) { 
            $o = ["value" => $i];
            if (isset($this->meanings->$i)) {
                $o["meaning"] = $this->meanings->$i;
            }
            $options[] = $o;
        }

        $context['options'] = $options;
        $context['optionswidth'] = 100 / ($this->maxval - $this->minval + 1);

        if (has_capability('local/teameval:submitquestionnaire', $this->teameval->get_context(), $userid, false)) {
            // get any response this user has given already
            $response = new response($this->teameval, $this, $userid);
            $marks = $response->raw_marks();

            $group = $this->teameval->group_for_user($userid);
            
            $members = groups_get_members($group->id);
            foreach ($members as $user) {
                $opts = [];

                foreach($options as $o) {
                    if (isset($marks[$user->id])) {
                        $mark = $marks[$user->id];
                        if ($o['value'] == $mark) { $o['checked'] = true; }
                    }
                    $opts[] = $o;
                }

                $context['users'][] = [
                    "name" => fullname($user),
                    "userid" => $user->id,
                    "options" => $opts
                ];
            }

        } else {
            $context['demo'] = true;
            $context['users'] = [
                [
                    "name" => "Example user",
                    "userid" => 0,
                    "options" => $options
                ]
            ];
        }

        return $context;
    }
    
    public function editing_view() {
        global $DB;

        $context = ["id" => $this->id, "title" => $this->title, "description" => $this->description];

        $meanings = [];
        for ($i=$this->minval; $i <= $this->maxval; $i++) { 
            $o = ["value" => $i];
            if (isset($this->meanings->$i)) {
                $o["meaning"] = $this->meanings->$i;
            }
            $meanings[] = $o;
        }

        $context['meanings'] = $meanings;

        return $context;
    }
    
    public function update($formdata) {
        //todo
    }

    public function plugin_name() {
        return 'likert';
    }

    public function has_value() {
        return true;
    }

    public function minimum_value() {
        return 0; // even if $minval == 1, return 0; it's what users expect
    }

    public function maximum_value() {
        return $this->maxval;
    }
    
    
}

?>