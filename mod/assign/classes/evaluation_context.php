<?php

namespace mod_assign;

class evaluation_context extends \local_teameval\evaluation_context {

    protected $assign;

    public function __construct(\assign $assign) {
        $this->assign = $assign;
        parent::__construct($assign->get_course_module());
    }

    public function evaluation_permitted($userid = null) {
        $enabled = $this->assign->get_instance()->teamsubmission && parent::evaluation_permitted($userid);
        if ($enabled && $userid) {
            if ($this->assign->is_any_submission_plugin_enabled()) {
                $groupsub = $this->assign->get_group_submission($userid, 0, false);
                if (($groupsub == false) ||
                    ($groupsub->status != ASSIGN_SUBMISSION_STATUS_SUBMITTED) ||
                    ($this->assign->submission_empty($groupsub))) {
                    $enabled = false;
                }
            } else {
                $grade = $this->assign->get_user_grade($userid, false);
                if (!($grade && $grade->grade !== null && $grade->grade >= 0)) {
                    $enabled = false;
                }
            }
        }
        return $enabled;

    }

    public function default_deadline() {
        $duedate = $this->assign->get_instance()->duedate;
        if ($duedate) {
            // By default, due date plus seven days
            return $duedate + 604800;
        } else {
            // otherwise one week after the assignment was created
            return $this->cm->added + 604800;
        }
    }

    public function minimum_deadline() {
        return $this->assign->get_instance()->duedate;
    }

    public function group_for_user($userid) {
        return $this->assign->get_submission_group($userid);
    }

    public function all_groups() {
        $grouping = $this->assign->get_instance()->teamsubmissiongroupingid;
        $groups = groups_get_all_groups($this->assign->get_course()->id, 0, $grouping);
        return $groups;
    }

    public function marking_users($fields = 'u.id') {
        $grouping = $this->assign->get_instance()->teamsubmissiongroupingid;

        $groups = groups_get_all_groups($this->assign->get_course()->id, 0, $grouping, 'g.id');

        // we want only group IDs
        $groups = array_keys($groups);

        $ctx = $this->assign->get_context();

        return get_users_by_capability($ctx, 'local/teameval:submitquestionnaire', $fields, '', '', '', $groups);
    }

    public function grade_for_group($groupid) {
        static $cached_grades = [];
        //TODO: you can actually assign different grades for everyone
        //check if that has happened

        if (isset($cached_grades[$groupid])) {
            return $cached_grades[$groupid];
        }

        // get any user from this group
        $mems = groups_get_members($groupid, 'u.id');
        $user = key($mems);

        if ($user > 0) {
            $grade = $this->assign->get_user_grade($user, false);
            if ($grade) {
                $cached_grades[$groupid] = $grade->grade;
                return $grade->grade;
            }
        }

        return null;
    }

    public function trigger_grade_update($users = null) {
        global $DB;

        if (empty($users)) {
            $grades = $this->assign->get_user_grades_for_gradebook(0);
        } else {
            $grades = [];
            foreach ($users as $userid) {
                $grades += $this->assign->get_user_grades_for_gradebook($userid);
            }
        }

        // Copied from assign::gradebook_item_update
        $assign = clone $this->assign->get_instance();
        $assign->cmidnumber = $this->cm->idnumber;
        $assign->gradefeedbackenabled = $this->assign->is_gradebook_feedback_enabled();

        assign_grade_item_update($assign, $grades);
    }

}
