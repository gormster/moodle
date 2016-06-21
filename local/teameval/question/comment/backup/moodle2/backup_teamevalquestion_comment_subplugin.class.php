<?php

class backup_teamevalquestion_comment_subplugin extends backup_subplugin {

	public function define_question_subplugin_structure() {

		$subplugin = $this->get_subplugin_element(null, '../../qtype', 'comment');

		$wrapper = new backup_nested_element($this->get_recommended_name());

		$subplugin->add_child($wrapper);

		$question = new backup_nested_element('commentquestion', ['id'],
			['title',
			'description',
			'anonymous',
			'optional']);

		$wrapper->add_child($question);

		$question->set_source_table('teamevalquestion_comment', ['id' => '../../../../questionid']);

		return $subplugin;

	}

}