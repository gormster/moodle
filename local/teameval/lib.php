<?php

namespace local_teameval;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

/**
 * This is about all you have to call from your mod plugin to show teameval
 */

use renderable;
use core_plugin_manager;
use stdClass;

class team_evaluation {

    protected $cm;

    protected $settings;

    public function __construct($cmid) {

        $this->cm = get_coursemodule_from_id(null, $cmid);
    
    }

    protected static function default_settings() {

        //todo: these should probably be site-wide settings

        $settings = new stdClass;
        $settings->enabled = true;
        $settings->public = false;
        $settings->fraction = 0.5;
        $settings->noncompletionpenalty = 0.1;
        $settings->deadline = null;

        return $settings;
    }

    public function get_settings()
    {
    
        global $DB;

        // initialise settings if they're not already
        if (!isset($this->settings)) {

            $this->settings = $DB->get_record('teameval', array('cmid' => $this->cm->id));
            
            if ($this->settings === false) {
                $settings = team_evaluation::default_settings();
                $settings->cmid = $cmid;
                $DB->insert_record('teameval', $settings, false);

                $this->settings = $settings;
            } else {
                // when fetching the record from the DB these are ints
                // we need them to be bools
                $this->settings->enabled = (bool)$this->settings->enabled;
                $this->settings->public = (bool)$this->settings->public;
            }

            unset($this->settings->cmid);
        }

        // don't return our actual settings object, else it could be updated behind our back
        $s = clone $this->settings;
        return $s;
    }

    public function update_settings($settings) {
        global $DB;
        
        //todo: validate
        foreach(['enabled', 'public', 'fraction', 'noncompletionpenalty', 'deadline'] as $i) {
            if (isset($settings->$i)) {
                $this->settings->$i = $settings->$i;
            }
        }

        $DB->update_record('teameval', array('cmid' => $this->cm->id), $this->settings);
    }

}

interface question {
    
    /**
     * @param int $cmid the ID of the coursemodule for this teameval instance
     * @param int $questionid the ID of the question. may be null if this is a new question.
     */
    public function __construct($cmid, $questionid = null);
    
    public function submission_view($userid);
    
    public function editing_view();
    
    /**
     * @return int Question ID
     */
    public function update($formdata);
    
}

interface response {
    
    /**
     * @param int $questionid the ID of the question this is a response to
     * @param int $userid the ID of the user responding to this question
     * @param int $responseid the ID of the response. may be null if this is a new response.
     */
    public function __construct($questionid, $userid, $responseid = null);
    
    /**
     * @return int Response ID
     */
    public function update_response($formdata);
    
}

?>