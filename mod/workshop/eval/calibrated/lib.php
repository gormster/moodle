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
 * This file defines interface of all grading evaluation classes
 *
 * @package    mod
 * @subpackage workshop
 * @copyright  2009 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(__FILE__)) . '/lib.php');  // interface definition
require_once($CFG->libdir . '/gradelib.php');

/**
 * Defines all methods that grading evaluation subplugins has to implement
 *
 * @todo the final interface is not decided yet as we have only one implementation so far
 */
class workshop_calibrated_evaluation implements workshop_evaluation {

    /** @var workshop the parent workshop instance */
    protected $workshop;

    /** @var the recently used settings in this workshop */
    protected $settings;
	
	private $examples;
	
	private $deviation_factors = array(9 => 0.2, 8 => 0.28, 7 => 0.35, 6=> 0.5, 5 => 0.75, 4 => 1.0, 3 => 1.3, 2 => 1.75, 1 => 2.0);
	private $grading_curves = array(9 => 4.0, 8 => 3.0, 7 => 2.0, 6 => 1.5, 5 => 1.0, 4 => 0.666, 3 => 0.5, 2 => 0.333, 1 => 0.25, 0 => 0);
	
    public function __construct(workshop $workshop) {
        global $DB;
        $this->workshop = $workshop;
        $this->settings = $DB->get_record('workshopeval_calibrated', array('workshopid' => $this->workshop->id));
		$this->examples = array();
    }

    public function update_grading_grades(stdclass $settings, $restrict=null) {
		
		global $DB;
		
        // remember the recently used settings for this workshop
        if (empty($this->settings)) {
            $record = new stdclass();
            $record->workshopid = $this->workshop->id;
            $record->comparison = $settings->comparison;
			$record->consistency = $settings->consistency;
            $DB->insert_record('workshopeval_calibrated', $record);
            $this->settings = $record;
        } elseif (($this->settings->comparison != $settings->comparison) || ($this->settings->consistency != $settings->consistency)) {
            $DB->set_field('workshopeval_calibrated', 'comparison', $settings->comparison,
                    array('workshopid' => $this->workshop->id));
            $DB->set_field('workshopeval_calibrated', 'consistency', $settings->consistency,
                    array('workshopid' => $this->workshop->id));
					
        }
		
        $grader = $this->workshop->grading_strategy_instance();

        // get the information about the assessment dimensions
        $diminfo = $grader->get_dimensions_info();
		
		// cache the reference assessments
		$references = $this->workshop->get_examples_for_manager();
		$calibration_scores = array();

        // fetch a recordset with all assessments to process
        $rs = $grader->get_assessments_recordset($restrict);
		$rrs = array();
        foreach ($rs as $r) {
			if(empty($calibration_scores[$r->reviewerid])) {
				$calibration_scores[$r->reviewerid] = $this->calculate_calibration_score($r->reviewerid, $references);
			}
			$rrs[] = $r;
        }
        $rs->close();
		
		$biggest_cal = max($calibration_scores);
		foreach ($calibration_scores as $k => $cal) {
			$calibration_scores[$k] = $cal / $biggest_cal * 100;
		}
        
        //now recalibrate the actual scores...
        foreach($rrs as $r) {
            
        }
		
		foreach($rrs as $r) {
            $record = new stdclass();
            $record->id = $r->assessmentid;
            $record->gradinggrade = grade_floatval($calibration_scores[$r->reviewerid]);
			$DB->update_record('workshop_assessments', $record, false);  // bulk operations expected
		}
		
	}
    
