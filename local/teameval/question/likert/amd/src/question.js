define(['jquery', 'core/templates', 'core/ajax', 'core/str', 'core/notification'], function($, Templates, Ajax, Strings, Notification) {

    function LikertQuestion(container, teameval, contextid, self, editable, questionID, context) {
        this.container = container
        this.questionID = questionID;

        this._teameval = teameval;
        this._self = self;
        this._editable = editable;

        var context = context || {};
        this._submissioncontext = context.submissioncontext || {}; 
        this._editingcontext = context.editingcontext || {_newquestion: true};

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
        this._editingcontext._self = this._self;
        var promise = Templates.render('teamevalquestion_likert/editing_view', this._editingcontext);
        promise.done(function(html, js) {
            Templates.replaceNodeContents(this.container, html, js);
        }.bind(this));
        return promise;
    };

    LikertQuestion.prototype.save = function(ordinal) {
        this.updateMeanings();

        var deferred = $.Deferred();
        var data = { teamevalid: this._teameval };
        if (this.questionID) {
            data.id = this.questionID
        }
        data.ordinal = ordinal;
        data.title = this.container.find('[name=title]').val();
        data.description = this.container.find('[name=description]').val();
        data.minval = parseInt(this.container.find('[name=minval]').val());
        data.maxval = parseInt(this.container.find('[name=maxval]').val());
        data.meanings = $.map(this._meanings, function(v, k) {
            if ((k < data.minval) || (k > data.maxval)) return;
            return {'value': k, 'meaning':v}; 
        });

        // validate data
        this.validateData(data).then(function() {

            var promises = Ajax.call([{
                methodname: 'teamevalquestion_likert_update_question',
                args: data
            }]);

            return promises[0];
                
        }).done(function(result) {
            
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
        var minval = parseInt(this.container.find('[name=minval]').val())
        var maxval = parseInt(this.container.find('[name=maxval]').val());

        var meaningsList = this.container.find('ol[name=meanings]');

        // fetch our current meanings
        var _this = this;
        meaningsList.children('li').each( function(idx) {
            _this._meanings[this.value] = $(this).find('input').val();
        });

        if (!this._editingcontext.locked) {
            // make new textboxes

            meaningsList.empty();

            for(var i = minval; i <= maxval; i++) {
                var li = $('<li />');
                var textbox = $('<input type="text">');
                if(this._meanings[i]) {
                    textbox.val(this._meanings[i]);
                }
                li.append(textbox);
                li.prop('value', i);
                meaningsList.append(li);
            }
        }
    }

    LikertQuestion.prototype.validateData = function(data) {

        var deferred = $.Deferred();
        
        if ((data.title.trim().length == 0) && (data.description.trim().length == 0)) {
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