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

define(__NAMESPACE__ . '\REPORT_PLUGIN_PREFERENCE', 'local_teameval_report_plugin');

define(__NAMESPACE__ . '\RELEASE_ALL', 0);
define(__NAMESPACE__ . '\RELEASE_GROUP', 1);
define(__NAMESPACE__ . '\RELEASE_USER', 2);

class team_evaluation {

    protected $id;

    protected $cm;

    protected $context;

    protected $evalcontext;

    protected $settings;

    private static $groupcache = [];

    protected $releases;

    protected $evaluator;

    public function __construct($cmid) {

        $this->cm = get_coursemodule_from_id(null, $cmid);

        $this->context = context_module::instance($cmid);

        $this->get_evaluation_context();
    
    }

    public function get_evaluation_context() {
        global $CFG;

        if (! isset($this->evalcontext)) {
        
            $modname = $this->cm->modname;
            include_once("$CFG->dirroot/mod/$modname/lib.php");

            $function = "{$modname}_get_evaluation_context";
            if (!function_exists($function)) {
                // throw something
                print_error("noevaluationcontext");
            }

            $this->evalcontext =  $function($this->cm);
        }

        return $this->evalcontext;
    }

    protected static function default_settings() {

        //todo: these should probably be site-wide settings

        $settings = new stdClass;
        $settings->enabled = true;
        $settings->public = false;
        $settings->self = true;
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
                $settings->cmid = $this->cm->id;
                
                $this->id = $DB->insert_record('teameval', $settings, false);

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

        //fetch settings if they're not set
        $this->get_settings();

        //todo: validate
        foreach(['enabled', 'public', 'self', 'fraction', 'noncompletionpenalty', 'deadline'] as $i) {
            if (isset($settings->$i)) {
                $this->settings->$i = $settings->$i;
            }
        }

        $record = clone $this->settings;

        if (! isset($this->id)) {
            $this->id = $DB->get_field('teameval', 'id', ['cmid' => $this->cm->id]);
        }

        $record->cmid = $this->cm->id;
        $record->id = $this->id;
        $DB->update_record('teameval', $record);
    }

    public function get_context() {
        return $this->context;
    }

    public function get_coursemodule() {
        return $this->cm;
    }

    // These functions are designed to be called from question subplugins

    /**
     * Ask teameval if a user should be allowed to update a question. Must be called before
     * update_question as the transaction returned from this function must be passed to
     * update_question.
     * 
     * @param string $type The question subplugin type 
     * @param int $id The question ID. 0 if new question.
     * @param int $userid The ID of the user trying to update this question
     * @return moodle_transaction|null Transaction if allowed, or null if not allowed
     */
    public function should_update_question($type, $id, $userid) {
        global $DB;

        if (has_capability('local/teameval:createquestionnaire', $this->context, $userid)) {
            $transaction = $DB->start_delegated_transaction();
            return $transaction;    
        }

        return null;
    }

    /**
     * Update teameval's internal question table. You must pass a transaction returned from
     * should_update_question.
     * 
     * @param moodle_transaction $transaction The transaction returned from should_update_question
     * @param string $type The question subplugin type
     * @param int $id The question ID
     * @param int $ordinal The position of the question in order. This is passed to the save handler.
     */
    public function update_question($transaction, $type, $id, $ordinal) {
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

        $transaction->allow_commit();
    }

    /**
     * Ask teameval if a user should be allowed to delete a question. Must be called before
     * delete_question. The transaction returned from this funciton must be passed to delete_question.
     * @param string $type The question subplugin type
     * @param int $id The question ID
     * @param int $userid The user ID
     * @return moodle_transaction|null A transaction if allowed, else null
     */
    public function should_delete_question($type, $id, $userid) {
        global $DB;

        if (has_capability('local/teameval:createquestionnaire', $this->context, $userid)) {
            $transaction = $DB->start_delegated_transaction();
            return $transaction;    
        }

        return null;
    }

    /**
     * Delete the question from teameval's internal question table. Must be passed a transaction
     * started in should_delete_question.
     * @param moodle_transaction $transaction The transaction from should_delete_question
     * @param string $type The question subplugin type
     * @param int $id The question ID
     */
    public function delete_question($transaction, $type, $id) {
        global $DB;
        $DB->delete_records("teameval_questions", array("cmid" => $this->cm->id, "qtype" => $type, "questionid" => $id));
        
        $transaction->allow_commit();
    }

