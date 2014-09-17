function set_flagging_on(o, id) {
    var a = o.checked;

    YUI().use('node', 'io-base', 'querystring-stringify-simple', function(Y) {

        var uri = 'flaggingon.php';

        Y.io(uri, {
            data: {activate: a, id: id},
            on: {
                start: function (evt) {
                    o.disabled = true;
                    Y.one(o).addClass('loading');
                },
                success: function (evt) {
                    Y.one(o).removeClass('loading');
                    o.disabled = false;
                },
                failure: function(evt) {
                    alert("Could not connect to Moodle");
                    Y.one(o).removeClass('loading');
                    o.checked = !o.checked;
                    o.disabled = false;
                }
            }
        })

    });

}