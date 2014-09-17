<?php
    
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id         = required_param('id', PARAM_INT);            // course module

$workshop   = $DB->get_record('workshop', array('id' => $id), '*', MUST_EXIST);
$course     = $DB->get_record('course', array('id' => $workshop->course), '*', MUST_EXIST);
$cm         = get_coursemodule_from_instance('workshop', $workshop->id, $course->id, false, MUST_EXIST);

$workshop   = new workshop($workshop, $cm, $course);

// the params to be re-passed to view.php
$page       = optional_param('page', 0, PARAM_INT);
$sortby     = optional_param('sortby', 'lastname', PARAM_ALPHA);
$sorthow    = optional_param('sorthow', 'ASC', PARAM_ALPHA);

$PAGE->set_url($workshop->calibrate_url(), array('page' => $page, 'sortby' => $sortby, 'sorthow' => $sorthow));

require_login($course, false, $cm);
require_capability('mod/workshop:overridegrades', $PAGE->context);

$calibration = $workshop->calibration_instance();
$settingsform = $calibration->get_settings_form($PAGE->url);

if ($settingsdata = $settingsform->get_data()) {
    $calibration->calculate_calibration_scores($settingsdata);   // updates 'gradinggrade' in {workshop_assessments}
    $workshop->log('update calibration scores');
}

redirect(new moodle_url($workshop->view_url(), array('page' => $page, 'sortby' => $sortby, 'sorthow' => $sorthow)));
