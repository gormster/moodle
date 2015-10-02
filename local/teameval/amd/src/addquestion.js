/*
 * @package    local_teameval
 * @copyright  2015 Morgan Harris
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 /**
  * Add question button for teameval blocks
  * @module local_teameval/addquestion
  */
define(['jquery', 'core/str'], function($, str) {

	return {

		initialise: function(subplugins) {

			var stringsNeeded = ['addquestion'];

			var promise = str.get_strings(stringsNeeded.map(function (v) {
				return {key: v, component: 'local_teameval'};
			}));

			// we can't continue until we have some text!
			promise.done(function(_strings) {

				// The underscore (_) object holds all the strings, keyed to their original keys
				var _ = {};
				for (var i = _strings.length - 1; i >= 0; i--) {
					_[stringsNeeded[i]] = _strings[i];
				};

				// Find the question container and add the button after it
				var questionContainer = $('#local-teameval-questions');
				var addQuestionButton = $('<div id="local-teameval-add-question" class="mdl-right" />');
				addQuestionButton.html('<a href="#">' + _['addquestion'] + '</a>');
				questionContainer.after(addQuestionButton);
				
			});

		}

	}

});