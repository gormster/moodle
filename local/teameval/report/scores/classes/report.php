<?php

namespace teamevalreport_scores;

require_once("{$CFG->dirroot}/local/teameval/lib.php");

class report implements \local_teameval\report {

    protected $teameval;

    public function __construct(\local_teameval\team_evaluation $teameval) {

        $this->teameval = $teameval;

    }

    public function generate_report() {
        $scores = $this->teameval->get_evaluator()->scores();
        return new output\scores_report($scores);
    }


}