define(['jquery', 'local_teameval/ajaxforms'], function($, AjaxForms) {

    return {
        init: function() {

            var container = $('.local-teameval-settings-container');
            container.css('display','block');
            container.hide();

            var settingsButton = $('.local-teameval-settings-button');
            settingsButton.click(function() {
                container.toggle();
            });

            var form = container.find('form');
            AjaxForms.ajaxify(form, function (promise) {
                var saveButton = container.find('input[type=submit]');
                saveButton.prop('disabled',true);
                saveButton.html('Saving...');

                promise.done(function(data) {
                    container.hide();
                    saveButton.html("Save");
                    saveButton.prop('disabled',false);

                    var releaseTab = $(".local-teameval-containerbox nav.tabs .tab.release");
                    if (data.autorelease) {
                        releaseTab.hide();
                    } else {
                        releaseTab.show();
                    }

                    var deadlineBanner = $(".local-teameval-containerbox .deadline");
                    if (data.deadline) {
                        var d = new Date(data.deadline * 1000);
                        if (window.Intl !== undefined) {
                            var options = {weekday: "long",
                                day: "numeric",
                                month: "long",
                                year: "numeric",
                                hour: "numeric",
                                minute: "numeric"};
                            deadlineBanner.find("time").text(window.Intl.DateTimeFormat([],options).format(d));
                        } else {
                            // it doesn't look right but just print the JS string
                            // the alternative is insanity
                            deadlineBanner.find("time").text(d.toString());
                        }
                        deadlineBanner.removeClass('hidden');
                    } else {
                        deadlineBanner.addClass('hidden');
                    }


                }.bind(this));
            });

        }
    };
});