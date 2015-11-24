<?php

namespace local_teameval\output;

use local_teameval\team_evaluation;
use core_plugin_manager;
use renderable;

class team_evaluation_block implements renderable {

    public $cm;

    public $questions;
    
    public $questiontypes;

    public $teameval;

    public $settings;

    /**
     * @param int $cmid This is the cmid of the activity module this teameval belongs to
     */
    public function __construct($cmid) {
        $this->cm = get_coursemodule_from_id(null, $cmid);
        $this->teameval = new team_evaluation($cmid);
        $this->questiontypes = core_plugin_manager::instance()->get_plugins_of_type("teamevalquestion");

        $this->questions = $this->teameval->get_questions();

        $settings = $this->teameval->get_settings();
        $settings->fraction *= 100;
        $settings->noncompletionpenalty *= 100;
        $settings->cmid = $cmid;
        $this->settings = $settings;

        print_r($this->teameval->multipliers_for_group(8));

    }

}

?>