    public function can_submit_response($type, $id, $userid) {
        global $DB;

        //first verify that the quesiton is in this teameval
        $isquestion = $DB->count_records("teameval_questions", array("cmid" => $this->cm->id, "qtype" => $type, "questionid" => $id));

        if ($isquestion > 0) {
            return has_capability('local/teameval:submitquestionnaire', $this->context, $userid);
        }

        return false;
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
            $questioninfo->question = new $cls($this, $bareq->questionid);
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

    /**
     * Is this group ready to receive their adjusted marks?
     * @param int $groupid The group in question
     * @return bool If the group is ready
     */ 
    protected function group_ready($groupid) {

        $members = $this->_groups_get_members($groupid);
        $questions = $this->get_questions();

        $ready = true;

        foreach($questions as $q) {
            $response_cls = $q->plugininfo->get_response_class();

            foreach($members as $m) {
                $response = new $response_cls($this, $q->question, $m->id);
                if( $response->marks_given() == false ) {
                    $ready = false;
                    break;
                }
            }

            if ($ready == false) break;
        }

        return $ready;

    }

    /**
     * Returns the percentage completion of a user as 0..1
     * @param int $uid the id of the User
     * @return float the completion index
     */
    public function user_completion($uid) {
        $questions = $this->get_questions();
        $marks_given = 0;
        foreach($questions as $q) {
            $response_cls = $q->plugininfo->get_response_class();
            $response = new $response_cls($this, $q->question, $uid);
            if ($response->marks_given()) {
                $marks_given++;
            }
        }

        return $marks_given / count($questions);
    }

    public function get_evaluator() {

        if (! isset($this->evaluator)) {

            $evaluators = core_plugin_manager::instance()->get_plugins_of_type("teamevaluator");

            $plugininfo = current( $evaluators );
            $evaluator_cls = $plugininfo->get_evaluator_class();

            $markable_users = $this->evalcontext->marking_users();

            $questions = $this->get_questions();
            $responses = [];
            foreach($questions as $q) {
                $response_cls = $q->plugininfo->get_response_class();
                foreach($markable_users as $m) {
                    $response = new $response_cls($this, $q->question, $m->id);
                    $responses[$m->id][] = $response;
                }
            }

            $this->evaluator = new $evaluator_cls($this, $responses);

        }

        return $this->evaluator;

    }

    protected function score_to_multiplier($score, $uid) {
        $fraction = $this->get_settings()->fraction;
        $multiplier = (1 - $fraction) + ($score * $fraction);

        $noncompletion = $this->get_settings()->noncompletionpenalty;
        $completion = $this->user_completion($uid);
        $penalty = $noncompletion * (1 - $completion);

        $multiplier -= $penalty;

        return $multiplier;
    }

    public function multipliers() {
        $eval = $this->get_evaluator();

        $scores = $eval->scores();

        $multipliers = [];

        foreach($scores as $uid => $score) {
            $multipliers[$uid] = $this->score_to_multiplier($score, $uid);
        }

        return $multipliers;
    }

    /**
     * Returns the score multipliers for a particular group
     * @param int $groupid The ID of the group in question
     * @return array(int => float) User ID to score multiplier
     */
    public function multipliers_for_group($groupid) {

        $users = $this->_groups_get_members($groupid);
        $scores = $eval->scores();

        $multipliers = [];

        foreach($users as $uid => $user) {
            $multipliers[$uid] = $this->score_to_multiplier($scores[$uid]);
        }

        return $multipliers;

    }

    public function multiplier_for_user($userid) {
        $eval = $this->get_evaluator();
        $scores = $eval->scores();

        if (! isset($scores[$userid])) {
            return null;
        }
        
        $score = $eval->scores()[$userid];

        return $this->score_to_multiplier($score, $userid);
    }

    public function set_report_plugin($plugin) {
        set_user_preference(REPORT_PLUGIN_PREFERENCE, $plugin);
    }

    public function get_report_plugin() {
        $plugin = get_user_preferences(REPORT_PLUGIN_PREFERENCE, 'scores');
        return core_plugin_manager::instance()->get_plugin_info("teamevalreport_$plugin");
    }

    public function get_report() {
        // TODO: site-wide default report
        $plugininfo = $this->get_report_plugin();
        $cls = $plugininfo->get_report_class();

        $report = new $cls($this);

        return $report->generate_report();
    }


    // interface to evalcontext

    public function group_for_user($userid) {
        return $this->evalcontext->group_for_user($userid);
    }

    public function all_groups() {
        return $this->evalcontext->all_groups();
    }

    public function marking_users() {
        return $this->evalcontext->marking_users();
    }

    // convenience functions

    /**
     * Gets the teammates in a user's team.
     * @param int $userid User to get the teammates for
     * @param bool $include_self Include user in teammates. Defaults to $this->settings->self.
     * @return type
     */
    public function teammates($userid, $include_self=null) {

        if (is_null($include_self)) {
            $include_self = $this->get_settings()->self;
        }

        $group = $this->group_for_user($userid);

        $members = $this->_groups_get_members($group->id);
        
        if($include_self == false) {
            unset($members[$userid]);
        }

        return $members;
    }

    /**
     * Cached version of groups_get_members.
     * @param type $groupid 
     * @return type
     */
    private function _groups_get_members($groupid) {
        $groupcache = self::$groupcache;
        if (!isset($groupcache[$groupid])) {
            $members = groups_get_members($groupid);   
            $groupcache[$groupid] = $members; 
        } else {
            $members = $groupcache[$groupid];
        }
        return $members;
    }

    // MARK RELEASE

    public function release_marks_for($target, $level, $set) {
        global $DB;

        $release = new stdClass;
        $release->cmid = $this->cm->id;
        $release->target = $target;
        $release->level = $level;

        // try to get a record which matches this.
        $record = $DB->get_record('teameval_release', (array)$release);

        if (($set == true) && ($record === false)) {
            $DB->insert_record('teameval_release', $release);
        }

        if (($set == false) && ($record !== false)) {
            $DB->delete_records('teameval_release', (array)$record);
        }

        $this->releases[] = $release;

        // figure who we need to trigger grades for
        if ($level == RELEASE_ALL) {
            $this->evalcontext->trigger_grade_update();
        } else if ($level == RELEASE_GROUP) {
            $users = $this->_groups_get_members($target);
            $this->evalcontext->trigger_grade_update(array_keys($users));
        } else if ($level == RELEASE_USER) {
            $this->evalcontext->trigger_grade_update([$target]);
        }
    }

    public function release_marks_for_all($set = true) {
        $this->_release_marks_for(0, RELEASE_ALL, $set);
    }

    public function release_marks_for_group($groupid, $set = true) {
        $this->_release_marks_for($groupid, RELEASE_GROUP, $set);
    }

    public function release_marks_for_user($userid, $set = true) {
        $this->_release_marks_for($userid, RELEASE_USER, $set);
    }

    public function marks_available($userid) {
        global $DB;
        // First check if the marks are released.

        $releases = $DB->get_records('teameval_release', ['cmid' => $this->cm->id], 'level ASC');
        $grp = $this->group_for_user($userid);

        $is_released = false;

        foreach($releases as $release) {
            if ($release->level == RELEASE_ALL) {
                $is_released = true;
                break;
            }

            if ($release->level == RELEASE_GROUP) {
                if ($release->target == $grp->id) {
                    $is_released = true;
                    break;
                }
            }

            if ($release->level == RELEASE_USER) {
                if ($release->target == $userid) {
                    $is_released = true;
                    break;
                }
            }
        }

        if ($is_released == false) {
            return false;
        }

        // Next check if everyone in their group has submitted OR the deadline has passed

        if ($this->get_settings()->deadline < time()) {
            return true;
        }

        if ($this->group_ready($grp->id)) {
            return true;
        }

        return false;

    }

}

interface question {
    
