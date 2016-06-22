<?php

namespace local_teameval\output;

use local_teameval\team_evaluation;
use local_teameval\evaluation_context;
use core_plugin_manager;
use renderable;
use context_module;

class team_evaluation_block implements renderable {

    public $cm;

    public $context;

    public $disabled;

    public $locked;

    public $lockedreason;

    public $lockedhint;

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
        $context = null;
        if ($enabled) {
            $teameval = team_evaluation::from_cmid($cmid);
        } else {
            $cm = get_coursemodule_from_id(null, $cmid);
            $evalcontext = evaluation_context::context_for_module($cm);
            if ($evalcontext->evaluation_permitted()) {
                $context = context_module::instance($cmid);
            }
        }

        return new static($teameval, $context);

    }

    public function __construct($teameval, $context = null) {
        global $USER, $DB;

        // If teameval is not set, we just want to show the big button saying "Start Team Evaluation"
        if ($teameval) {

            $this->teameval = $teameval;
            $this->context = $teameval->get_context();

            $cancreate = has_capability('local/teameval:createquestionnaire', $this->teameval->get_context());
            $cansubmit = has_capability('local/teameval:submitquestionnaire', $this->teameval->get_context(), null, false);

            // If the user can create questionnaires, then check against null (the general case).
            if ($teameval->get_evaluation_context()->evaluation_permitted($cancreate ? null : $USER->id) == false) {

                $this->disabled = true;

            } else {

                $settings = $this->teameval->get_settings();
                $settings->fraction *= 100;
                $settings->noncompletionpenalty *= 100;
                $settings->id = $this->teameval->id;
                $this->settings = $settings;

                $this->questiontypes = core_plugin_manager::instance()->get_plugins_of_type("teamevalquestion");
                $this->questions = $this->teameval->get_questions();

                $cm = $teameval->get_coursemodule();
                if ($cm) {
                    $this->cm = $cm;

                    $this->locked = $teameval->questionnaire_locked();
                    if ($this->locked !== false) {
                        list($reason, $user) = $this->locked;
                        $this->locked = true;
                        $this->lockedreason = $teameval->questionnaire_locked_reason($reason);
                        $this->lockedhint = $teameval->questionnaire_locked_hint($reason, $user);
                    }

                    if ($cancreate) {
                        $this->reporttypes = core_plugin_manager::instance()->get_plugins_of_type("teamevalreport");
                        $this->report = $this->teameval->get_report();
                    }

                    $releases = $DB->get_records('teameval_release', ['cmid' => $cm->id]);
                    $this->release = new release($this->teameval, $releases);

                    if ($cansubmit) {

                        if ($this->teameval->marks_available($USER->id)) {
                            $this->feedback = new feedback($this->teameval, $USER->id); // more than 200ms
                        }

                    }
                }

            }

        } else {

            $this->context = $context;

        }


    }

}

?>
