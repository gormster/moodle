<?php

namespace local_teameval;

interface question {
    
    /**
     * @param team_evaluation $teameval this teameval instance
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
     * 
     * You MUST attach an event handler for the "delete" event. This handler must return
     * a $.Deferred whose results will be ignored.
     *
     * You MUST attach an event handler for the "submit" event. This handler must return
     * a $.Deferred whose results should be an object with an 'incomplete' property indicating
     * if the submitted data was a complete response to the question. If there is an error 
     * in submission, return a non-200 status.
     *
     * You should return a version that cannot be edited if $locked is set to true.
     *
     * You should indicate that the form is incomplete after the first "submit" event
     * or if $locked is true. You should set the CSS class "incomplete" on your template's
     * direct ancestor if you do.
     */
    public function submission_view($userid, $locked = false);
    
    /**
     * The view that an editing user should see. Rendered with editing_view.mustache
     * When being created for the first time, a question's editing view will be rendered
     * with a context consisting of just one key-value pair: _newquestion: true. This
     * template must render properly without any context.
     *
     * You MUST attach an event handler for the "save" event to the parent .question-container.
     * This event must return a $.Deferred which will resolve with the new 
     * question data which will be returned from $this->submission_view.
     *
     * Once submitting users have started submitting responses to your question, you should
     * prevent editing users from changing aspects of your question that would affect marks.
     * For example, in the Likert question, you could no longer change the minimum and maximum
     * values. However, you may allow some aspects of your question to be edited, such as
     * the title or description. It's up to you to ensure that users don't edit your question
     * in such a way that the responses become unreadable.
     *
     * @return stdClass|array template data. @see templatable
     */
    public function editing_view();

    /**
     * Return the name of this teamevalquestion subplugin
     * @return type
     */
    public function plugin_name();

    public function has_value();

    /**
     * Does this question contribute toward completion? has_value must be false if this is true.
     * @return bool
     */
    public function has_completion();

    public function minimum_value();

    public function maximum_value();

    public function get_title();

    /**
     * If this function returns true, the corresponding response class must implement response_feedback
     * @see response_feedback
     * @return bool 
     */
    public function has_feedback();

    /**
     * Return true if the feedback given by your question should not be associated with the person
     * who left that feedback when shown to the target of that feedback. Teacher roles can always
     * see who gave feedback.
     * @return bool
     */
    public function is_feedback_anonymous();

    public function render_for_report($groupid = null);

    /**
     * Make a new copy of this question. We handle calling should_update_question and update_question.
     * @param int $questionid The ID of the old question
     * @param team_evaluation $newteameval The new team evaluation your question is being copied into.
     * @return int The questionid for the new question (that would normally be passed to update_question)
     */
    public static function duplicate_question($questionid, $newteameval);

    /**
     * Delete these questions from disk.
     * @param array $questionids The plugin-local question ID you passed to should_update_question
     */
    public static function delete_questions($questionids);

    /**
     * Reset user data for these questions, including responses.
     * @param array $questionids The plugin-local question ID you passed to should_update_question
     */
    public static function reset_userdata($questionids);
    
}