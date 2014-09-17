<?php

require_once(dirname(dirname(__FILE__)) . '/lib.php');  // interface definition

class workshop_examples_calibration_method implements workshop_calibration_method {
    
    protected $workshop;
    
    protected $settings;
    
    protected $scores;
    
    public static $grading_curves = array(9 => 4.0, 8 => 3.0, 7 => 2.0, 6 => 1.5, 5 => 1.0, 4 => 0.666, 3 => 0.5, 2 => 0.333, 1 => 0.25, 0 => 0);
    
    public function __construct(workshop $workshop) {
        global $DB;
        $this->workshop = $workshop;
        $this->settings = $DB->get_record('workshopcalibration_examples',array("workshopid" => $workshop->id));
    }
    
    public function calculate_calibration_scores(stdClass $settings) {
        
		global $DB;
		
        // remember the recently used settings for this workshop
        if (empty($this->settings)) {
            $this->settings = new stdclass();
            $record = new stdclass();
            $record->workshopid = $this->workshop->id;
            $record->comparison = $settings->comparison;
			$record->consistency = $settings->consistency;
            $DB->insert_record('workshopcalibration_examples', $record);
        } elseif (($this->settings->comparison != $settings->comparison) || ($this->settings->consistency != $settings->consistency)) {
            $DB->set_field('workshopcalibration_examples', 'comparison', $settings->comparison,
                    array('workshopid' => $this->workshop->id));
            $DB->set_field('workshopcalibration_examples', 'consistency', $settings->consistency,
                    array('workshopid' => $this->workshop->id));
        }
		
        $grader = $this->workshop->grading_strategy_instance();
        
        $this->settings->comparison = $settings->comparison;
        $this->settings->consistency = $settings->consistency;

        // get the information about the assessment dimensions
        $diminfo = $grader->get_dimensions_info();

		// fetch the reference assessments
		$references = $this->get_reference_assessments();

        // fetch a recordset with all assessments to process
        $rs = $grader->get_assessments_recordset(null,true);
        $example_assessments = array();
        
        $reference_assessments = array();
        foreach($references as $r) { 
            $reference_assessments[] = $r->assessmentid;
        }
        
        foreach ($rs as $r) {
            //skip the exemplar assessments
            if (in_array($r->assessmentid,$reference_assessments))
                continue;
            
            if (array_key_exists($r->submissionid,$references)) {
                $example_assessments[$r->reviewerid][$r->submissionid][$r->dimensionid] = $r->grade;
            }
        }
        $rs->close();
        

        foreach($example_assessments as $userid => $assessments) {
            $calibration_scores[$userid] = $this->calculate_calibration_score($assessments,$references,$diminfo) * 100;
        }
        
        $this->scores = $calibration_scores;
        
        
        $records = $DB->get_records('workshop_calibration', array("workshopid" => $this->workshop->id), '', 'userid, id');
        
        foreach($this->scores as $k => $v) {
            if (isset($records[$k])) {
                $record = $records[$k];
                $record->score = $v;
                $DB->update_record('workshop_calibration',$record);
            } else {
                $record = new stdclass;
                $record->workshopid = $this->workshop->id;
                $record->userid = $k;
                $record->score = $v;
                $DB->insert_record('workshop_calibration',$record);
            }
        }
        
    }
    
    private function get_reference_assessments() {
        $grader = $this->workshop->grading_strategy_instance();
        $diminfo = $grader->get_dimensions_info();
        
		// cache the reference assessments
		$references = $this->workshop->get_examples_for_manager();
		$calibration_scores = array();
        
        //fetch grader recordset for examples
        $userkeys = array();
        foreach($references as $r) {
            $userkeys[$r->authorid] = $r->authorid;
        }

        $exemplars = $grader->get_assessments_recordset($userkeys,true);
        
        foreach($exemplars as $r) {
            if (array_key_exists($r->submissionid,$references)) {
                $references[$r->submissionid]->diminfo[$r->dimensionid] = $r->grade;
            }
        }
        
        return $references;
    }
    
    /*
    Thinking through this calculation:
    
    T is the set of all absdev of student's scores v exemplar scores
    
    x is the sum of T

    you're aiming for LOWER x. Exemplar's x is 0. If x < 0.02, round to 0.
    
    Invert this and normalise it to 0..1. Exemplar's x is now 1. The worst possible x is 0.
    
    Scale this according to the accuracy curves.
    
    y is the mean absolute deviation of T. Once again you want small y. y falls in the range 0..50
    
    We invert and normalise y to 0..1. Exemplar's y is now 1. The worst possible y is 0.
    
    Plug y into the consistency curves. Multiply x by the result and you have your calibration score!
    
    */
	
