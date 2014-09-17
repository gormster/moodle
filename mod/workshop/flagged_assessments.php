<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A list of all flagged assessments, and actions to take upon them.
 *
 * @package    mod
 * @subpackage workshop
 * @copyright  2009 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id         = required_param('id', PARAM_INT); // course_module ID

$cm         = get_coursemodule_from_id('workshop', $id, 0, false, MUST_EXIST);
$course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$workshop   = $DB->get_record('workshop', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
require_capability('mod/workshop:viewallassessments', $PAGE->context);

$workshop = new workshop($workshop, $cm, $course);

$flagged_assessments = $workshop->get_flagged_assessments();

if ($_POST) {
	
	foreach($flagged_assessments as $a) {
		if (isset($_POST['assessment_'.$a->id])) {
			
			$record = new stdClass();
			$record->id = $a->id;
			$record->submitterflagged = -1;
			
			$weight = $_POST['assessment_'.$a->id];
			if ($weight == 0) {
				$record->weight = 0;
			}
			
            // TODO: MAKE SURE YOU UNCOMMENT THIS LINE BEFORE COMMITTING
            // $DB->update_record('workshop_assessments', $record);
			
		}
	}
	
	redirect($workshop->aggregate_url());
	
}

$PAGE->set_url($workshop->flagged_assessments_url());
$PAGE->set_title($workshop->name);
$PAGE->set_heading($course->fullname);

$output = $PAGE->get_renderer('mod_workshop');
echo $output->header();
echo $output->heading(format_string($workshop->name), 2);

$strategy = $workshop->grading_strategy_instance();

// Moodleforms don't nest or repeat nicely, so we're going to be using bare HTML forms

echo html_writer::start_tag('form', array('action' => $workshop->flagged_assessments_url(), 'method' => 'post'));

foreach($flagged_assessments as $assessment) {
	$mform      = $strategy->get_assessment_form($PAGE->url, 'assessment', $assessment, false);
    $options    = array(
        'showreviewer'  => has_capability('mod/workshop:viewreviewernames', $workshop->context),
        'showauthor'    => has_capability('mod/workshop:viewauthornames', $workshop->context),
        'showform'      => !is_null($assessment->grade),
        'showweight'    => true,
		'showflaggingresolution' => true
    );

	$submission = new stdClass();
	$submission->id = $assessment->submissionid;
	$submission->content = $assessment->submissioncontent;
	$submission->contentformat = $assessment->submissionformat;
	$submission->attachment = $assessment->submissionattachment;
	$submission->authorid = $assessment->authorid;
		
    $displayassessment = $workshop->prepare_assessment_with_submission($assessment, $submission, $mform, $options);
	echo $output->render($displayassessment);
	
	
}

echo $output->container(html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('aggregategrades', 'workshop'))), 'center');

echo html_writer::end_tag('form');

echo $output->footer();
