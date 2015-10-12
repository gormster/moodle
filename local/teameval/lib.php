<?php

namespace local_teameval;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

/**
 * This is about all you have to call from your mod plugin to show teameval
 */

use renderable;
use core_plugin_manager;
use stdClass;
use context_module;

class team_evaluation {

    protected $cm;

    protected $context;

    protected $settings;

    public function __construct($cmid) {

        $this->cm = get_coursemodule_from_id(null, $cmid);

        $this->context = context_module::instance($cmid);
    
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

    // These functions are designed to be called from question subplugins

    public function update_question($type, $id, $ordinal) {
        global $DB;

        $record = $DB->get_record("teameval_questions", array("cmid" => $this->cm->id, "qtype" => $type, "questionid" => $id));
        if ($record) {
            $record->ordinal = $ordinal;
            $DB->update_record("teameval_questions", $record);
        } else {
            $record = new stdClass;
            $record->cmid = $this->cm->id;
            $record->qtype = $type;
            $record->questionid = $id;
            $record->ordinal = $ordinal;
            $DB->insert_record("teameval_questions", $record);
        }
    }

    public function delete_question($type, $id) {
        global $DB;
        $DB->delete_records("teameval_questions", array("cmid" => $this->cm->id, "qtype" => $type, "questionid" => $id));
    }

    protected function get_bare_questions() {
        global $DB;
        return $DB->get_records("teameval_questions", array("cmid" => $this->cm->id), "ordinal ASC");
    }

    /**
     * Gets all the questions in this teameval questionnaire, along with some helpful context
     * @return stdClass ->question, ->plugininfo, ->submissiontemplate, ->editingtemplate
     */
    public function get_questions() {
        global $DB;
        $barequestions = $this->get_bare_questions();
        
        $questions = [];
        $questionplugins = core_plugin_manager::instance()->get_plugins_of_type("teamevalquestion");
        foreach($barequestions as $bareq) {
            $questioninfo = new stdClass;

            $questioninfo->plugininfo = $questionplugins[$bareq->qtype];
            $cls = $questioninfo->plugininfo->get_question_class();
            $questioninfo->question = new $cls($this->cm->id, $bareq->questionid);
            $questioninfo->questionid = $bareq->questionid;
            $questioninfo->submissiontemplate = "teamevalquestion_{$bareq->qtype}/submission_view";
            $questioninfo->editingtemplate = "teamevalquestion_{$bareq->qtype}/editing_view";

            $questions[] = $questioninfo;
        }

        return $questions;
    }

    public function questionnaire_set_order($order) {

        global $DB, $USER;

        require_capability('local/teameval:createquestionnaire', $this->context);

        //first assert that $order contains ALL the question IDs and ONLY the question IDs of this teameval
        $records = $DB->get_records("teameval_questions", array("cmid" => $this->cm->id), '', 'id, questionid');
        $ids = array_map(function($record) {
            return $record->questionid;
        }, $records);

        if (count(array_diff($order, $ids)) > 0) {
            throw new moodle_error('questionidsoutofsync', 'teameval');
        }

        // flip the records so that we've got questionids => ids

        $questionids = [];
        foreach($records as $r) {
            $questionids[$r->questionid] = $r;
        }

        // set the ordinals according to $order

        foreach($order as $i => $qid) {
            $r = $questionids[$qid];
            $r->ordinal = $i;
            $bulk = $i == count($order) - 1;
            $DB->update_record('teameval_questions', $r, $bulk);
        }

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
     * You MUST also attach an event handler for the "delete" event. This handler must return
     * a $.Deferred which will resolve with no arguments.
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