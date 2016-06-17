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

    public $release;

    public $feedback;

    /**
     * @param int $cmid This is the cmid of the activity module this teameval belongs to
     */

    public static function from_cmid($cmid) {
        global $DB;

        // if teameval is not enabled we should just show the button and not load the class
        $enabled = $DB->get_field('teameval', 'enabled', ['cmid' => $cmid]);

        $teameval = null;
        if ($enabled) {
            $teameval = team_evaluation::from_cmid($cmid);
        }

        return new static($teameval);

    }

    public function __construct($teameval) {

        // If teameval is not set, we just want to show the big button saying "Start Team Evaluation"
        if ($teameval) {

            $this->teameval = $teameval;

            $this->questiontypes = core_plugin_manager::instance()->get_plugins_of_type("teamevalquestion");
            $this->questions = $this->teameval->get_questions();

            if (has_capability('local/teameval:createquestionnaire', $this->teameval->get_context())) {
                $this->reporttypes = core_plugin_manager::instance()->get_plugins_of_type("teamevalreport");
                $this->report = $this->teameval->get_report();
            }

            $settings = $this->teameval->get_settings();
            $settings->fraction *= 100;
            $settings->noncompletionpenalty *= 100;
            $settings->id = $this->teameval->id;
            $this->settings = $settings;

            $cm = $teameval->get_coursemodule();
            if ($cm) {
                $this->cm = $cm;

                global $DB;
                $releases = $DB->get_records('teameval_release', ['cmid' => $cm->id]);
                $this->release = new release($this->teameval, $releases);

                global $USER;
                if (has_capability('local/teameval:submitquestionnaire', $this->teameval->get_context(), null, false)) {

                    if ($this->teameval->marks_available($USER->id)) {
                        $this->feedback = new feedback($this->teameval, $USER->id); // more than 200ms
                    }

                }
            }

        }



    }

}

?>