	private function calculate_calibration_score($assessments, $references, $diminfo) {
        
        //before we even get started, make sure the user has completed enough assessments to be calibrated
        $required_number_of_assessments = $this->workshop->numexamples or count($references);
        if ( count($assessments) < $required_number_of_assessments ) {
            return 0;
        }
        
        //now that we've made sure of that, we need to get our set of deviations
        
        $absolute_deviations = array(); // the set of all absdev of student's scores v exemplar scores (T)
        
        foreach($references as $k => $a) {

            foreach($a->diminfo as $dimid => $mydimval) {
                if (!empty($assessments[$k])) {
                    $theirdimval = $assessments[$k][$dimid];
                    $dim = $diminfo[$dimid];

                    $diff = abs( $mydimval - $theirdimval ); 
                    $absolute_deviations[] = $this->normalize_grade($dim, $diff);
                }
            }
        }
        
        $x = array_sum($absolute_deviations) / count($absolute_deviations);
        
        $x /= 100;
        if ($x < 0.01) $x = 0; //round 99% up to 100%
        $x = 1 - $x; //invert $x. 1 is now the best possible score.
        
        $grading_curve = $this::$grading_curves[$this->settings->comparison];
        
        if ($grading_curve >= 1) {
            $x = 1 - pow(1-$x, $grading_curve);
        } else {
            $x = pow($x, 1 / $grading_curve);
        }
        
        //now let's adjust for consistency
        
        //let's get the mean absolute deviation of T
        
        $mean = array_sum($absolute_deviations) / count($absolute_deviations);
        $numerator = 0; //top half of the MAD fraction
        foreach($absolute_deviations as $z) {
            $numerator += abs($z - $mean);
        }
        $y = $numerator / count($absolute_deviations);
        
        $y /= 100;
        if ($y < 0.01) $y = 0;
        if ($y > 1) $y = 1; //this *shouldn't* happen, but I'm not ruling it out
        $y = 1 - $y; //invert y. 1 is now the best possible score.
        
        $consistency_curve = $this::$grading_curves[9 - $this->settings->consistency]; //the consistency curves are actually around the other way - 0 means no consistency check while 8 is strictest. so we subtract the consistency setting from nine.
        
        //y = ax - a + 1
        $consistency_multiplier = $consistency_curve * $y - $consistency_curve + 1;
        
        $x *= $consistency_multiplier;
        
        // restrict $x to 0..1
        if ($x < 0) $x = 0;
        if ($x > 1) $x = 1;
        
        return $x;
		
	}
    
    private function normalize_grade($dim,$grade) {
        //todo: weight? is weight a factor here? probably should be...
        $dimmin = $dim->min;
        $dimmax = $dim->max;
        if ($dimmin == $dimmax) {
            return grade_floatval($dimmax);
        } else {
            return grade_floatval(($grade - $dimmin) / ($dimmax - $dimmin) * 100);
        }
    }
    
    //You are strongly encouraged to maintain a (sensibly sized) in memory
    //cache of the calibration scores after a call to calculate_calibration_scores
    //and use that whenever possible to return values for these functions.
    public function get_calibration_scores() {
        if (!isset($this->scores)) {
            global $DB;
            $this->scores = $DB->get_records_menu('workshop_calibration', array("workshopid" => $this->workshop->id), '', 'userid, score');
        }
        return $this->scores;
    }
    
    public function get_calibration_score_for_user($userid) {

    }
    
    public function user_calibration_url($userid) {
        return new moodle_url('/mod/workshop/calibration/examples/view.php', array('uid' => $userid, 'id' => $this->workshop->id));
    }
    
    // These methods are optional - unfortunately PHP has no way of marking
    // optional methods in an interface. So, you can safely implement these methods
    // with just an empty pair of braces and return nothing.
    
    public function prepare_grade_breakdown($userid) {
        $grader = $this->workshop->grading_strategy_instance();
        $diminfo = $grader->get_dimensions_info();
        $exxx = $grader->get_assessments_recordset(array($userid),true);
        $references = $this->get_reference_assessments();
        $options = array(
            'gradedecimals' => $this->workshop->gradedecimals,
            'accuracy' => $this->settings->comparison,
            'consistency' => $this->settings->consistency,
            'finalscoreoutof' => $this->workshop->gradinggrade
        );
        
        return new workshop_examples_calibration_explanation($userid, $exxx, $references, $diminfo, $options);
    }
    
