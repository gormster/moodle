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

	var _searchBar;

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
					var question = _this.addQuestion(type);
					_this.editQuestion(question);
				}
				dropdown.remove();
			});

		},

		addQuestion: function(type) {
			var _this = this;
			var context = {'_newquestion' : true, '_id': _id, '_self': _self};
			var question = $('<li class="local-teameval-question" />');
			question.data('questiontype', type);
			var questionContainer = $('<div class="question-container" />');
			question.append(questionContainer);
			$('#local-teameval-questions').append(question);
			_this.addEditingControls(question);
			return question;
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
				return {type: $(this).data('questiontype'), id: $(this).data('questionid')};
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

		addFromTemplate: function() {
			var templateid = _searchBar.data('template-id');

			if (templateid > 0) {

				var _this = this;

				var promises = ajax.call([{
					methodname: 'local_teameval_add_from_template',
					args: {
						from: templateid,
						to: _id
					}
				}]);

				promises[0].done(function(questions) {
					for (var i = 0; i < questions.length; i++) {
						var qdata = questions[i];
						var question = _this.addQuestion(qdata.type);
						question.data('questionid', qdata.questionid);
						question.data('editingcontext', qdata.editingcontext);
						question.data('submissioncontext', qdata.submissioncontext);
						_this.showQuestion(question);
					}
					
				});

				promises[0].fail(notification.exception);
			}
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

			if (!_locked) {

				$('#local-teameval-questions').sortable({
					handle: '.local-teameval-question-actions .move',
					axis: "y",
					update: function() {
						_this.setOrder();
					}
				});

				// We need some strings before we can render the button

				var context = {};
				var promise = templates.render('local_teameval/add_question', context);

				// we can't continue until we have some text!
				promise.done(function(html, js) {

					var rslt = $(html);

					// Find the question container and add the button after it
					var questionContainer = $('#local-teameval-questions');
					questionContainer.after(rslt);

					_addButton = rslt.filter('.local-teameval-add-question');
					_addButton.find('a').click(_this.preAddQuestion.bind(_this));


					var templateSearch = rslt.filter('.local-teameval-template-search');

					var templateAddButton = templateSearch.find('button');
				    templateAddButton.click(_this.addFromTemplate.bind(_this));
				    templateAddButton.prop('disabled', true);

					_searchBar = templateSearch.find('input');
					_searchBar.autocomplete({
						minLength: 2,
						source: function(request, response) {
							var promises = ajax.call([{
								methodname: 'local_teameval_template_search',
								args: {
									id: _id,
									term: request.term
								}
							}]);

							promises[0].done(function(results) {
								response(results);
							});

							promises[0].fail(notification.exception);
						},
						focus: function( event, ui ) {
					        _searchBar.val( ui.item.title );
					        return false;
					      },
						select: function( event, ui ) {
							if (ui.item) {
						        _searchBar.val( ui.item.title );
						        _searchBar.data('template-id', ui.item.id);
						        templateAddButton.prop('disabled', false);
						    } else {
						    	templateAddButton.prop('disabled', true);
						    }
					        return false;
					      }
					}).autocomplete( "instance" )._renderItem = function( ul, item ) {
				      return $( "<li class='local-teameval-template-search-result'>" )
				        .append( "<a class='title'>" + item.title + "</a><br><span class='tags'>Matchin tags: " + item.tags.join(', ') + "</span>" )
				        .appendTo( ul );
				    };

				    _searchBar.focus(function() {
				    	_searchBar.val('');
				    	templateAddButton.prop('disabled', true);
				    });

				    _searchBar.change(function() {
				    	templateAddButton.prop('disabled', true);
				    })
					
				});

			}

		}

	};

});