    public function update_submission_grades(stdclass $settings) {
    
	
		global $DB;
		error_log("in");
		//fetch all the assessments for all the submissions in this 
		$sql = "SELECT a.id, a.submissionid, a.weight, a.grade, a.gradinggrade, a.gradinggradeover
				FROM {workshop_submissions} s, {workshop_assessments} a
				WHERE s.workshopid = {$this->workshop->id}
					AND s.example = 0
					AND a.submissionid = s.id
				ORDER BY a.submissionid";
		
		$records = $DB->get_recordset_sql($sql);
		
		$weighted_grades = array();
		$total_weight = 0;
		$current_submissionid = 0;
		foreach($records as $v) {
			
			//this is actually "last": if the submissionid has changed, then we're on to a new submission.
			//it's kind of a stupid way of doing it but unfortunately there's no seeking in moodle recordsets, so
			//we can't get the submissionid of the next record to check if this is the last one
			if ($v->submissionid != $current_submissionid) {

				if ($current_submissionid > 0)
					$this->update_submission_grade($current_submissionid, $weighted_grades, $total_weight);
				
				//reset our vital statistics
				$weighted_grades = array();
				$total_weight = 0;
				$current_submissionid = $v->submissionid;
				
			}
			
			//just add the submission to the queue. we do all the work in the above if statement.
			$gradinggrade = is_null($v->gradinggradeover) ? $v->gradinggrade : $v->gradinggradeover;
			$weighted_grade = $v->grade * $v->weight * $gradinggrade;
			$weighted_grades[] = $weighted_grade;
			$total_weight += $v->gradinggrade * $v->weight;
		}
		
		//do it for the last one
		$this->update_submission_grade($current_submissionid, $weighted_grades, $total_weight);
		
		$records->close();
		error_log("end");
    }
	
	private function update_submission_grade($submissionid, $weighted_grades, $total_weight) {
		
		global $DB;
		
		//perform weighted average
		$weighted_avg = array_sum($weighted_grades) / $total_weight;
		error_log("submission: $submissionid; weighted average: {$weighted_avg}");
			
		$DB->set_field('workshop_submissions','grade',$weighted_avg,array("id" => $submissionid));
		
	}
	
	public function get_settings_form(moodle_url $actionurl=null) {
        global $CFG;    // needed because the included files use it
        global $DB;
        require_once(dirname(__FILE__) . '/settings_form.php');

        $customdata['workshop'] = $this->workshop;
        $customdata['current'] = $this->settings;
		$customdata['methodname'] = 'calibrated';
        $attributes = array('class' => 'evalsettingsform calibrated');

        return new workshop_calibrated_evaluation_settings_form($actionurl, $customdata, 'post', '', $attributes);
	}
	
    public static function delete_instance($workshopid) {
		echo 3;
	}
	
	
	//Private functions
	
	private function calculate_calibration_score($user, $references) {
		$examples = $this->workshop->get_examples_for_reviewer($user);
		
		$calibration_scores = array();

		foreach($references as $id => $ref) {
			$cal = $examples[$id];
			$diff = abs($cal->grade - $ref->grade); //order here doesn't matter
			$maxwrongness = max($ref->grade, 100 - $ref->grade); //how wrong can they possibly be
			
			$calibration_scores[] = 1 - ($diff / $maxwrongness);
		}
		
		//absdev
		$mean = array_sum($calibration_scores) / count($calibration_scores);
		foreach($calibration_scores as $key => $num) $devs[$key] = abs($num - $mean);
		$absdev = array_sum($devs) / count($devs);
		
		if ($absdev < 0.01) $absdev = 0;
		
		
		$deviation_factor = $this->deviation_factors[$this->settings->consistency]; //this measures how consistent the marks must be. higher numbers enforce stronger consistency.
		
		$adjusted_score = $mean * (1 - $absdev);
		
		//slope. the higher your accuracy, the more strictly we assess your consistency.
		$b = pow(2, $this->grading_curves[$this->settings->consistency - 1]);
		$deviation_factor *= $b * $mean - $b + 1;
		
		if ($deviation_factor < 0) $deviation_factor = 0;
		
		$calibrated_score = $adjusted_score * $deviation_factor + $mean * (1 - $deviation_factor);
		
		//restrict to 0..1
		$calibrated_score = min(max($calibrated_score, 0), 1);
		
		//now recalibrated based on the strictness curve
		
		$grading_curve = $this->grading_curves[$this->settings->comparison];
		
		if ($grading_curve >= 1) {
			$calibrated_score = 1 - pow(1-$calibrated_score, $grading_curve);
		} else {
			$calibrated_score = pow($calibrated_score, 1 / $grading_curve);
		}
		
		return $calibrated_score;
		
	}
	
	private function get_example_assessments() {
		
	}
}
