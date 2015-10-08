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

            $this->settings = $DB->get_record('teameval', array('id' => $this->cm->id));
            
            if ($this->settings === false) {
                $settings = team_evaluation::default_settings();
                $settings->id = $cmid;
                $DB->insert_record('teameval', $settings, false);

                $this->settings = $settings;
            } else {
                // when fetching the record from the DB these are ints
                // we need them to be bools
                $this->settings->enabled = (bool)$this->settings->enabled;
                $this->settings->public = (bool)$this->settings->public;
            }

            unset($this->settings->id);
        }

        // don't return our actual settings object, else it could be updated behind our back
        $s = clone $this->settings;
        return $s;
    }

    public function update_settings($settings) {
        global $DB;

        //fetch settings if they're not set
        $this->get_settings();

        //todo: validate
        foreach(['enabled', 'public', 'fraction', 'noncompletionpenalty', 'deadline'] as $i) {
            if (isset($settings->$i)) {
                $this->settings->$i = $settings->$i;
            }
        }

        $record = clone $this->settings;
        $record->id = $this->cm->id;
        $DB->update_record('teameval', $record);
    }

}

interface question {
    
    /**
     * @param int $cmid the ID of the coursemodule for this teameval instance
     * @param int $questionid the ID of the question. may be null if this is a new question.
     */
    public function __construct($cmid, $questionid = null);
    
    /*

    These next two things are templatables, not renderables. There is a good reason for
    this! Simply put, these are virtually always rendered client-side, via a webservice.
    Teameval can't guarantee that your custom rendering code will run, and indeed it
    almost always won't be. If you need to run code in your view that can't be handled
    by Mustache, include it as Javascript in a {{#js}}{{/js}} block and it will be run
    every time your view is rendered.

    Keep that in mind - it will be run EVERY TIME YOUR VIEW IS RENDERED. Be performant,
    and make sure not to install event handlers twice.

    When the view is added to the DOM hierarchy, its container will have an attribute 
    "data-script-marker". You can use this to find your question in the hierarchy from your
    javascript. This attribute is removed as soon as your javascript has run - so if
    you are doing anything asynchronous, grab a handle before you start, because you 
    will never find it again.

    */

    /**
     * The view that a submitting user should see. Rendered with submission_view.mustache
     * @return stdClass|array template data. @see templatable
     */
    public function submission_view($userid);
    
    /**
     * The view that an editing user should see. Rendered with editing_view.mustache
     * When being created for the first time, a question's editing view will be rendered
     * with a context consisting of just one key-value pair: _newquestion: true. This
     * template must render properly without any context.
     *
     * You MUST attach an event handler for the "save" event to the question element marked with
     * "data-script-marker". This event must return a $.Deferred which will resolve with the new 
     * question data which will be returned from $this->submission_view.
     *
     * @return stdClass|array template data. @see templatable
     */
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