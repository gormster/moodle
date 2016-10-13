<?php

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');

$id = optional_param('id', 0, PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'assign');

require_login($course, true, $cm);

$form = new \teamevalquestion_likert\forms\settings_form();

$PAGE->set_url(new moodle_url('/local/teameval/question/likert/view.php', ['id' => $id]));

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();

