<?php

namespace teamevalreport_scores;

require_once("{$CFG->dirroot}/local/teameval/lib.php");

use stdClass;

class report implements \local_teameval\report {

    protected $teameval;

    public function __construct(\local_teameval\team_evaluation $teameval) {

        $this->teameval = $teameval;

    }

    public function generate_report() {
        $scores = $this->teameval->get_evaluator()->scores();

        $data = [];
        foreach ($scores as $uid => $score) {
        	$group = $this->teameval->get_evaluation_context()->group_for_user($uid);
        	$grade = $this->teameval->get_evaluation_context()->grade_for_group($group->id);
        	$fraction = $this->teameval->get_settings()->fraction;
        	$multiplier = (1 - $fraction) + ($score * $fraction);
        	$intermediategrade = $grade * $multiplier;
        	$noncompletionpenalty = $this->teameval->non_completion_penalty($uid);
        	$finalgrade = $grade * $this->teameval->multiplier_for_user($uid);

        	$datum = new stdClass;
        	$datum->group = $group;
        	$datum->grade = $grade;
        	$datum->score = $score;
        	$datum->intermediategrade = $intermediategrade;
        	$datum->noncompletionpenalty = $noncompletionpenalty;
        	$datum->finalgrade = $finalgrade;

        	$data[$uid] = $datum;
        }

        return new output\scores_report($data);
    }


}