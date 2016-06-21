<?php

class restore_teamevalquestion_comment_subplugin extends restore_subplugin {

	protected function define_question_subplugin_structure() {

		$question = new restore_path_element('commentquestion', $this->get_pathfor('/commentquestion'));

		return [$question];
	}

	public function process_commentquestion($question) {
		global $DB;

		$question = (object)$question;

		$oldid = $question->id;
		unset($question->id);

		$newid = $DB->insert_record('teamevalquestion_comment', $question);

		$this->set_mapping('comment_questionid', $oldid, $newid);

	}

	//TODO: if restore failed and teameval_questions was not updated, delete these rows

	public function after_restore_question() {
		global $DB;
		
	}

}