    /**
     * @param int $cmid the ID of the coursemodule for this teameval instance
     * @param int $questionid the ID of the question. may be null if this is a new question.
     */
    public function __construct(team_evaluation $teameval, $questionid = null);

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

    /**
     * Return the name of this teamevalquestion subplugin
     * @return type
     */
    public function plugin_name();

    public function has_value();

    public function minimum_value();

    public function maximum_value();
    
}

interface response {
    
    /**
     * @param team_evaluation $teameval the teamevaluation object this response belongs to
     * @param question $question the question object of the question this is a response to
     * @param int $userid the ID of the user responding to this question
     */
    public function __construct(team_evaluation $teameval, $question, $userid);
    
    /**
     * @return int Response ID
     */
    public function update_response($formdata);

    /**
     * @return bool Has a response been given by this user?
     */
    public function marks_given();

    /**
     * What is this user's opinion of a particular teammate? Scaled from 0.0 to 1.0
     * @param type $userid Team mate's user ID
     * @return type
     */
    public function opinion_of($userid);
    
}

interface evaluator {

    /**
     * Constructor.
     * @param team_evaluation $teameval The team evaluation object this evaluator is evaluating
     * @param array $responses [userid => [response object]]
     */
    public function __construct(team_evaluation $teameval, $responses);

    /**
     * The team evaluator scores, which are the basis for adjusting marks.
     * @return array [userid => float]
     */
    public function scores();

}

interface report {

    public function __construct(team_evaluation $teameval);

    /**
     * Generate and return a renderable report.
     * @return type
     */
    public function generate_report();

}


//copied from core_component. why this is not a global function...
function is_developer() {
    global $CFG;

    // Note we can not rely on $CFG->debug here because DB is not initialised yet.
    if (isset($CFG->config_php_settings['debug'])) {
        $debug = (int)$CFG->config_php_settings['debug'];
    } else {
        $debug = $CFG->debug;
    }

    if ($debug & E_ALL and $debug & E_STRICT) {
        return true;
    }

    return false;
}


?>