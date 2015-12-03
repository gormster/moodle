<?php

namespace teamevalreport_responses;

require_once("{$CFG->dirroot}/local/teameval/lib.php");

class report implements \local_teameval\report {

    protected $teameval;

    public function __construct(\local_teameval\team_evaluation $teameval) {

        $this->teameval = $teameval;

    }

    public function generate_report() {
        $scores = $this->teameval->multipliers_for_group(4);
        return new output\responses_report($scores);
    }


}