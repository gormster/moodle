<?php

namespace local_teameval;

abstract class evaluation_context {

	abstract public function evaluation_permitted($userid);

	abstract public function group_for_user($userid);

    abstract public function marking_users();

}