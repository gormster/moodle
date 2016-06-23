# Implementing Team Evaluation in your plugin

## Implement your evaluation_context subclass

You need to implement six methods from evaluation_context.

### evaluation_permitted

When called with null, return true if team evaluation makes sense in this context. For example, if your module is not set to group mode, you should return false.

When called with a userid, return true if that user should be shown the evaluation block. This might only make sense after they have submitted work, or otherwise completed some portion of your activity.

### user_group

Return the group that represents the user's team. Every user who can evaluate should belong to exactly one group. This should return a group object returned from Moodle's Groups API.

### all_groups

Every group that could potentially be returned by user_group. This should return an array of group objects returned from Moodle's Groups API, keyed by group ID.

### marking_users

Return a list of every user who has permission to mark in this activity. These are the users who can be passed to user_group and evaluation_permitted. Return a list of user objects from Moodle's User API.

### grade_for_group

The idea of team evaluation is to take a single group grade and adjust it according to each team member's contributions to the group. This function returns that initial group grade, from 0-100 as per Moodle standards.

### trigger_grade_update

Sometimes, changing team evaluation settings will change the final grades of your activity. In this case, we need you to go through your normal process to determine grades and send them to the gradebook. Remember that at some point during this process, you will need to call the adjust_grades method on your evaluation_context - that will return the grades adjusted according to teameval scores. You will almost always call this during YOURMODULE_update_grades.

There are optionally some more methods you could implement.

### plugin_namespace

When we need to scope things according to your plugin, we use this function. Normally it just returns the top-level namespace of the calling class, but if you're using teameval in a subplugin, or if your evaluation_context is defined in the global namespace, you should override this with a unique namespace for your plugin. (The namespace convention for moodle is plugintype_pluginname, like mod_assign or block_messages).

### component_string

Occasionally we might need to ask you for the human-readable name of your plugin. The default implementation gets the `modulenameplural` string if you're an activity module, or `pluginname` if you're any other kind of plugin.

### questionnaire_locked_hint

The questionnaire is locked to editing when evaluation_permitted returns true for at least one person in marking_users. We give the user a hint on how to unlock the questionnaire, if they need to. This function should return a localised string with that hint.

### format_feedback

Teammate feedback is appended to your plugin's feedback in the gradebook. If you want to customise how that looks, implement this function.

### format_grade

Grades aren't always out of 100. Use this function to format your grades according to your plugin's normal presentation. The default implementation simply rounds to the grade item's decimals value. You can return any value coercable to string.

## Implement _get_evaluation_context

More or less all this function needs to do is instantiate your evaluation context and return it. It should live in your lib.php and be named (like the other global plugin functions) YOURMODULE_get_evaluation_context, and take a single argument of a cm_info object.

You can cache this if your object is heavy to create.

## Calling update_grades

When your users have evaluated each other, you can submit adjusted grades to the gradebook. **Note that your plugin is still responsible for submitting grades to the gradebook** - there's no Moodle plugin that can intercept grades. Much as your evaluation_context is teameval's view in to your plugin, it's also your view into teameval. You should call update_grades with an array of gradebook grades keyed by user id. You usually want to call this in your plugin's YOURMODULE_update_grades function in lib.php. This function doesn't modify the grades in place - you have to take the returned array. If evaluation isn't permitted or enabled in your activity, update_grades will return the passed array directly.

Whenever you call into a teameval function, you should first check if the plugin is installed. You can do that with the following code:

    $teameval_plugin = core_plugin_manager::instance()->get_plugin_info('local_teameval');
    if ($teameval_plugin) {
    	// teameval is installed
    }

So your update code should look like

    $teameval_plugin = core_plugin_manager::instance()->get_plugin_info('local_teameval');
    if ($teameval_plugin) {
        $evaluationcontext = \local_teameval\evaluation_context::context_for_module($workshep->cm);
        $grades = $evaluationcontext->update_grades($grades);
    }

### Presenting the block

You've implemented the necessary code – now it's time to actually show your users the team evaluation UI.

Like before, we need to guard that the plugin is installed. Aside from that, it's just a case of fetching the renderer, creating the block, and rendering the result.

    $teameval_plugin = core_plugin_manager::instance()->get_plugin_info('local_teameval');
    if ($teameval_plugin) {
        $teameval_renderer = $PAGE->get_renderer('local_teameval');
        $teameval = \local_teameval\output\team_evaluation_block::from_cmid($cm->id);
        echo $teameval_renderer->render($teameval);
    }

That's it! Teameval handles all the UI from there.

## Implement reset methods

TODO