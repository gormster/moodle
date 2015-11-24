define(['jquery', 'core/ajax', 'core/notification'], function($, ajax, notification) {

return {

    ajaxify: function(form) {
        $(form).submit(function(evt) {
            evt.preventDefault();
            this.put(form);
        }.bind(this));
    },

    put: function(form) {

        // The webservice callback to issue is stored as data-ajaxfroms-callback on the form element
        var call = $(form).data('ajaxforms-callback');

        // We need to collect the form data. Because it might be nested, we can't just use FormData objects.
        var params = {};

        $(form).find('input, select, textarea').each(function() {
            var name = $(this).get(0).name;
            if ((typeof name == 'undefined') || name.length == 0) {
                // there are some input elements without names. these will not be submitted.
                return;
            }

            var val = $(this).val();

            if($(this).is('input[type=checkbox], input[type=radio]')) {
                // workaround for checkboxes & radio buttons
                val = $(this).prop('checked');
            }

            // Names are nested like name[subname][subname].
            // We can get these out with a regex split.
            var names = name.split(/\[([a-zA-Z0-9_-]+)\]/);

            // Thanks to the way .split works there will be empty strings in this array
            names = names.filter(function(x) { return x.length > 0; });

            // insertInto is the object we're eventually going to insert into
            // We work our way down the names keypath until we find the right object
            // We stop one short of the last object, hence `i < names.length - 1`
            var insertInto = params;
            for (var i = 0; i < names.length - 1; i++) {
                var n = names[i];
                if(typeof insertInto[n] === 'undefined') {
                    insertInto[n] = {};
                }
                insertInto = insertInto[n];
            }

            // We stopped one short of the last object because we're going to set it now
            var lastName = names[names.length - 1];
            insertInto[lastName] = val;

        });

        promises = ajax.call([{
            methodname: call,
            args: {'form': params}
        }]);

        promises[0].done(function() {
            alert('holy crap it workd');
        });

        promises[0].fail(notification.exception);
    }

}
});