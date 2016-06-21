<?php

namespace local_teameval;

class events {

	public static function module_deleted($evt) {
		global $DB;

		$DB->delete_records('teameval', ['cmid' => $evt->objectid]);

	}

}