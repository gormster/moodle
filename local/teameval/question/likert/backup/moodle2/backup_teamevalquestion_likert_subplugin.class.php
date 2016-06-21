<?php

class backup_teamevalquestion_likert_subplugin extends backup_subplugin {

	public function define_question_subplugin_structure() {

		$subplugin = $this->get_subplugin_element(null, '../../qtype', 'likert');

		$wrapper = new backup_nested_element($this->get_recommended_name());

		$subplugin->add_child($wrapper);

		$question = new backup_nested_element('likertquestion', ['id'],
			['title',
			'description',
			'minval',
			'maxval',
			'meanings']);

		$wrapper->add_child($question);

		$question->set_source_table('teamevalquestion_likert', ['id' => '../../../../questionid']);

		return $subplugin;

	}

}