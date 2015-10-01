<?php

namespace local_teameval\teamevalquestion_likert;
    
class question implements \local_teameval\question {
    
    public function __construct($cmid, $questionid = null) {
        //todo
    }
    
    public function submission_view($userid) {
        return array("test" => "Hello, world");
    }
    
    public function editing_view() {
        return array("test" => "Editing view");
    }
    
    public function update($formdata) {
        //todo
    }
    
    
}

class response implements \local_teameval\response {
    public function __construct($questionid, $userid, $responseid = null) {
        //todo
    }
    
    public function update_response($formdata) {
        //todo
    }
}

    
?>