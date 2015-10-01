<?php

namespace local_teameval;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

/**
 * This is about all you have to call from your mod plugin to show teameval
 */

use renderable, core_plugin_manager;

class team_evaluation_block implements renderable {

    public $cm;
    
    public $subplugins;

    /**
     * @param int $cmid This is the cmid of the activity module this teameval belongs to
     */
    public function __construct($cmid) {
        $this->cm = get_coursemodule_from_id(null, $cmid);
        
        $this->subplugins = core_plugin_manager::instance()->get_plugins_of_type("teamevalquestion");
        
    }

}

class team_evaluation {

    protected $cm;

    protected $settings;

    public function __construct($cmid) {

        global $DB;

        $this->cm = get_coursemodule_from_id(null, $cmid);
        $this->settings = $DB->get_record('teameval', array('cmid' => $cmid));
        if ($this->settings === false) {
            $this->settings = team_evaluation::default_settings;
            $this->settings->cmid = $cmid;
        }

    }

    protected static function default_settings() {

        //todo: these should probably be site-wide settings

        $settings = new stdClass;
        $settings->public = false;
        $settings->fraction = 0.5;
        $settings->noncompletionpenalty = 0.1;
        $settings->deadline = null;

        return $settings;
    }

    public function get_settings()
    {
        // don't return our actual settings object, then it could be updated behind our back
        $s = clone $settings;
        return $s;
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