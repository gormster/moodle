<?php

namespace teamevalquestion_likert;
    
class question implements \local_teameval\question {
    
    protected $id;
    protected $cm;
    protected $title;
    protected $description;
    protected $minval;
    protected $maxval;

    public function __construct($cmid, $questionid = null) {
        global $DB;
        $record = $DB->get_record('teamevalquestion_likert', array("id" => $questionid));

        $this->id               = $questionid;
        $this->title            = $record->title;
        $this->description      = $record->description;
        $this->minval           = $record->minval;
        $this->maxval           = $record->maxval;
    }
    
    public function submission_view($userid) {
        global $DB;

        $context = ["id" => $this->id, "title" => $this->title, "description" => $this->description];
        
        // get any response this user has given already
        $response = response($this->id, $userid);
        $marks = $response->raw_marks();

        return $context;
    }
    
    public function editing_view() {
        return array("id" => $this->id, "test" => $this->test);
    }
    
    public function update($formdata) {
        //todo
    }
    
    
}

?>