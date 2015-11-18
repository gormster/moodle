<?php

namespace teamevalquestion_likert;

use stdClass;

class response implements \local_teameval\response {

    protected $teameval;
	protected $question;
	protected $userid;
	protected $responses;

    public function __construct($teameval, $question, $userid, $responseid = null) {
        global $DB;

        $this->teameval = $teameval;
        $this->question = $question;
        $this->userid = $userid;

    	$records = $DB->get_records("teamevalquestion_likert_resp", array("questionid" => $question->id, "fromuser" => $userid), '', 'id,touser,mark,markdate');

    	//rearrange responses to be keyed by touser
    	$this->responses = [];
    	foreach($records as $r) {
    		$this->responses[$r->touser] = $r;
    	}

    }
    
    /**
     * Update responses from given user data
     * @param array $formdata userid => mark
     * @return null
     */
    public function update_response($formdata) {
        global $DB;

        foreach($formdata as $userid => $mark) {
            if (isset($this->responses[$userid])) {
                $record = $this->responses[$userid];
                $record->mark = $mark;
                $record->markdate = time();
                $DB->update_record("teamevalquestion_likert_resp", $record);
                $this->responses[$userid] = $record;
            } else {
                $record = new stdClass;
                $record->fromuser = $this->userid;
                $record->questionid = $this->questionid;
                $record->touser = $userid;
                $record->mark = $mark;
                $record->markdate = time();
                $id = $DB->insert_record("teamevalquestion_likert_resp", $record);
                $record->id = $id;
                $this->responses[$userid] = $record;
            }
        }
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