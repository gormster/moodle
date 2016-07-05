<?php

namespace local_teameval;

interface report {

    public function __construct(team_evaluation $teameval);

    /**
     * Generate and return a renderable report.
     * @return type
     */
    public function generate_report();

}