	public function get_settings_form(moodle_url $actionurl) {
        global $CFG;    // needed because the included files use it
        global $DB;
        require_once(dirname(__FILE__) . '/settings_form.php');

        $customdata['workshop'] = $this->workshop;
        $customdata['current'] = $this->settings;
        $customdata['methodname'] = 'examples';
        $attributes = array('class' => 'calibrationsettingsform calibrated');

        return new workshop_examples_calibration_settings_form($actionurl, $customdata, 'post', '', $attributes);
	}
    
}

class workshop_examples_calibration_explanation implements renderable {
    
    public function __construct($user, $examples, $references, $diminfo, $options = array()) {
        $this->user = $user;
        $this->examples = array();
        foreach($examples as $k => $v) {
            if (array_key_exists($k, $references)) {
                $this->examples[$v->submissionid][$v->dimensionid] = $v;
            }
        }
        
        if (count($this->examples) == 0) {
            $this->empty = true;
            return;
        }
        
        $this->references = array();
        foreach($references as $k => $v) {
            $this->references[$k] = $v;
        }
        $this->diminfo = $diminfo;
        foreach(array('gradedecimals' => 0, 'accuracy' => 5, 'consistency' => 5, 'finalscoreoutof' => null) as $k => $default) {
            $this->$k = empty($options[$k]) ? $default : $options[$k];
        }
        
        //make the table
        
        $reference_values = array();
        
        foreach($this->references as $k => $a) {
            
            foreach($a->diminfo as $dimid => $mydimval) {
                if (!empty($this->examples[$k])) {
                    $theirdimval = $this->examples[$k][$dimid];
                    $dim = $diminfo[$dimid];
                
                    $diff = abs( $mydimval - $theirdimval->grade ); 
                    $reference_values[$k][$dimid] = array($diminfo[$dimid], $theirdimval->grade, $mydimval, $diff);
                }
            }
        }
        
        $this->reference_values = $reference_values;
        
        //calculate the needed values
        
        $abs_devs = array();
        foreach($this->reference_values as $k => $v) {
            foreach($v as $i => $a) {
                $dim = $a[0];
                $diff = $a[3];
                $abs_devs[] = $this->normalize_grade($dim,$diff);
            }
        }
        
        $this->raw_average = array_sum($abs_devs) / count($abs_devs);
        $x = 100 - $this->raw_average;
        
        $grading_curve = workshop_examples_calibration_method::$grading_curves[$this->accuracy];
        
        
        $x /= 100;
        if ($grading_curve >= 1) {
            $scaled_average = 1 - pow(1-$x, $grading_curve);
        } else {
            $scaled_average = pow($x, 1 / $grading_curve);
        }
        $scaled_average *= 100;
        
        $this->scaled_average = $scaled_average;
        
        $mean = array_sum($abs_devs) / count($abs_devs);
        $numerator = 0; //top half of the MAD fraction
        foreach($abs_devs as $z) {
            $numerator += abs($z - $mean);
        }
        $mad = $numerator / count($abs_devs);
        
        // $mad /= 50;
        // if ($mad < 0.01) $mad = 0;
        // if ($mad > 1) $mad = 1;
        // $mad = 1 - $mad;
        
        $this->mad = $mad;
        
        $consistency_curve = workshop_examples_calibration_method::$grading_curves[9 - $this->consistency];
        
        $consistency_multiplier = $consistency_curve * (1 - ($mad / 100)) - $consistency_curve + 1;
        
        $this->consistency_multiplier = $consistency_multiplier;
        
        $this->final_score = $consistency_multiplier * $this->scaled_average;
        
        if ($this->final_score > 100) $this->final_score = 100;
        if ($this->final_score < 0) $this->final_score = 0;
    }
    
    public function normalize_grade($dim,$grade) {
        //todo: weight? is weight a factor here? probably should be...
        $dimmin = $dim->min;
        $dimmax = $dim->max;
        if ($dimmin == $dimmax) {
            return grade_floatval($dimmax);
        } else {
            return grade_floatval(($grade - $dimmin) / ($dimmax - $dimmin) * 100);
        }
    }
    
}