/*
 * @package    local_teameval
 * @copyright  2015 Morgan Harris
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 /**
  * @module local_teameval/settings
  */
define(['jquery'], function($) {
	return {
		initialise: function() {
			var settings_button = $("<div id='local-teameval-settings-button' />");
			$('.local-teameval-containerbox').prepend(settings_button);
			settings_button.click(function() {

				window.alert("you clicked the settings icon");

			});
		}
	};

});