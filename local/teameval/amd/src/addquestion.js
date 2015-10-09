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

	"use strict";

	var _cmid;

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
			var coords = _addButton.position();

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
			var _this = this;
			var context = {'_newquestion' : true, '_cmid': _cmid};
			templates.render('teamevalquestion_'+type+'/editing_view', context).done(function(html, js) {
				var question = $('<li class="local-teameval-question editing" />');
				question.data('questiontype', type);

				var questionContainer = $('<div class="question-container" />');
				questionContainer.html(html);

				question.append(questionContainer);
				$('#local-teameval-questions').append(question);
				templates.runTemplateJS(js);

				// after we've run JS we can add our edit and delete buttons

				templates.render('local_teameval/question_actions', {}).done(function(html, js) {

					var actionBar = $(html);
					question.prepend(actionBar);
					actionBar.find('.edit').click(function() {
						_this.editQuestion(question);
					});
					actionBar.find('.delete').click(function() {
						_this.deleteQuestion(question);
					})

					// because we're already in editing view, we hide the editing button
					actionBar.find('.edit').hide();

				})

				// and our Save and Cancel buttons for editing

				templates.render('local_teameval/save_cancel_buttons', {}).done(function(html, js) {
					var buttonArea = $(html);
					buttonArea.find(".save").click(function() {
						_this.saveQuestion(question);
					});
					buttonArea.find(".cancel").click(function() {
						_this.showQuestion(question);
					});
					question.append(buttonArea);
				});

			});
		},

		editQuestion: function(question) {

			var editingContext = question.data('editingcontext') || {};
			var questionType = question.data('questiontype');

			editingContext._cmid = _cmid;

			question.find('.local-teameval-question-actions .edit').hide();

			templates.render('teamevalquestion_'+questionType+'/editing_view', editingContext).done(function(html, js) {

				var questionContainer = question.find('.question-container');
				question.addClass('editing');
				questionContainer.html(html);
				templates.runTemplateJS(js);
				question.find('.local-teameval-save-cancel-buttons').show();

			}).fail(function () {

				alert('wat');
				question.find('.local-teameval-question-actions .edit').show();

			});

		},

		saveQuestion: function(question) {

			// todo: do save

			var questionContainer = question.find('.question-container');
			questionContainer.triggerHandler("save").done(function(submissionContext, editingContext) {
				question.data('editingcontext', editingContext);
				question.data('submissioncontext', submissionContext);
				this.showQuestion(question);
			}.bind(this));

		},

		showQuestion: function(question) {

			var submissionContext = question.data('submissioncontext') || {};
			var questionType = question.data('questiontype');

			submissionContext._cmid = _cmid;

			templates.render('teamevalquestion_'+questionType+'/submission_view', submissionContext).done(function(html, js) {

				question.removeClass('editing');
				question.find('.question-container').html(html);
				question.find('.local-teameval-save-cancel-buttons').hide();
				question.find('.local-teameval-question-actions .edit').show();

			}).fail(function(err) {

				alert("fail");
				console.log(err);

			});

		},

		deleteQuestion: function(question) {
			var questionContainer = question.find('.question-container');
			questionContainer.triggerHandler("delete").done(function() {
				question.remove();
			});
		},

		initialise: function(cmid, subplugins) {

			_cmid = cmid;
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
				addQuestionButton.html('<a href="javascript:void(0);">' + _['addquestion'] + '</a>');
				questionContainer.after(addQuestionButton);

				addQuestionButton.find('a').click(_this.preAddQuestion.bind(_this));

				_addButton = addQuestionButton;
				
			});

		}

	}

});