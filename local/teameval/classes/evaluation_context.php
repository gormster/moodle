<?php

namespace local_teameval;

require_once(dirname(dirname(__FILE__)) . '/lib.php');

abstract class evaluation_context {

    protected $cm;

    /**
     * Should evaluation be shown to this or any user?
     * @param type|null $userid If null, check if evaluation is even possible in this context
     * @return bool
     */
	abstract public function evaluation_permitted($userid = null);

    /**
     * What group is this user associated with?
     * @param type $userid User ID
     * @return stdClass groups record
     */
	abstract public function group_for_user($userid);

    /**
     * Every group that might be returned by group_for_user
     * @return type
     */
    abstract public function all_groups();

    /**
     * Which users are marking in this context?
     * @return [int => stdClass] user id to user records
     */
    abstract public function marking_users();

    /**
     * This is never used to calculate grades, just in reports.
     * @param int id of the group in question
     * @return float grade for group
     */
    abstract public function grade_for_group($groupid);

    /**
     * Called when teameval knows that adjusted grades will have changed
     * @param [int] $users optional array of user ids whose grades have changed
     */
    abstract public function trigger_grade_update($users = null);

    public function evaluation_enabled() {
        // This can be called even when evaluation is not possible.
        // For this reason we don't use get_settings()
        global $DB;
        $enabled = $DB->get_field('teameval', 'enabled', ['cmid' => $this->cm->id]);
        return (bool)$enabled;
    }

    public function marks_available($userid) {
        $teameval = new team_evaluation($this->cm->id);
        return $teameval->marks_available($userid);
    }

    public function update_grades($grades) {

        if (is_object($grades)) {
            $grades = array($grades->userid=>$grades);
        } else if (array_key_exists('userid', $grades)) {
            $grades = array($grades['userid']=>$grades);
        }

        $teameval = new team_evaluation($this->cm->id);

        foreach($grades as $userid => $grade) {
            if (!is_object($grade)) {
                $grade = (object)$grade;
                $grades[$userid] = $grade;
            }
            $grade->rawgrade *= $teameval->multiplier_for_user($userid);
        }

        return $grades;
    }

}