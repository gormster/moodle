<?php

class restore_teamevalquestion_likert_subplugin extends restore_subplugin {

	protected function define_question_subplugin_structure() {

		$question = new restore_path_element('likertquestion', $this->get_pathfor('/likertquestion'));

		return [$question];
	}

	public function process_likertquestion($question) {
		global $DB;

		$question = (object)$question;

		$oldid = $question->id;
		unset($question->id);

		$newid = $DB->insert_record('teamevalquestion_likert', $question);

		$this->set_mapping('likert_questionid', $oldid, $newid);

	}

	//TODO: if restore failed and teameval_questions was not updated, delete these rows

	public function after_restore_question() {
		global $DB;
		
	}

}