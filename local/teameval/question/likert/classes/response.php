<?php

namespace teamevalquestion_likert;

class response implements \local_teameval\response {

	protected $questionid;
	protected $userid;
	protected $responses;

    public function __construct($questionid, $userid, $responseid = null) {
        global $DB;

    	$records = $DB->get_records("teamevalquestion_likert_resp", array("questionid" => $questionid, "fromuser" => $userid), '', 'id,touser,mark,markdate');

    	//rearrange responses to be keyed by touser
    	$this->responses = [];
    	foreach($records as $r) {
    		$this->responses[$r->touser] = $r;
    	}
    	
    }
    
    public function update_response($formdata) {
        //todo
    }

    public function raw_marks() {
    	$context = [];
    	foreach($this->responses as $k => $r) {
    		$context[$k] = $r->mark;
    	}
    	return $context;
    }

}

?>