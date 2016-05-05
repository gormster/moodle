/*
 * @package    local_teameval
 * @copyright  2015 Morgan Harris
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 /**
  * Submit questionnaire button for teameval blocks
  * @module local_teameval/submitquestion
  */
define(['jquery', 'core/templates', 'core/ajax', 'core/notification', 'core/str'], function($, templates, ajax, notification, Str) {

	var _cmid;

	return {

		submit: function() {

			var questions = $('#local-teameval-questions');
			var promises = [];
			questions.find('.question-container').each(function() {

				var uiblocker = $('<div class="ui-blocker" />');
				$(this).append(uiblocker);
				var p = $(this).triggerHandler("submit");
				promises.push(p);
				p.always(function () {
					uiblocker.remove();
				});

			});

			var allPromises  = $.when.apply($, promises);
			allPromises.done(function() {
                var invalids = arguments.filter(function(el) {
                    return el.valid === false;
                }).length;
                if (invalids > 0) {
                    Str.get_string('ninvalidquestions', 'local_teameval', invalids)
                        .done(function(string) {
                        $('.local-teameval-submit-buttons .results.invalid').text(string).show('fast');    
                    });
                } else {
                    $('.local-teameval-submit-buttons .results.saved').show('fast').delay(5000).hide('fast');
                }
			}).fail(notification.exception);

		},

		initialise: function(cmid) {
			
			_cmid = cmid;

			templates.render('local_teameval/submit_buttons', {}).done(function(html, js) {
				var questionContainer = $('.local-teameval-containerbox');
				questionContainer.append(html);
				templates.runTemplateJS(js);

				$('.local-teameval-submit-buttons .submit').click(this.submit);
			}.bind(this));

		}

	};

});