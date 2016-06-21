/*
 * @package    local_teameval
 * @copyright  2015 Morgan Harris
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 /**
  * Add question button for teameval blocks
  * @module local_teameval/addquestion
  */
define(['jquery', 'jqueryui', 'core/str', 'core/templates', 'core/ajax', 'core/notification'], 
	function($, ui, str, templates, ajax, notification) {

	"use strict";

	var _id;

	var _subplugins;

	var _self;

	var _locked;

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
			var context = {'_newquestion' : true, '_id': _id, '_self': _self};
			templates.render('teamevalquestion_'+type+'/editing_view', context).done(function(html, js) {
				var question = $('<li class="local-teameval-question editing" />');
				question.data('questiontype', type);

				var questionContainer = $('<div class="question-container" />');
				questionContainer.html(html);

				question.append(questionContainer);
				$('#local-teameval-questions').append(question);
				templates.runTemplateJS(js);

				// after we've run JS we can add our edit and delete buttons

				_this.addEditingControls(question);

			});
		},

		addEditingControls: function(question) {
			var _this = this;

			templates.render('local_teameval/question_actions', {locked:_locked}).done(function(html) {

				var actionBar = $(html);
				question.prepend(actionBar);
				actionBar.find('.edit').click(function() {
					_this.editQuestion(question);
				});
				actionBar.find('.delete').click(function() {
					_this.deleteQuestion(question);
				});


				//if we're in editing mode, hide the edit and delete buttons
				if (question.hasClass('editing')) {
					actionBar.hide();
				}

			});

			// and our Save and Cancel buttons for editing

			templates.render('local_teameval/save_cancel_buttons', {}).done(function(html) {
				var buttonArea = $(html);
				buttonArea.find(".save").click(function() {
					_this.saveQuestion(question);
				});
				buttonArea.find(".cancel").click(function() {
					// The cancel button should delete a question if it isn't saved
					if (question.data('questionid') === undefined) {
						_this.deleteQuestion(question);
					} else {
						_this.showQuestion(question);
					}
				});
				question.append(buttonArea);

				if (!question.hasClass('editing')) {
					buttonArea.hide();
				}

			});
		},

		editQuestion: function(question) {

			var editingContext = question.data('editingcontext') || {};
			var questionType = question.data('questiontype');

			editingContext._id = _id;
			editingContext._self = _self;

			// hide the action bar
			question.find('.local-teameval-question-actions').hide();

			templates.render('teamevalquestion_'+questionType+'/editing_view', editingContext).done(function(html, js) {

				var questionContainer = question.find('.question-container');
				question.addClass('editing');

				//disable the events that are registered in submission_view
				questionContainer.html(html).off();
				templates.runTemplateJS(js);
				question.find('.local-teameval-save-cancel-buttons').show();

			}).fail(function () {

				question.find('.local-teameval-question-actions').show();

			});

		},

		saveQuestion: function(question) {

			// todo: do save

			var questionContainer = question.find('.question-container');
			var ordinal = question.index('.local-teameval-question');
			var promise = questionContainer.triggerHandler("save", ordinal);
			promise.done(function(questionID, submissionContext, editingContext) {
				question.data('questionid', questionID);
				question.data('editingcontext', editingContext);
				question.data('submissioncontext', submissionContext);
				this.showQuestion(question);
			}.bind(this));
			promise.fail(function() {
				// at the moment, do nothing
				// rely on the question plugin to relay that saving has failed
			}.bind(this));

		},

		showQuestion: function(question) {

			var submissionContext = question.data('submissioncontext') || {};
			var questionType = question.data('questiontype');

			submissionContext._id = _id;

			templates.render('teamevalquestion_'+questionType+'/submission_view', submissionContext).done(function(html, js) {

				question.removeClass('editing');
				//disable the events that are registered in editing_view
				question.find('.question-container').html(html).off();
				question.find('.local-teameval-save-cancel-buttons').hide();
				question.find('.local-teameval-question-actions').show();

				templates.runTemplateJS(js);

			}).fail(notification.exception);

		},

		deleteQuestion: function(question) {
			if (question.data('questionid') === undefined) {
				// just pull it out of the DOM
				question.remove();
			} else {
				// actually delete it from the database
				var questionContainer = question.find('.question-container');
				
				questionContainer.triggerHandler("delete").done(function() {
					question.remove();
				}).fail(notification.exception);
			}
		},

		setOrder: function() {
			var order = $("#local-teameval-questions li").map(function() {
				return $(this).data('questionid');
			}).filter(function() {
				return this !== undefined;
			}).get();

			var promises = ajax.call([{
				methodname: 'local_teameval_questionnaire_set_order',
				args: {
					id: _id,
					order: order
				}
			}]);

			promises[0].done(function() {
				
			}).fail(notification.exception);
		},

		initialise: function(teamevalid, self, subplugins, locked) {

			_id = teamevalid;
			_self = self;
			_subplugins = subplugins;
			_locked = locked;

			// stupid javascript scoping

			var _this = this;

			// add the controls to the questions already in the block

			$('#local-teameval-questions .local-teameval-question').each(function() {
				_this.addEditingControls($(this));
			});

			if (_locked == false) {

				$('#local-teameval-questions').sortable({
					handle: '.local-teameval-question-actions .move',
					axis: "y",
					update: function() {
						_this.setOrder();
					}
				});

				// We need some strings before we can render the button

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
					}

					// Find the question container and add the button after it
					var questionContainer = $('#local-teameval-questions');
					var addQuestionButton = $('<div id="local-teameval-add-question" class="mdl-right" />');
					addQuestionButton.html('<a href="javascript:void(0);">' + _.addquestion + '</a>');
					questionContainer.after(addQuestionButton);

					addQuestionButton.find('a').click(_this.preAddQuestion.bind(_this));

					_addButton = addQuestionButton;
					
				});

			}

		}

	};

});