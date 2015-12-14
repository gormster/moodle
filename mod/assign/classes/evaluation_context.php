<?php

namespace mod_assign;

class evaluation_context extends \local_teameval\evaluation_context {
	
	protected $assign;

	public function __construct(\assign $assign) {
		$this->assign = $assign;
		$this->cm = $assign->get_course_module();
	}

	public function evaluation_permitted($userid = null) {
		return $this->assign->get_instance()->teamsubmission;
	}

	public function group_for_user($userid) {
		$grouping = $this->assign->get_instance()->teamsubmissiongroupingid;
		$groups = groups_get_all_groups($this->assign->get_course()->id, $userid, $grouping);
		if(count($groups) == 1) {
			return current($groups);
		}
		if(count($groups) > 1) {
			//throw something
		}
		if(count($groups) == 0) {
			//figure out correct response here
		}
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
		//TODO: you can actually assign different grades for everyone
		//check if that has happened

		// get any user from this group
		$mems = groups_get_members($groupid, 'u.id');
		$user = key($mems);

		if ($user > 0) {
			$grade = $this->assign->get_user_grade($user, false);
			if ($grade) {
				return $grade->grade;
			}
		}

		return null;
	}

	public function trigger_grade_update($users = null) {
		if (is_null($users)) {
			assign_update_grades($this->assign->get_instance());
		} else {
			foreach($users as $u) {
				assign_update_grades($this->assign->get_instance(), $u);
			}
		}
	}

}