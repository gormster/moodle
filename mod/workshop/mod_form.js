function init() {
	var uc = $('#id_usecalibration');
	
	uc.change(function(evt) {
		updateCalibration(true);
	});
	
	updateCalibration(false);
	
}

function updateCalibration(animated) {
	var uc = $('#id_usecalibration');
	var ue = $("#id_useexamples");
	var cp = $("#id_calibrationphase");
	var em = $("#id_examplesmode");
	var ec = $("#id_examplescompare");
	var er = $("#id_examplesreassess");
	
	var checked = uc.prop("checked");
	
	if (checked) {
		ue.prop({checked: true, disabled: true});
		em.prop({disabled: true});
		
		//set up the binding between the two selects
		cp.bind('change', updateExamplePhase);
		updateExamplePhase();
		
		//set up preventing examples from simultaneous compare/reassess
		if (ec.prop('checked') && er.prop('checked')) {
			
			if (animated) {
				
				$("#id_examplesubmissionssettings").removeClass("collapsed");
				var div = er.closest('.fitem');
				div.css('position','relative');
				var bg = $("<div style='background-color: #fff3a5; position: absolute; left:0px; right:0px; top:0px; bottom:0px; z-index:-1;' />");
				div.prepend(bg);
				div.fadeOut(100).fadeIn(100).fadeOut(100).fadeIn(100).fadeOut(100).fadeIn(100, function() {
					er.prop({checked: false});
					bg.fadeOut(2000, function() {
						bg.remove();
					});
				});
			} else {
				er.prop({checked: false});
			}
			
		}
		
		// These two are now mutually exclusive
		ec.bind('change', updateExamplesOptions);
		er.bind('change', updateExamplesOptions);
		
	} else {
		ue.prop({disabled: false});
		em.prop({disabled: false});
		cp.unbind('change', updateExamplePhase);
		er.unbind('change', updateExamplesOptions);
		ec.unbind('change', updateExamplesOptions);
	}
}

function updateExamplePhase() {
	
	console.log("updateExamplePhase");
	
	var cp = $("#id_calibrationphase");
	var em = $("#id_examplesmode");
	var val = cp.val();
	switch(val) {
	case '10':
		em.val('1');
		break;
	case '20':
		em.val('2');
		break;
	}
	
}

function updateExamplesOptions(evt) {
	
	var ec = $("#id_examplescompare");
	var er = $("#id_examplesreassess");
	var target = $(evt.target);
	
	if (ec.prop('checked') && er.prop('checked')) {
		ec.prop('checked', false);
		er.prop('checked', false);
		target.prop('checked', true);
	}
	
}