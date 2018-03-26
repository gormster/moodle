
define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {


    function performVote(voting, value, options, direction, otherDirection) {
        if(voting.hasClass(direction)) {
            // remove vote
            performVote(voting, 0, options, otherDirection, direction);
            return;
        }

        var isOtherWay = voting.hasClass(otherDirection);
        voting.removeClass(otherDirection);
        if (value) {
            voting.addClass(direction);
        }

        options.rating = value;
        var promises = Ajax.call([{
            methodname: 'core_rating_add_rating',
            args: options
        }]);

        promises[0]
        .then(function(result) {
            if (result.success) {
                voting.find('.agg').text(result.aggregate);
            } else {
                throw result.warnings;
            }
        })
        .fail(function(error) {
            voting.removeClass(direction);
            if (isOtherWay) {
                voting.addClass(otherDirection);
            }
            Notification.exception(error);
        });
    }

    return {
        initpost: function(id, options) {
            var voting = $('#'+id);
            voting.on('click', '.upvote', function(evt) {
                var voting = $(evt.delegateTarget);
                performVote(voting, 1, options, 'upvoted', 'downvoted');
            });
            voting.on('click', '.downvote', function(evt) {
                var voting = $(evt.delegateTarget);
                performVote(voting, -1, options, 'downvoted', 'upvoted');
            });
        }
    };

});