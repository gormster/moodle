/*
 * @package    local_teameval
 * @copyright  2015 Morgan Harris
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 /**
  * @module local_teameval/settings
  */
define(['jquery', 'core/ajax', 'core/templates', 'core/notification'], function($, ajax, templates, notification) {
	return {
		initialise: function(cmid, settings) {
			this._cmid = cmid;
			this._settings = settings;

			// Add a button to show the settings
			var settings_button = $("<div id='local-teameval-settings-button' />");
			$('.local-teameval-containerbox').prepend(settings_button);

			// We need to add stuff to this later, but `this` will be redefined, so keep a handle on it
			var _this = this;

			// When we click the button, show the settings container and populate it.
			settings_button.click(function() {

				if(!_this._container) {
					// make the container if it doesn't already exist
					var container = $("<div class='local-teameval-settings-container' />");
					container.insertAfter(settings_button);

					container.html("Loading...");

					_this.renderSettingsForm(container);

					// We'll need to hide this later, so keep a handle on it
					_this._container = container;
				} else {
					_this._container.toggle();
				}

			});

		},

		renderSettingsForm: function(container) {
			var _this = this;

			//templates.render dumps a bunch of shit in its context, so we need to make a copy
			var context = $.extend({}, this._settings);

			// Todo: is it possible to deliver the template with the page?
			templates.render('local_teameval/settings_form', context).done(function(html, js) {

                container.html(html);

                // get the save and cancel buttons
                var saveButton = container.find('#local-teameval-settings-save-button');
                var cancelButton = container.find('#local-teameval-settings-cancel-button');

                saveButton.click(function() {
                	_this._settings.enabled = container.find('#local-teameval-settings-enabled').is(':checked');
                	_this._settings.public = container.find('#local-teameval-settings-public').is(':checked');
                	_this._settings.fraction = container.find('#local-teameval-settings-fraction').val();
                	_this._settings.noncompletionpenalty = container.find('#local-teameval-settings-noncompletionpenalty').val();
                	_this.updateSettings(_this._settings);
                });

                cancelButton.click(function() {
                	//reset back to _this._settings
                	_this.renderSettingsForm(container);
                	container.hide();
                });

            }).fail(notification.exception);
		},

		updateSettings: function(newSettings) {
			promises = ajax.call([{
					methodname: 'local_teameval_update_settings',
					args: { 
						'cmid' : this._cmid,
						'settings' : newSettings
					}
			}]);

			var saveButton = this._container.find('#local-teameval-settings-save-button');
			saveButton.prop('disabled',true);
			saveButton.html('Saving...');

			var _this = this;
			promises[0].done(function(data) {
				_this._container.html("Done");
				_this._container.hide();
				_this._container.html("Save");
				saveButton.prop('disabled',false);
			}).fail(notification.exception);
		}
	};

});