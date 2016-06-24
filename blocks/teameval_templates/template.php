<?php

require_once(__DIR__ . '/../../config.php');

global $CFG, $OUTPUT, $PAGE;

require_once($CFG->dirroot . '/local/teameval/lib.php');

use local_teameval\team_evaluation;
use block_teameval_templates\output\title;

$id = optional_param('id', 0, PARAM_INT);
$contextid = optional_param('contextid', 0, PARAM_INT);

if (($id == 0) && ($contextid == 0)) {
	print_error('missingparam', '', '', 'id or contextid');
}

if ($id > 0) {
	$teameval = new team_evaluation($id);
	if (!is_null($teameval->get_coursemodule())) {
		print_error('notatemplate', 'block_teameval_templates');
	}
	$context = $teameval->get_context();
	$title = $teameval->get_settings()->title;
} else {
	$context = context::instance_by_id($contextid);
	$title = get_string('newtemplateheading', 'block_teameval_templates');
}

// Set up the page.
$url = new moodle_url("/blocks/teameval_templates/template.php");
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');

$courseid = null;
if ($context->contextlevel == CONTEXT_COURSE) {
	$courseid = $context->instanceid;
}

if ($context->contextlevel == CONTEXT_COURSECAT) {
	$node = $PAGE->navigation->find($context->instanceid, navigation_node::TYPE_CATEGORY);
	if ($node) {
		$node->make_active();
	}
}

require_login($courseid);
require_capability('block/teameval_templates:viewtemplate', $context);

// now that we've checked permissions, make a new teameval if needed

if (!isset($teameval)) {
	$teameval = team_evaluation::new_with_contextid($contextid);
	$url = new moodle_url($url, ['id' => $teameval->id]);
	$url->remove_params('contextid');
	redirect($url);
}

$PAGE->navbar->add($title);

$output = $PAGE->get_renderer('block_teameval_templates');
echo $output->header();

$title = new title($teameval);
echo $output->render($title);

$teameval_renderer = $PAGE->get_renderer('local_teameval');
$teameval = new \local_teameval\output\team_evaluation_block($teameval);
echo $teameval_renderer->render($teameval);

echo $output->footer();
