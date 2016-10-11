/*

This is the interface for your question plugins' AMD module. Your plugin MUST implement a module that returns
a function whose prototype conforms to the Question interface. You MAY define other functions on your class
for use within your plugin.

*/

/**
 * Your constructor must conform to this signature, even if you do not use some of these parameters.
 * 
 * @param container {jQuery} The top level of your question. Insert your content in this container.
 * @param teameval {int} The ID of the team evaluation instance. Useful to pass to web services.
 * @param self {bool} If self-evaluation is enabled.
 * @param editable {bool} If this user can edit this team evaluation. (Do not use as a replacement for guard_capability!)
 * @param questionID {int|null} The question ID for this question
 * @param context {Object|null} Context data provided by your question subclass
 */
function Question(container, teameval, self, editable, questionID, context) {}

/**
 * Replace the contents of container with the submitter's view.
 * @return {Promise} A promise that resolves when the view has changed.
 */
Question.prototype.submissionView = function() {}

/**
 * Replace the contents of container with the editing view.
 * @return {Promise} A promise that resolves when the view has changed.
 */
Question.prototype.editingView = function() {}
    
/**
 * Save question data back to the database in Moodle. You must use should_update_question/update_question.
 * @param ordinal {int} The index of this question in the questionnaire. You must pass this to update_question().
 * @return {Promise} A promise that resolves with the question ID when the save is complete.
 */
Question.prototype.save = function(ordinal) {};

/**
 * Delete the question in Moodle. You must use should_delete_question/delete_question.
 * @return {Promise} A promise that resolves when the question has been deleted.
 */
Question.prototype.delete = function() {};

/**
 * Submit this response to Moodle. You should check if the user can submit using can_submit_response.
 * @return {Promise} A promise that resolves when the response has been submitted.
 */
Question.prototype.submit = function() {};