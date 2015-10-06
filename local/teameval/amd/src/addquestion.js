/*
 * @package    local_teameval
 * @copyright  2015 Morgan Harris
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 /**
  * Add question button for teameval blocks
  * @module local_teameval/addquestion
  */
define(['jquery', 'core/str', 'core/templates'], function($, str, templates) {

	var _subplugins;

	var _addButton;

	return {

		// Ask the user what kind of question they want to add
		preAddQuestion: function(evt) {

			//todo: check that you CAN add a question right now

			//todo: if there's only one question subplugin skip this step and assume they mean that

			//todo: mustache this
			var dropdown = $('<ul class="local-teameval-question-dropdown" />');
			$.each(_subplugins, function(name, subplugin) {
				var li = $("<li />");
				li.html('<a>' + subplugin.displayname + '</a>');
				li.data('type',subplugin.name);
				dropdown.append(li);
			});

			$(".local-teameval-containerbox").append(dropdown);
			coords = _addButton.position();

			dropdown.css('top', coords.top + 'px');
			dropdown.css('right', '0px');

			// If we don't do this, we'll accidentally trigger our click-outside handler
			evt.stopPropagation();

			var _this = this;
			$(document).one('click', function(evt) {
				if ($(evt.target).closest('.local-teameval-question-dropdown').length > 0) {
					var type = $(evt.target).closest('li').data('type');
					_this.addQuestion(type);
				}
				dropdown.remove();
			});

		},

		addQuestion: function(type) {
			templates.render('teamevalquestion_'+type+'/editing_view', {'_newquestion' : true}).done(function(html, js) {
				var question = $('<li class="local-teameval-question" data-justadded="justadded" />');
				question.html(html);

				$('#local-teameval-questions').append(question);
				templates.runTemplateJS(js);
				question.removeAttr("data-justadded");
			});
		},

		initialise: function(subplugins) {

			_subplugins = subplugins;

			// We need some strings before we can render the button

			var stringsNeeded = ['addquestion'];

			var promise = str.get_strings(stringsNeeded.map(function (v) {
				return {key: v, component: 'local_teameval'};
			}));

			var _this = this;

			console.log($.ui);

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
				addQuestionButton.html('<a>' + _['addquestion'] + '</a>');
				questionContainer.after(addQuestionButton);

				addQuestionButton.click(_this.preAddQuestion.bind(_this));

				_addButton = addQuestionButton;
				
			});

		}

	}

});