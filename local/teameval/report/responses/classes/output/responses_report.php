<?php

namespace teamevalreport_responses\output;

use core_user;
use stdClass;
use user_picture;

class responses_report implements \renderable, \templatable {

    protected $responses;

    public function __construct($responses) {
    	$this->responses = $responses;
    }

    public function export_for_template(\renderer_base $output) {
    	$c = new stdClass;

    	$c->questions = [];

    	foreach($this->responses as $question) {
    		$q = new stdClass;
    		$q->title = $question->questioninfo->question->get_title();
    		$q->groups = [];

    		foreach($question->groups as $groupinfo) {
    			$g = new stdClass;
    			$g->name = $groupinfo->group->name;
    			$g->markers = [];

    			$g->marks = [];

    			foreach($groupinfo->members as $m) {
    				$marked = new stdClass;
    				$marked->fullname = fullname($m->user);
    				$g->marked[] = $marked;

    				$marks = [];
    				foreach($groupinfo->members as $n) {
    					
    					$marks[] = $m->response->opinion_of_readable($n->user->id);
	    				
    				}

    				$marker = new stdClass;
    				$marker->marker = fullname($m->user);
    				$marker->scores = $marks;

    				$g->marks[] = $marker;
    				

    			}

    			$q->groups[] = $g;
    		}

    		$c->questions[] = $q;
    	}

        return $c;
    }

}