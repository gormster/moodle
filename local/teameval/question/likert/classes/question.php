<?php

namespace teamevalquestion_likert;
    
class question implements \local_teameval\question {
    
    protected $test;

    protected $cm;

    public function __construct($cmid, $questionid = null) {
        global $DB;
        $record = $DB->get_record('teamevalquestion_likert', array("id" => $questionid));

        $this->test = $record->test;
    }
    
    public function submission_view($userid) {
        return array("test" => $this->test);
    }
    
    public function editing_view() {
        return array("test" => $this->test);
    }
    
    public function update($formdata) {
        //todo
    }
    
    
}

?>