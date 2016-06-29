<?php

namespace teamevalreport_responses;

require_once("{$CFG->dirroot}/local/teameval/lib.php");

use user_picture;
use stdClass;

class report implements \local_teameval\report {

    protected $teameval;

    public function __construct(\local_teameval\team_evaluation $teameval) {

        $this->teameval = $teameval;

    }

    public function generate_report() {
        $questions = $this->teameval->get_questions();
        $allgroups = $this->teameval->get_evaluation_context()->all_groups();

        $responses = [];

        // this will end up looking like:
        // [ questioninfo => questioninfo, groups => [
        //   groupid => [ group => group, members => [ userid => [user => user, response => response]]
        // ]]

        foreach($questions as $q) {
        	$responseinfo = new stdClass;
        	$responseinfo->questioninfo = $q;
        	$responseinfo->groups = [];
        	$responses[] = $responseinfo;
        }

        $groupmembers = [];

        foreach($allgroups as $gid => $grp) {
        	$groupmembers[$gid] = $this->teameval->group_members($gid);
        }

        foreach($responses as $r) {
        	foreach($allgroups as $gid => $grp) {
	        	$groupinfo = new stdClass;
	    		$groupinfo->group = $grp;
	    		$groupinfo->members = [];

	        	foreach($groupmembers[$gid] as $uid => $user) {
	        		$memberinfo = new stdClass;
	        		$memberinfo->user = $user;
	        		
        			$qi = $r->questioninfo;
        			$resp = $this->teameval->get_response($qi, $uid);
        			$memberinfo->response = $resp;
	        		
	        		$groupinfo->members[$uid] = $memberinfo;
	        	}

	        	$r->groups[] = $groupinfo;
	        }
        }

        return new output\responses_report($responses);
    }


}