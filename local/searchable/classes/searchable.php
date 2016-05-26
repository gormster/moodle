<?php

namespace local_searchable;

use stdClass;

class searchable {

	static function set_weight($objecttype, $objectid, $tag, $weight) {

		global $DB;

		$tagrecord = $DB->get_record('searchable_tags', ['tag' => $tag]);
		if ($tagrecord == null) {
			$tagrecord = new stdClass;
			$tagrecord->tag = $tag;
			$id = $DB->insert_record('searchable_tags', $tagrecord);
			$tagrecord->id = $id;
		}

		$record = $DB->get_record('searchable_objects', ['objecttype' => $objecttype, 'objectid' => $objectid, 'tagid' => $tagrecord->id]);
		if ($record == null) {
			$record = new stdClass;
			$record->objecttype = $objecttype;
			$record->objectid = $objectid;
			$record->tagid = $tagrecord->id;
			$record->weight = $weight;
			$DB->insert_record('searchable_objects', $record);
		} else {
			$record->weight = $weight;
			$DB->update_record('searchable_objects', $record);
		}

	}

	static function remove_object($objecttype, $objectid) {
		$DB->delete_records('searchable_objects', ['objecttype' => $objecttype, 'objectid' => $objectid]);
	}

	static function results($objecttype, $chars) {
		if (count($chars) == 0) {
			return [];
		}

		global $DB;

		list($sql, $params) = $DB->get_in_or_equal($chars);
		$tags = $DB->get_records_select('searchable_tags', "tag $sql", $params);

		list($sql, $params) = $DB->get_in_or_equal(array_keys($tags), SQL_PARAMS_NAMED);
		$params['objecttype'] = $objecttype;
		$rslt = $DB->get_records_select('searchable_objects', "objecttype = :objecttype AND tagid $sql", $params, 'weight DESC', 'objectid, tagid, weight');

		foreach($rslt as $r) {
			$r->tag = $tags[$r->tagid]->tag;
			unset($r->tagid);
		}

		reset($rslt);

		return $rslt;

	}

}