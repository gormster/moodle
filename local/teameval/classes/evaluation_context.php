<?php

namespace local_teameval;

require_once(dirname(dirname(__FILE__)) . '/lib.php');

abstract class evaluation_context {

    protected $cm;

    /**
     * You must call parent::__construct in your constructor.
     */
    public function __construct($cm) {
        $this->cm = $cm;
    }

    /**
     * Should evaluation be shown to this or any user?
     * @param type|null $userid If null, check if evaluation is even possible in this context
     * @return bool
     */
	abstract public function evaluation_permitted($userid = null);

    /**
     * What group is this user associated with?
     * @param type $userid User ID
     * @return stdClass groups record
     */
	abstract public function group_for_user($userid);

    /**
     * Every group that might be returned by group_for_user
     * @return type
     */
    abstract public function all_groups();

    /**
     * Which users are marking in this context?
     * @return [int => stdClass] user id to user records
     */
    abstract public function marking_users();

    /**
     * This is never used to calculate grades, just in reports.
     * @param int id of the group in question
     * @return float grade for group
     */
    abstract public function grade_for_group($groupid);

    /**
     * Called when teameval knows that adjusted grades will have changed
     * Teameval is not responsible for making sure that the users specified herein have
     * been assigned grades in your plugin - you have to check that yourself.
     * @param [int] $users optional array of user ids whose grades have changed
     */
    abstract public function trigger_grade_update($users = null);

    /**
     * Override this if your class isn't in your plugin's namespace, or just for
     * performance's sake.
     */
    public static function plugin_namespace() {
        return explode('\\', get_called_class())[0];
    }

    /**
     * Implement this as get_string('modulenameplural', 'yourmodule')
     */
    public static function component_string() {
        $ns = static::plugin_namespace();
        if (strstr($ns, 'mod_')) {
            return get_string('modulenameplural', substr($ns, 4));
        }
        return get_string('pluginname', $ns);
    }
    /**
     * You can override this function to customise the appearance of Teameval feedback in the gradebook.
     */
    protected function format_feedback($feedbacks) {
        $o = '<h3>Team Evaluation</h3>';
        foreach($feedbacks as $q) {
            $o .= "<h4>{$q->title}</h4><ul>";
            foreach($q->feedbacks as $fb) {
                $feedback = clean_text($fb->feedback);
                if (isset($fb->from)) {
                    $o .= "<li><strong>{$fb->from}:</strong> $feedback</li>";
                } else {
                    $o .= "<li>$feedback</li>";
                }
            }
            $o .= '</ul>';
        }
        return $o;
    }







    /*
     * The above methods were teameval calling in to your plugin. 
     * These are methods for you to call into teameval.
     * You should probably not override these, as teameval uses them as well.
     */

    public static function context_for_module($cm) {
        global $CFG;

        $modname = $cm->modname;
        include_once("$CFG->dirroot/mod/$modname/lib.php");

        $function = "{$modname}_get_evaluation_context";
        if (!function_exists($function)) {
            // throw something
            print_error("noevaluationcontext");
        }

        return $function($cm);
    }

    public function evaluation_enabled() {
        // This can be called even when evaluation is not possible.
        // For this reason we don't use get_settings()
        global $DB;
        $enabled = $DB->get_field('teameval', 'enabled', ['cmid' => $this->cm->id]);
        return (bool)$enabled;
    }

    public function marks_available($userid) {
        $teameval = team_evaluation::from_cmid($this->cm->id);
        return $teameval->marks_available($userid);
    }

    public function update_grades($grades) {

        if (is_object($grades)) {
            $grades = array($grades->userid=>$grades);
        } else if (array_key_exists('userid', $grades)) {
            $grades = array($grades['userid']=>$grades);
        }

        $teameval = team_evaluation::from_cmid($this->cm->id);

        foreach($grades as $userid => $grade) {
            if (!is_object($grade)) {
                $grade = (object)$grade;
                $grades[$userid] = $grade;
            }

            if (isset($grade->rawgrade) && !is_null($grade->rawgrade)) {

                if ($this->marks_available($userid)) {
                    $grade->rawgrade *= $teameval->multiplier_for_user($userid);
                    $feedbacks = $teameval->all_feedback($userid);
                    if(count($feedbacks)) {
                        $grade->feedback .= $this->format_feedback($feedbacks);
                    }
                } else {
                    $grade->rawgrade = null;
                }

            }
        }

        return $grades;
    }
    

    // COURSE RESET

    public static function reset_course_form_definition(&$mform) {

        $ns = static::plugin_namespace() . '_';

        $mform->addElement('static', $ns.'teameval_hr', '', '<hr />');

        $mform->addElement('checkbox', $ns.'reset_teameval_responses', get_string('resetresponses', 'local_teameval'));

        $mform->addElement('checkbox', $ns.'reset_teameval_questionnaire', get_string('resetquestionnaire', 'local_teameval'));
        $mform->disabledIf($ns.'reset_teameval_questionnaire', $ns.'reset_teameval_responses');

    }

    public static function reset_course_form_defaults($course) {
        $ns = static::plugin_namespace() . '_';
        return [$ns.'reset_teameval_responses' => 1, $ns.'reset_teameval_questionnaire' => 0];
    }

    public function reset_userdata($options) {
        global $DB;

        $ns = static::plugin_namespace() . '_';

        $resetresponses = $ns . 'reset_teameval_responses';
        $resetresponses = !empty($options->$resetresponses);
        $resetquestionnaire = $ns . 'reset_teameval_questionnaire';
        $resetquestionnaire = !empty($options->$resetquestionnaire);

        $status = [];

        if ($this->evaluation_enabled()) {

            $teameval = team_evaluation::from_cmid($this->cm->id);

            if ($resetresponses) {

                $status[] = $teameval->reset_userdata();

                if ($resetquestionnaire) {
                    $status[] = $teameval->delete_questionnaire();
                } else {
                    $teameval->reset_questionnaire();
                }

            }

        }

        return $status;

    }



}
