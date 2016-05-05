<?php

namespace teamevalquestion_likert;

use coding_exception;
    
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

        // what I need to end up with:

        // context[id, title, description, users[ord, remaining, name], options[value, meaning, users[userid, name, checked]]]

        $context = ["id" => $this->id, "title" => $this->title, "description" => $this->description, "self" => $this->teameval->get_settings()->self];

        $options = [];
        $totalstrlen = 0;

        for ($i=$this->minval; $i <= $this->maxval; $i++) { 
            $o = ["value" => $i];
            if (isset($this->meanings->$i)) {
                $o["meaning"] = $this->meanings->$i;
                $totalstrlen += strlen($this->meanings->$i);
            }
            $options[] = $o;
        }

        $context['waterfall'] = $totalstrlen >= 255;
        $context['grid'] = $totalstrlen < 255;

        // if the user can respond to this teameval
        if (has_capability('local/teameval:submitquestionnaire', $this->teameval->get_context(), $userid, false)) {
            // get any response this user has given already
            $response = new response($this->teameval, $this, $userid);
            $marks = $response->raw_marks();
            
            $members = $this->teameval->teammates($userid);

            $ord = 0;

            $headers = [];
            $previous = [];

            $users = [];

            foreach ($members as $user) {
                $opts = [];
                $ord++;

                $fullname = $user->id == $userid ? get_string('yourself', 'local_teameval') : fullname($user);

                $headers[] = [
                    "ord" => $ord,
                    "remaining" => count($members) - $ord + 1,
                    "name" => $fullname,
                    "previous" => $previous
                ];

                $previous[] = $fullname;

                // set user options for grid format

                foreach($options as $o) {
                    if (isset($marks[$user->id])) {
                        $mark = $marks[$user->id];
                        if ($o['value'] == $mark) { $o['checked'] = true; }
                    }
                    $opts[] = $o;
                }

                $c = [
                    "name" => fullname($user),
                    "userid" => $user->id,
                    "options" => $opts
                ];

                if ($user->id == $userid) {
                    $c['self'] = true;
                    $c['name'] = get_string('yourself', 'local_teameval');
                }

                $users[] = $c;
            }

            $context['headers'] = $headers;
            $context['users'] = $users;

            $opts = [];
            foreach($options as $o) {
                
                $users = [];
                foreach($members as $markeduser) {
                    $u = [
                        "userid" => $markeduser->id,
                        "name" => fullname($markeduser)
                    ];
                    if ($markeduser->id == $userid) {
                        $u["name"] = get_string('yourself', 'local_teameval');
                    }

                    if (isset($marks[$markeduser->id])) {
                        $mark = $marks[$markeduser->id];
                        $u['checked'] = ($o['value'] == $mark);
                    }    
                    $users[] = $u;
                }
                $o['users'] = $users;

                $opts[] = $o;
            }

            $context['options'] = $opts;




        } else {
            $context['demo'] = true;

            $opts = [];

            if ($this->teameval->get_settings()->self) {
                $context['headers'] = [
                    [
                        "ord" => 1,
                        "remaining" => 2,
                        "name" => "Yourself",
                        "previous" => []
                    ],
                    [
                        "ord" => 2,
                        "remaining" => 1,
                        "name" => "Example user",
                        "previous" => ["Yourself"]
                    ]
                ];

                foreach ($options as $o) {
                $o["users"] = [
                    [
                        "name" => "Yourself",
                        "userid" => -1,
                        "checked" => false
                    ],
                    [
                        "name" => "Example user",
                        "userid" => 0,
                        "checked" => false
                    ]

                ];
                $opts[] = $o;

                $yourself = ["name" => "Yourself", "userid" => -1];
                $user = ["name" => "Example user", "userid" => 0];
                foreach ($options as $o) {
                    $yourself["options"][] = ["value" => $o['value'], "checked" => false];
                    $user["options"][] = ["value" => $o['value'], "checked" => false];
                }
                $context['users'] = [$yourself, $user];
            }

            } else {
                $context['headers'] = [
                    [
                        "ord" => 1,
                        "remaining" => 1,
                        "name" => "Example user",
                        "previous" => []
                    ]
                ];

                $o["users"] = [
                    [
                        "name" => "Example user",
                        "userid" => 0,
                        "checked" => false
                    ]
                ];

                $user = ["name" => "Example user", "userid" => 0];
                foreach ($options as $o) {
                    $user["options"][] = ["value" => $o['value'], "checked" => false];
                }
                $context['users'] = [$user];
            }
            
            $context['options'] = $opts;

            

        }

        return $context;
    }
    
    public function editing_view() {
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
    
    public function get_title() {
        return $this->title;
    }

    public function has_feedback() {
        return false;
    }

    public function render_for_report($groupid = null) {
        throw new coding_exception("not implemented");
    }
    
}

?>