define(['jquery', 'core/ajax', 'core/templates', 'core/notification', 'core/str'], function($, Ajax, Templates, Notification, Strings){

    function CommentQuestion(container, teameval, contextid, self, editing, questionID, context) {
        this.container = container;
        this.questionID = questionID;

        this._teameval = teameval;
        this._self = self;
        this._editing = editing;

        var context = context || {};
        this._editingcontext = context.editingcontext || {_newquestion: true};
        this._submissioncontext = context.submissioncontext || {};
    }

    CommentQuestion.prototype.submissionView = function() {
        var promise = Templates.render('teamevalquestion_comment/submission_view', this._submissioncontext);
        promise.done(function(html, js) {
            Templates.replaceNodeContents(this.container, html, js);
        }.bind(this));
        return promise;
    };

    CommentQuestion.prototype.editingView = function() {
        var promise = Templates.render('teamevalquestion_comment/editing_view', this._editingcontext);
        promise.done(function(html, js) {
            Templates.replaceNodeContents(this.container, html, js);
        }.bind(this));
        return promise;
    };

    CommentQuestion.prototype.save = function(ordinal) {
        var deferred = $.Deferred();

        var data = { teamevalid: this._teameval };
        if (this.questionID) {
            data.id = this.questionID;
        }
        data.ordinal = ordinal;

        data.title = this.container.find('[name=title]').val();
        data.description = this.container.find('[name=description]').val();
        data.anonymous = this.container.find('[name=anonymous]').prop('checked');
        data.optional = this.container.find('[name=optional]').prop('checked');

        // validate that data
        validateData(data).then(function() {

            // remove all error states
            $('.control-group').removeClass('error');
            $('.help-inline').empty();

            var promises = Ajax.call([{
                methodname: 'teamevalquestion_comment_update_question',
                args: data
            }]);

            return promises[0];

        }).then(function(result) {

            data.id = result;

            return Strings.get_strings([
                {key: 'exampleuser', component: 'local_teameval'},
                {key: 'yourself', component: 'local_teameval'}
            ]);

        }.bind(this)).done(function(str) {

            var demoUsers = [{ userid: 0, name: str[0] }];
            if (this._self) {
                demoUsers.unshift({userid: -1, name: str[1], self: true});
            }

            this._submissioncontext = $.extend({}, data, {
                users: demoUsers
            });

            this._editingcontext = data;

            deferred.resolve(data.id);

        }.bind(this)).fail(function(error) {

            if (error.invalid) {
                for (var k in error.errors) {
                    this.container.find('[name='+k+']')
                    .closest('.control-group').addClass('error').end()
                    .next('.help-inline').text(error.errors[k]);
                }
            } else {
                Notification.exception(error);
            }

            deferred.reject();

        }.bind(this));

        return deferred;
    };

    function validateData(data) {

        var deferred = $.Deferred();
        
        if ((data.title.trim().length == 0) && (data.description.trim().length == 0)) {
            Strings.get_string('titleordescription', 'teamevalquestion_comment').done(function(str) {
                deferred.reject({invalid: true, errors: { title: str, description: str} });
            });
        } else {
            deferred.resolve();
        }

        return deferred.promise();
    }

    CommentQuestion.prototype.submit = function() {
        var comments = [];
        this.container.find('.comments textarea').each(function(v, k) {
            var toUser = $(this).data('touser');
            var m = {};
            m.touser = toUser;
            m.comment = $(this).val();
            comments.push(m);
        });

        var promises = Ajax.call([{
            methodname: 'teamevalquestion_comment_submit_response',
            args: {
                teamevalid: this._teameval,
                id: this.questionID,
                comments: comments
            }
        }]);

        var incomplete = false;
        if (this._submissioncontext.optional) {
            incomplete = checkComplete();
        }

        return promises[0].then(function() {
            return {'incomplete': incomplete};
        });
    }

    function checkComplete() {
        var incomplete = this.container.find('textarea').filter(function() {
            return $(this).val().trim().length == 0;
        });

        if (incomplete.length > 0) {
            questionContainer.parent().addClass('incomplete');
        } else {
            questionContainer.parent().removeClass('incomplete');
        }

        return incomplete.length > 0;
    }

    CommentQuestion.prototype.delete = function() {

        var promises = Ajax.call([{
            methodname: 'teamevalquestion_comment_delete_question',
            args: {
                teamevalid: this._teameval,
                id: this.questionID
            }
        }]);

        return promises[0];

    };

    return CommentQuestion;

});