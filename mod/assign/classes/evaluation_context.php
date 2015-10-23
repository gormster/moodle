<?php

namespace mod_assign;

class evaluation_context extends \local_teameval\evaluation_context {
	
	protected $assign;

	public function __construct(\assign $assign) {
		$this->assign = $assign;
	}

	public function evaluation_permitted($userid) {
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

}