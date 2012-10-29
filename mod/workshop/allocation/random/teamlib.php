<?php

//Included from lib.php

require_once(dirname(__FILE__) . '/teammode_settings_form.php');

class workshop_teammode_random_allocator extends workshop_random_allocator {
    
    protected $form_class = 'workshop_teammode_random_allocator_form';
    
    protected function get_options_from_settings($settings) {
        $options                     = array();
        $options['numofreviews']     = $settings->numofreviews;
        $options['numper']           = $settings->numper;
        $options['excludesamegroup'] = true;
        return $options;
    }
    
    protected function get_group_mode() {
        //teammode always spoofs visible groups mode
        return VISIBLEGROUPS;
    }
    
    protected function get_authors() {
        global $DB;
        $rslt = $this->workshop->get_submissions_grouped();
        //now we have to do some magic to turn these back into "authors"
        $ret = array();
        $users = array();
        
        //loop 1: get user ids
        foreach ($rslt as $r) {
            $users[] = $r->authorid;
        }
        $fields = user_picture::fields();
        $users = $DB->get_records_list('user','id',$users,'',$fields);
        //loop 2: apply users to submissions 
        $ret[0] = array();
        foreach ($rslt as $r){
            $ret[$r->group->id] = array( $r->authorid => $users[$r->authorid] );
            $ret[0][$r->authorid] = $users[$r->authorid];
        }

        return $ret;
    }
    
}
