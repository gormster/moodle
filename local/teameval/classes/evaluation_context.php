<?php

namespace local_teameval;

abstract class evaluation_context {

    protected $cm;

	abstract public function evaluation_permitted($userid);

	abstract public function group_for_user($userid);

    abstract public function marking_users();

    public function evaluation_enabled() {
        $teameval = new team_evaluation($cm->id);
        return $teameval->get_settings()->enabled;
    }

    public function marks_available($userid) {
        $teameval = new team_evaluation($cm->id);
        return $teameval->marks_available($userid);
    }

}