<?php

namespace local_teameval\output;

use local_teameval;
use stdClass;
use user_picture;

class release implements \renderable, \templatable {

    protected $teameval;

    protected $releases;

    public function __construct($teameval, $releases) {
        $this->teameval = $teameval;
        $this->releases = $releases;
    }

    public function export_for_template(\renderer_base $output) {

        $evalcontext = $this->teameval->get_evaluation_context();

        $groups = $evalcontext->all_groups();

        $released_all = false;
        $released_groups = [];
        $released_users = [];

        foreach($this->releases as $release) {
            if ($release->level == local_teameval\RELEASE_ALL) {
                $released_all = true;
            } else if ($release->level == local_teameval\RELEASE_GROUP) {
                $released_groups[] = $release->target;
            } else if ($release->level == local_teameval\RELEASE_USER) {
                $released_users[] = $release->target;
            }
        }

        $c = new stdClass;
        $c->cmid = $this->teameval->get_coursemodule()->id;
        $c->all = $released_all;
        $c->groups = [];

        foreach($groups as $gid => $group) {
            $g = new stdClass;
            $g->gid = $gid;
            $g->grade = $evalcontext->grade_for_group($gid);
            $g->name = $group->name;
            $g->released = in_array($gid, $released_groups);
            $g->overridden = $released_all;
            

            $g->users = [];

            $users = groups_get_members($gid);
            foreach($users as $uid => $user) {
                $u = new stdClass;
                $u->id = $uid;
                $u->released = in_array($uid, $released_users);
                $u->name = fullname($user);
                $u->userpic = $output->render(new user_picture($user));
                $u->grade = $g->grade * $this->teameval->multiplier_for_user($uid);
                $u->overridden = ($g->overridden || $g->released);

                $g->users[] = $u;
            }

            $c->groups[] = $g;
        }

        return $c;

    }

}