define(['jquery', 'core/templates', 'core/ajax', 'core/str', 'core/notification', 'core/fragment'], function($, Templates, Ajax, Strings, Notification, Fragment) {

    function LikertQuestion(container, teameval, contextid, self, editable, questionID, context) {
        this.container = container
        this.questionID = questionID;

        this._teameval = teameval;
        this._contextid = contextid;
        this._self = self;
        this._editable = editable;

        var context = context || {};
        this._submissioncontext = context.submissioncontext || {}; 
        this._editingcontext = context.editingcontext || {};

        this._meanings = {};
    };

    LikertQuestion.prototype.submissionView = function() {
        var promise = Templates.render('teamevalquestion_likert/submission_view', this._submissioncontext);
        promise.done(function(html, js) {
            Templates.replaceNodeContents(this.container, html, js);
        }.bind(this));
        return promise;
    };

    LikertQuestion.prototype.editingView = function() {
        this._editingcontext._id = this._teameval;

        var params = {
            'form': '\\teamevalquestion_likert\\forms\\settings_form',
            'jsonformdata': JSON.stringify($.param(this._editingcontext))
        };

        var promise = Fragment.loadFragment('local_teameval', 'ajaxform', this._contextid, params);

        promise.done(function(html, js) {
            Templates.replaceNodeContents(this.container, html, js);
            this.container.find('[name="range[min]"], [name="range[max]"]').change(this.updateMeanings.bind(this));
        }.bind(this));

        promise.fail(Notification.exception);

        return promise;
    };

    LikertQuestion.prototype.save = function(ordinal) {
        this.updateMeanings();

        var deferred = $.Deferred();

        this.container.find('[name=ordinal]').val(ordinal);

        if (this.questionID) {
            this.container.find('[name=id]').val(this.questionID);
        }

        var form = this.container.find('form');

        // validate data
        this.validateData(form).then(function() {

            var promises = Ajax.call([{
                methodname: 'teamevalquestion_likert_update_question',
                args: {'teamevalid': this._teameval, 'formdata': form.serialize()}
            }]);

            return promises[0];
                
        }.bind(this)).done(function(result) {
            
            this.questionID = result.id;
            this._submissioncontext = JSON.parse(result.submissionContext);
            deferred.resolve(result.id);

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

        return deferred.promise();
    };

    LikertQuestion.prototype.delete = function() {
        if (this.questionID) {
            var promises = Ajax.call([{
                methodname: 'teamevalquestion_likert_delete_question',
                args: {
                    teamevalid: this._teameval,
                    id: this.questionID
                }
            }]);

            return promises[0];
        }
        // No ID, never been saved
        return $.Deferred().resolve();
    };

    LikertQuestion.prototype.submit = function() {
        var marks = [];
        this.container.find('.responses tbody input[type="radio"]:checked').each(function(v, k) {
            var toUser = $(this).data('touser');
            var m = {};
            m.touser = toUser;
            m.value = this.value;
            marks.push(m);
        });

        var promises = ajax.call([{
            methodname: 'teamevalquestion_likert_submit_response',
            args: {
                teamevalid: this._teameval,
                id: this.questionID,
                marks: marks
            }
        }]);

        var incomplete = this.checkComplete();

        return promises[0].then(function() {
            return {'incomplete': incomplete};
        });
    };

    LikertQuestion.prototype.updateMeanings = function() {
        var minval = parseInt(this.container.find('[name="range[min]"]').val())
        var maxval = parseInt(this.container.find('[name="range[max]"]').val());

        console.log(minval, maxval);

        for (var i = 0; i <= 10; i++) {
            var meaning = this.container.find('[name="meanings['+i+']"]');
            this._meanings[i] = meaning.val();

            if (!this._editingcontext.locked) {
                if (this._meanings[i]) {
                    meaning.val(this._meanings[i]);
                }
                if ((i >= minval) && (i <= maxval)) {
                    meaning.closest('.fitem').addBack().removeClass('hidden');
                    console.log("showing "+i);
                } else {
                    meaning.closest('.fitem').addBack().addClass('hidden');
                    console.log("hiding "+i);
                }
            }
        }
    }

    LikertQuestion.prototype.validateData = function(form) {

        var deferred = $.Deferred();

        var data = function(v) { return $(form).find('[name="'+v+'"]').val(); };
        
        if ((data('title').trim().length == 0) && (data('description').trim().length == 0)) {
            Strings.get_string('titleordescription', 'teamevalquestion_likert').done(function(str) {
                deferred.reject({invalid: true, errors: { title: str, description: str} });
            });
        } else {
            deferred.resolve();
        }

        return deferred.promise();
    }

    LikertQuestion.prototype.checkComplete = function() {

        var userids = this.submissioncontext.users.map(function() { return this.userid; });

        var markedUsers = questionContainer.find('input:radio:checked').map(function() { return $(this).data('touser'); }).get();

        var missingUsers = userids.filter(function(v) { return markedUsers.indexOf(v) == -1; });

        if (missingUsers.length > 0) {
            questionContainer.parent().addClass('incomplete');
        } else {
            questionContainer.parent().removeClass('incomplete');
        }

        return (missingUsers.length > 0);
    }

    return LikertQuestion;

});