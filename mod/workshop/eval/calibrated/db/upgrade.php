<?php

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/locallib.php');

function xmldb_workshopeval_calibrated_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    // Moodle v2.2.0 release upgrade line

    if ($oldversion < 2014063001) {
        
        $table = new xmldb_table('workshopeval_calibrated');
        
        //Check if this upgrade has already happened behind our backs.
        if ($dbman->field_exists($table, 'comparison')) {
        
            // Okay, so... the old calibration scores are not actually stored anywhere
            // A user could have a calibration score of 100 and a gradinggrade of 0
            // if they didn't complete any of their actual assessments
        
            // So... we have to actually calibrate them all. Again.
        
            // First we copy across our calibration settings, transferring ownership to
            // workshopcalibration_examples
        
            $settings = $DB->get_records('workshopeval_calibrated', null, null, 'workshopid, comparison, consistency');
        
            // Then let's pull out all our workshops using calibration        
        
            $rs = $DB->get_recordset('workshop',array('evaluation' => 'calibrated'));
        
            foreach($rs as $pk => $record) {
            
            
                $record->usecalibration = true;
                $record->calibrationmethod = 'examples';
            
                switch($record->examplesmode) {
                    case workshop::EXAMPLES_BEFORE_SUBMISSION:
                        $record->calibrationphase = 10;
                        break;
                    case workshop::EXAMPLES_BEFORE_ASSESSMENT:
                        $record->calibrationphase = 20;
                        break;
                    default: // this should never happen but w/ev
                        $record->calibrationphase = 10;
                        break;
                }
            
                $DB->update_record('workshop', $record);
            
                $course     = $DB->get_record('course', array('id' => $record->course), '*', MUST_EXIST);
                $cm         = get_coursemodule_from_instance('workshop', $record->id, $course->id, false, MUST_EXIST);
            
                $workshop = new workshop($record, $cm, $course);
                $calibrator = $workshop->calibration_instance();
                $calibrator->calculate_calibration_scores($settings[$workshop->id]);
            
            }
        
            // First add the one remaining field: "Adjust grades"
        
            $field = new xmldb_field('adjustgrades',XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            $dbman->drop_field($table, new xmldb_field('comparison', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL));
            $dbman->drop_field($table, new xmldb_field('consistency', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL));
            
        }
        
        upgrade_plugin_savepoint(true, 2014063001, 'workshopeval', 'calibrated');
    }
    
    return true;
}
    
?>