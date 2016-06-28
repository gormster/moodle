define(['jquery'], function($) {

	var defaultSettings = {
		'selector' : '.collapsible',
		'target'   : '.label',
		'expanded' : 'expanded',
		'collapsed': 'collapsed',
	}

	return {

		init(settings) {
			var S = $.extend({}, defaultSettings, settings);
			console.log(S);
			$(S.selector).on('click', S.target, function(evt){
				var collapser = $(evt.delegateTarget);
				if (collapser.hasClass(S.expanded)) {
					collapser.removeClass(S.expanded);
					collapser.addClass(S.collapsed);
				} else {
					collapser.removeClass(S.collapsed);
					collapser.addClass(S.expanded);
				}
			});
		}

	}

});