<?php

namespace local_teameval\tasks;

use core\task\adhoc_task;

class calculate_grades_task extends adhoc_task {

    public function __construct($cmid) {
        global $DB;
        $record = new stdClass();
        $record->cmid = $cmid;

        $DB->delete_records('teameval_calculate_grades_task', ['cmid' => $cmid]);
        $id = $DB->insert_record('teameval_calculate_grades_task', $record);
        $this->set_custom_data('id' => $id);
    }

    public function execute() {
        global $DB, $CFG;
        $record = $DB->get_record('teameval_calculate_grades_task', ['cmid' => $cmid]);
        $customdata = $this->get_custom_data();
        $id = $customdata->id;

        // multiple tasks might be scheduled; only execute if we were last in
        if ($record->id == $id) {
            require_once($CFG->dirroot . '/local/teameval/lib.php');
            $teameval = new team_evaluation($record->cmid);
            $ctx = $teameval->get_evaluation_context();
            $ctx->trigger_grade_update();
        }
    }

}