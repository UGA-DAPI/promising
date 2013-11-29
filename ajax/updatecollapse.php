<?php

    /**
    *
    * Ajax receptor for updating collapse status.
    * when Moodle enables ajax, will also, when expanding, return all the underlying div structure
    *
	*
	* @package mod-promising
	* @category mod
	* @author Yohan Thomas - W3C2i (support@w3c2i.com)
	* @date 30/09/2013
	* @version 3.0
	* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
	*
	*/


    include "../../../config.php";
    require_once $CFG->dirroot."/mod/promising/locallib.php";

    $id = required_param('id', PARAM_INT);   // module id
    $entity = required_param('entity', PARAM_ALPHA);   // module id
    $entryid = required_param('entryid', PARAM_INT);   // module id
    $state = required_param('state', PARAM_INT);   // module id

    // get some useful stuff...
    if (! $cm = get_coursemodule_from_id('promising', $id)) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }
    if (! $project = $DB->get_record('promising', array('id' => $cm->instance))) {
        print_error('invalidpromisingid', 'promising');
    }
    
    $group = 0 + groups_get_course_group($course, true);

    require_login($course->id, false, $cm);
    $context = context_module::instance($cm->id);
    if ($state){
        $collapse->userid = $USER->id;
        $collapse->projectid = $promising->id;
        $collapse->entryid = $entryid;
        $collapse->entity = $entity;
        $collapse->collapsed = 1;
        $DB->insert_record('promising_collapse', $collapse);

		// prepare for hidden branch / may not bne usefull
		/*
	    if ($CFG->enableajax && $CFG->enablecourseajax){
	    	$printfuncname = "promising_print_{$entity}s";
	    	$propagated->collapsed = true;
	    	$printfuncname($project, $group, $entryid, $cm->id, $propagated);
	    }
	    */

    } else {
        $DB->delete_records('promising_collapse', array('userid' => $USER->id, 'entryid' => $entryid, 'entity' => $entity));

		// prepare for showing branch
	    if ($CFG->enableajax && $CFG->enablecourseajax){
	    	$printfuncname = "promising_print_{$entity}";
	    	$printfuncname($project, $group, $entryid, $cm->id);
	    }

    }

?>