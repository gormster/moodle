<?php

namespace local_teameval;

interface response {
    
    /**
     * @param team_evaluation $teameval the teamevaluation object this response belongs to
     * @param question $question the question object of the question this is a response to
     * @param int $userid the ID of the user responding to this question
     */
    public function __construct(team_evaluation $teameval, $question, $userid);

    /**
     * @return bool Has a response been given by this user?
     */
    public function marks_given();

    /**
     * What is this user's opinion of a particular teammate? Scaled from 0.0 to 1.0
     * @param type $userid Team mate's user ID
     * @return type
     */
    public function opinion_of($userid);

    /**
     * Human readable of above; for reports plugins
     * @param int $userid Teammates user ID
     * @param string $source The plugin that is asking for this opinion. Use to customise appearance.
     * @return renderable
     */
    public function opinion_of_readable($userid, $source = null);
    
}