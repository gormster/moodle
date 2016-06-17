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
        $amdmodules = [];

        global $PAGE;
        $is_ajax = !$PAGE->has_set_url();

    	foreach($this->responses as $question) {
    		$q = new stdClass;
    		$q->title = $question->questioninfo->question->get_title();
    		$q->groups = [];

    		foreach($question->groups as $groupinfo) {
    			$g = new stdClass;
    			$g->name = $groupinfo->group->name;
    			$g->marked = [];

    			$g->marks = [];

    			foreach($groupinfo->members as $m) {
    				$marked = new stdClass;
    				$marked->fullname = fullname($m->user);
    				$g->marked[] = $marked;

    				$marks = [];
    				foreach($groupinfo->members as $n) {
    					
                        $readable = $m->response->opinion_of_readable($n->user->id, 'teamevalreport_responses');
                        $mark = new stdClass;

                        $renderer = $PAGE->get_renderer('teamevalquestion_' . $m->response->question->plugin_name());
                        $mark->prerendered = $renderer->render($readable);
                        if ($readable->amd_init_call()) {
                            list($module, $call) = $readable->amd_init_call();
                            if (!isset($amdmodules[$module])) {
                                $amdmodules[$module] = [];
                            }
                            if (!in_array($call, $amdmodules[$module])) {
                                $amdmodules[$module][] = $call;
                            }
                        }

    					$marks[] = $mark;
	    				
    				}

    				$marker = new stdClass;
    				$marker->marker = fullname($m->user);
    				$marker->scores = $marks;

    				$g->marks[] = $marker;
    				

    			}

                $g->markedcount = count($g->marked);
                $g->markscount = count($g->marks) + 1;

    			$q->groups[] = $g;
    		}

    		$c->questions[] = $q;
    	}


        if($PAGE->has_set_url()) {
            // do stuff with the AMD shiz
            foreach($amdmodules as $module => $calls) {
                foreach ($calls as $call) {
                    $PAGE->requires->js_call_amd($module, $call);
                }
            }
        } else {
            $c->amdmodules = [];
            foreach($amdmodules as $module => $calls) {
                $m = new stdClass;
                $m->module = $module;
                $m->calls = $calls;
                $c->amdmodules[] = $m;
            }
        }

        return $c;
    }

}