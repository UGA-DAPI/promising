<?php

/**
*
* Moodle API Library
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

/**
* Requires and includes
*/
if (file_exists($CFG->libdir.'/openlib.php')){
    require_once($CFG->libdir.'/openlib.php');//openmod lib by rick chaides
}

/**
* Given an object containing all the necessary data,
* (defined by the form in mod.html) this function
* will create a new instance and return the id number
* of the new instance.
* @param object $project the form object from which create an instance 
* @return the new instance id
*/
function promising_add_instance($project){
	global $DB,$USER;
	
    $project->timecreated = time();
    $project->timemodified = time();
	//init des features utilisées
    $project->projectusesrequs = 0;
    $project->projectusesvalidations = 0;
    $project->projectusesspecs = 0;
    $project->projectusesdelivs = 1;
	
	if($project->typeprojet==0){//si c'eqst un type de projet on force confidentiel à non
		$project->projectconfidential=0;
	}
	$context = context_module::instance($project->coursemodule);
	if($project->instance!=''){
		$projetid= $project->instance;
	}else{
		$projetid=0;
	}
	/*var_dump($context);
	var_dump($project);die();*/
	
	/*$draftitemid = $project->introimg;
    if ($draftitemid) {
        file_save_draft_area_files($draftitemid, $context->id, 'mod_promising', 'introimg', 0, array('subdirs'=>true));
    }*/
	
    if ($returnid = $DB->insert_record('promising', $project)) {
        if($project->typeprojet>0){//on ajoute les événements que pour les projets pas les types
			$event = new StdClass;
			$event->name        = get_string('projectstartevent','promising', $project->name);
			$event->description = $project->intro;
			$event->courseid    = $project->course;
			$event->groupid     = 0;
			$event->userid      = 0;
			$event->modulename  = 'promising';
			$event->instance    = $returnid;
			$event->eventtype   = 'projectstart';
			$event->timestart   = $project->projectstart;
			$event->timeduration = 0;
			add_event($event);
			$event->name        = get_string('projectendevent','promising', $project->name);
			$event->eventtype   = 'projectend';
			$event->timestart   = $project->projectend;
			add_event($event);
		}		
    }	
	
	//gestion du champ introimg
	$introimgoptions = array('maxbytes' =>2000000, 'maxfiles'=> 1,'accepted_types' => array('.jpeg', '.jpg', '.png','return_types'=>FILE_INTERNAL));
	$project = file_postupdate_standard_filemanager($project, 'introimg', $introimgoptions, $context, 'mod_promising', 'introimg', $returnid);
	
	if($project->typeprojet>0){//Si c'est un projet on doit copier toutes les données du type de projet associé.
		promising_instance_from_typeprojet($project,$returnid);
	}
	
	//On ajoute automatiquement l'utilisateur au role local enseignant pour le projet !
	if($project->typeprojet>0){
		$role = $DB->get_record('role', array('shortname' => 'projectens'));
		$enrolement =  new StdClass;
		$enrolement->roleid=$role->id;
		$enrolement->contextid=$context->id;
		$enrolement->userid=$USER->id;
		$enrolement->timemodified= time();
		$enrolement->modifierid=$USER->id;
		$idroletmp = $DB->insert_record('role_assignments', $enrolement);
	}
	
    return $returnid;
}
/**
* Créé une copie des données du type de projet choisi dans l'objet $project vers le nouveau projet
* Params : les données du projet et l'id du projet qui recoit la copie
*/
function promising_instance_from_typeprojet($project,$returnid){
	global $DB,$CFG,$USER;
	//copie info introimg projet
	
	$projetFrom = $DB->get_record('promising', array('id' => $project->typeprojet));//projet à copier
	$moduleid = $DB->get_record('modules', array('name' => 'promising'));
	$coursemoduleid = $DB->get_record('course_modules', array('module' => $moduleid->id,'instance' => $projetFrom->id));//projet à copier
	$contextFrom = context_module::instance($coursemoduleid->id);//contexte du type projet a copier
	
	$context = context_module::instance($project->coursemodule);//contexte du projet en cours de création
	$project->id=$returnid;
	$project->projectgrpid = $projetFrom->projectgrpid;
	if(!$project->introimg){//si le projet na pas d'image mais que le type copié en a une on la récupère
		if((int)$projetFrom->introimg ==1){
			$fs = get_file_storage();
			//on récupère l'intro img du type projet
			$files = $fs->get_area_files($contextFrom->id, 'mod_promising', 'introimg', $projetFrom->id, 'sortorder DESC, id ASC', false);
			if(!empty($files)){
				$file = reset($files);
				$file_record = array('contextid'=>$context->id, 'component'=>'mod_promising', 'filearea'=>'introimg',
						 'itemid'=>$returnid, 'filepath'=>'/', 'filename'=>$file->get_filename(),
						 'timecreated'=>time(), 'timemodified'=>time());
				$fs->create_file_from_string($file_record, $file->get_content());
				//on update le projet avec l'introimg en +
				$project->introimg=1;
				$project->id = $returnid;
			}
		}
	}
	$returnidtmp = $DB->update_record('promising', $project);
	//copie des étapes
	$typemilestones = $DB->get_records('promising_milestone', array('projectid' => $project->typeprojet));//étapes
	foreach($typemilestones as $milestonetocopy){
		$milestonetocopy->projectid = $returnid;//on set l'id du projet pour l'étape
		$milestonetocopy->userid = $USER->id;
		$milestonetocopy->created = time();
		$milestonetocopy->modified = time();
		$milestonetocopy->statut = 0;
		$milestonetocopy->numversion = 0;
		if ($milestoneid = $DB->insert_record('promising_milestone', $milestonetocopy)){
			//Une fois l'étape crée on s'occupe des ressources/livrables associés
			$deliverables = $DB->get_records('promising_deliverable', array('milestoneid'=>$milestonetocopy->id,'projectid' => $project->typeprojet));//étapes
			foreach($deliverables as $deliverabletocopy){
				$deliverabletocopy->userid = $USER->id;
				$deliverabletocopy->modified = time();
				$deliverabletocopy->created = time();
				$deliverabletocopy->lastuserid = $USER->id;
				$deliverabletocopy->projectid = $returnid;
				$deliverabletocopy->milestoneid = $milestoneid;
				if ($deliverableid = $DB->insert_record('promising_deliverable', $deliverabletocopy)){
					if($deliverabletocopy->typeelm==0 && $deliverabletocopy->localfile==1){//si un fichier est déposé pour une ressource on la copie aussi
						$fs = get_file_storage();
						$files = $fs->get_area_files($contextFrom->id, 'mod_promising', 'deliverablelocalfile', $deliverabletocopy->id, 'sortorder DESC, id ASC', false);
						if(!empty($files)){
							$file = reset($files);
							$file_record = array('contextid'=>$context->id, 'component'=>'mod_promising', 'filearea'=>'deliverablelocalfile',
									 'itemid'=>$deliverableid, 'filepath'=>'/', 'filename'=>$file->get_filename(),
									 'timecreated'=>time(), 'timemodified'=>time());
							$fs->create_file_from_string($file_record, $file->get_content());
							//on update la ressource avec le fichier en +
							$deliverabletocopy->localfile=1;
							$deliverabletocopy->id = $deliverableid;
							$returnidtmp = $DB->update_record('promising_deliverable', $deliverabletocopy);
						}
					}
				}
			}
		}
	}
}
/**
* some consistency check over dates
* returns true if the dates are valid, false otherwise
* @param object $project a form object to be checked for dates
* @return true if dates are OK
*/
function promising_check_dates($project) {
    // but enforce non-empty or non negative projet period.
    return ($project->projectstart < $project->projectend);           
}

/**
* Given an object containing all the necessary data, 
* (defined by the form in mod.html) this function 
* will update an existing instance with new data.
* @uses $CFG
* @param object $project the form object from which update an instance
*/
function promising_update_instance($project){
    global $CFG, $DB;
    $project->timemodified = time();

    if (!promising_check_dates($project)) {
        return get_string('invaliddates', 'promising');
    }

    $project->id = $project->instance;
	//$draftitemid = $project->introimg;
	/*
    if ($draftitemid) {
		$context = context_module::instance($project->coursemodule);
        file_save_draft_area_files($draftitemid, $context->id, 'mod_promising', 'introimg', 0, array('subdirs'=>true));
    }*/
	$context = context_module::instance($project->coursemodule);
	$introimgoptions = array('maxbytes' =>2000000, 'maxfiles'=> 1,'accepted_types' => array('.jpeg', '.jpg', '.png','return_types'=>FILE_INTERNAL));
	$project = file_postupdate_standard_filemanager($project, 'introimg', $introimgoptions, $context, 'mod_promising', 'introimg', $project->id);
	
    if ($returnid = $DB->update_record('promising', $project)) {

        $dates = array(
            'projectstart' => $project->projectstart,
            'projectend' => $project->projectend,
            'assessmentstart' => $project->assessmentstart
        );
        $moduleid = $DB->get_field('modules', 'id', array('name' => 'promising'));
        foreach ($dates as $type => $date) {
            if ($event = $DB->get_record('event', array('modulename' => 'promising', 'instance' => $project->id, 'eventtype' => $type))) {
                $event->name        = get_string($type.'event','promising', $project->name);
                $event->description = $project->intro;
                $event->eventtype   = $type;
                $event->timestart   = $date;
                update_event($event);
            } 
            else if ($date) {
                $event = new StdClass;
                $event->name        = get_string($type.'event','promising', $project->name);
                $event->description = $project->intro;
                $event->courseid    = $project->course;
                $event->groupid     = 0;
                $event->userid      = 0;
                $event->modulename  = 'promising';
                $event->instance    = $project->instance;
                $event->eventtype   = $type;
                $event->timestart   = $date;
                $event->timeduration = 0;
                $event->visible     = $DB->get_field('course_modules', 'visible', array('module' => $moduleid, 'instance' => $project->id)); 
                add_event($event);
            }
        }
    }
    return $returnid;
}

/**
* Given an ID of an instance of this module,
* this function will permanently delete the instance
* and any data that depends on it.
* @param integer $id the instance id to delete
* @return true if successfully deleted
*/
function promising_delete_instance($id){
	global $DB;
	
    if (! $project = $DB->get_record('promising', array('id' => $id))) {
        return false;
    }

    $result = true;

    /* Delete any dependent records here */

    /* Delete subrecords here */
    $DB->delete_records('promising_heading', array('projectid' => $project->id));
    $DB->delete_records('promising_task', array('projectid' => $project->id));
    $DB->delete_records('promising_specification', array('projectid' => $project->id));
    $DB->delete_records('promising_requirement', array('projectid' => $project->id));
    $DB->delete_records('promising_milestone', array('projectid' => $project->id));
    $DB->delete_records('promising_deliverable', array('projectid' => $project->id));

    // echo "delete entities ok!!<br/>";

    $DB->delete_records('promising_task_to_spec', array('projectid' => $project->id));
    $DB->delete_records('promising_task_dependency', array('projectid' => $project->id));
    $DB->delete_records('promising_task_to_deliv', array('projectid' => $project->id));
    $DB->delete_records('promising_spec_to_req', array('projectid' => $project->id));

    // delete domain subrecords
    $DB->delete_records('promising_qualifier', array('projectid' => $project->id));
    $DB->delete_records('promising_assessment', array('projectid' => $project->id));
    $DB->delete_records('promising_criterion', array('projectid' => $project->id));

	/* Delete any event associate with the project */
    $DB->delete_records('event', array('modulename' => 'promising', 'instance' => $project->id));
	/* Delete the instance itself */
    if (! $DB->delete_records('promising', array('id' => $project->id))) {
        $result = false;
    }

    echo "full delete : $result<br/>";
    // return $result;
    return true;
}

/**
* gives back an object for student detailed reports
* @param object $course the current course
* @param object $user the current user
* @param object $mod the current course module
* @param object $project the current project
*/
function promising_user_complete($course, $user, $mod, $project){
    return NULL;
}

/**
* gives back an object for student abstract reports
* @uses $CFG
* @param object $course the current course
* @param object $user the current user
* @param object $mod the current course module
* @param object $project the current project
*/
function promising_user_outline($course, $user, $mod, $project){
    global $CFG, $DB;

    if ($project = $DB->get_record('promising', array('id' => $project->id))){
        // counting assigned tasks
        $assignedtasks = $DB->count_records('promising_task', array('projectid' => $project->id, 'assignee' => $user->id));
        $select = "projectid = {$project->id} AND assignee = $user->id AND done < 100";
        $uncompletedtasks = $DB->count_records_select('promising_task', $select);
        $ownedtasks = $DB->count_records('promising_task', array('projectid' => $project->id, 'owner' => $user->id));
        $outline = new stdClass();
        $outline->info = get_string('haveownedtasks', 'promising', $ownedtasks);
        $outline->info .= '<br/>'.get_string('haveassignedtasks', 'promising', $assignedtasks);
        $outline->info .= '<br/>'.get_string('haveuncompletedtasks', 'promising', $uncompletedtasks);

        $sql = "
            SELECT MAX(modified) as modified FROM 
               {promising_task}
            WHERE
                projectid = $project->id AND 
                (owner = $user->id OR
                assignee = $user->id)
        ";
        if ($lastrecord = $DB->get_record_sql($sql))
            $outline->time = $lastrecord->modified;
        else
            $outline->time = $project->timemodified;
        return $outline;
    }
    return NULL;
}

/**
 * Course resetting API
 * Called by course/reset.php
 * OLD OBSOLOETE WAY
 */
function promising_reset_course_form($course) {
    echo get_string('resetproject', 'promising'); 
    echo ':<br />';
    print_checkbox('reset_promising_groups', 1, true, get_string('grouped','promising'), '', '');  
    echo '<br />';
    print_checkbox('reset_promising_group0', 1, true, get_string('groupless','promising'), '', '');  
    echo '<br />';
    print_checkbox('reset_promising_grades', 1, true, get_string('grades','promising'), '', '');  
    echo '<br />';
    print_checkbox('reset_promising_criteria', 1, true, get_string('criteria','promising'), '', '');  
    echo '<br />';
    print_checkbox('reset_promising_milestones', 1, true, get_string('milestones','promising'), '', '');  
    echo '<br />';
    echo '</p>';
}

/**
 * Called by course/reset.php
 * @param $mform form passed by reference
 */
function promising_reset_course_form_definition(&$mform) {
    global $COURSE, $DB;

    $mform->addElement('header', 'teachprojectheader', get_string('modulenameplural', 'promising'));
    if(!$promisings = $DB->get_records('promising', array('course' => $COURSE->id))){
        return;
    }

    $mform->addElement('static', 'hint', get_string('resetproject','promising'));
    $mform->addElement('checkbox', 'reset_promising_grades', get_string('resetting_grades', 'promising'));
    $mform->addElement('checkbox', 'reset_promising_criteria', get_string('resetting_criteria', 'promising'));
    $mform->addElement('checkbox', 'reset_promising_groups', get_string('resetting_groupprojects', 'promising'));
    $mform->addElement('checkbox', 'reset_promising_group0', get_string('resetting_courseproject', 'promising'));
}

/**
* This function is used by the remove_course_userdata function in moodlelib.
* If this function exists, remove_course_userdata will execute it.
* This function will remove all posts from the specified forum.
* @uses $CFG
* @param object $data the reset options
* @param boolean $showfeedback if true, ask the function to be verbose
*/
function promising_reset_userdata($data) {
    global $CFG, $DB;

    $status = array();
    $componentstr = get_string('modulenameplural', 'magtest');
    $strreset = get_string('reset');
    if ($data->reset_promising_grades or $data->reset_promising_criteria or $data->reset_promising_groups){
        $sql = "
            DELETE FROM
                {promising_assessment}
                WHERE
                    projectid IN ( SELECT 
                c.id 
             FROM 
                {promising} AS c
             WHERE 
                c.course={$data->courseid} )
         ";
        if ($DB->execute($sql)){
            $status[] = array('component' => $componentstr, 'item' => get_string('resetting_grades','promising'), 'error' => false);
        }
    }

    if ($data->reset_promising_criteria){
        $sql = "
            DELETE FROM
                {promising_criterion}
                WHERE
                    projectid IN ( SELECT 
                c.id 
             FROM 
                {promising} AS c
             WHERE 
                c.course={$data->courseid} )
         ";
        if($DB->execute($sql)){
            $status[] = array('component' => $componentstr, 'item' => get_string('resetting_criteria','promising'), 'error' => false);
        }
    }

    if ($data->reset_promising_groups){
        $subsql = "
                WHERE
                    projectid IN ( SELECT 
                c.id 
             FROM 
                {promising} AS c
             WHERE 
                c.course={$data->courseid} ) AND
                groupid != 0
         ";

        $deletetables = array('spec_to_req', 
                              'task_to_spec', 
                              'task_to_deliv', 
                              'task_dependency', 
                              'requirement', 
                              'specification', 
                              'task', 
                              'deliverable',
                              'heading');

        if ($data->reset_promising_milestones){
            $deletetables[] = 'milestone';
        }
        foreach($deletetables as $atable){
            $sql = "
                DELETE FROM
                    {promising_{$atable}}
                    {$subsql}
            ";
            $DB->execute($sql);
        }        

        $status[] = array('component' => $componentstr, 'item' => get_string('resetting_groupprojects','promising'), 'error' => false);
    }

    if ($data->reset_promising_group0){
        $subsql = "
                WHERE
                    projectid IN ( SELECT 
                c.id 
             FROM 
                {promising} AS c
             WHERE 
                c.course={$data->courseid} ) AND
                groupid = 0
         ";

        $deletetables = array('spec_to_req', 
                              'task_to_spec', 
                              'task_to_deliv', 
                              'task_dependency', 
                              'requirement', 
                              'specification', 
                              'task', 
                              'deliverable',
                              'heading');

        if ($data->reset_promising_milestones){
            $deletetables[] = 'milestone';
        }
        foreach($deletetables as $atable){
            $sql = "
                DELETE FROM
                    {promising_{$atable}}
                    {$subsql}
            ";
            $DB->execute($sql);
        }
        $status[] = array('component' => $componentstr, 'item' => get_string('resetting_courseproject','promising'), 'error' => false);
    }
    return $status;
}


/**
* performs what needs to be done in asynchronous mode
*/
function promising_cron(){
    // TODO : may cleanup some old group rubish ??

}

/**
*
*/


/**
* get the "grade" entries for this user and add the first and last names (of project owner, 
* better to get name of teacher...
* ...but not available in assessment record...)
* @param object $course the current course
* @param int $timestart the time from which to log
*/
function promising_get_grade_logs($course, $timestart) {
    global $CFG, $USER, $DB;

    if (empty($USER->id)) {
        return false;
    }
    // TODO evaluate grading and assessment strategies
    return;
    $timethen = time() - $CFG->maxeditingtime;
    $query = "
        SELECT 
            l.time, 
            l.url, 
            u.firstname, 
            u.lastname, 
            a.projectid, 
            e.name
        FROM 
            {log} l,
            {promising} e, 
            {promising_assessments} a, 
            {user} u
        WHERE
            l.time > $timestart AND 
            l.time < $timethen AND 
            l.course = $course->id AND 
            l.module = 'promising' AND 
            l.action = 'grade' AND 
            a.id = l.info AND 
            e.id = a.projectid AND 
            a.userid = $USER->id AND 
            u.id = e.userid AND 
            e.id = a.projectid
    ";
    return $DB->get_records_sql($query);
}

/*
* get the log entries by a particular change in entities, 
* @uses $CFG
* @param object $course the current course
* @param int $timestart the time from which to log
* @param string $changekey the key of the event type to be considered
*/
function promising_get_entitychange_logs($course, $timestart, $changekey) {
    global $CFG, $DB;

    $timethen = time() - $CFG->maxeditingtime;
    $query = "
        SELECT 
            l.time, 
            l.url, 
            u.firstname, 
            u.lastname, 
            l.info as projectid, 
            p.name
        FROM 
            {log} l,
            {promising} p, 
            {user} u
        WHERE 
            l.time > $timestart AND 
            l.time < $timethen AND 
            l.course = $course->id AND 
            l.module = 'promising' AND 
            l.action = '$changekey' AND 
            p.id = l.info AND 
            u.id = l.userid
    ";
    return $DB->get_records_sql($query);
}

/**
* get the "submit" entries and add the first and last names...
* @uses $CFG
* @param object $course
* @param int $timestart
*/
function promising_get_submit_logs($course, $timestart) {
    global $CFG, $DB;

    $timethen = time() - $CFG->maxeditingtime;
    $query = "
        SELECT 
            l.time, 
            l.url, 
            u.firstname, 
            u.lastname, 
            l.info as projectid, 
            e.name
        FROM 
            {log} l,
            {promising} e, 
            {user} u
        WHERE 
            l.time > $timestart AND 
            l.time < $timethen AND 
            l.course = $course->id AND 
            l.module = 'promising' AND 
            l.action = 'submit' AND 
            e.id = l.info AND 
            u.id = l.userid
    ";
    return $DB->get_records_sql($query);
}

/**
* Given a list of logs, assumed to be those since the last login
* this function prints a short list of changes related to this module
* If isteacher is true then perhaps additional information is printed.
* This function is called from course/lib.php: print_recent_activity()
* @uses $CFG
* @param object $course
* @param boolean $isteacher
* @param int $timestart
*/
function promising_print_recent_activity($course, $isteacher, $timestart){
    global $CFG;

    // have a look for what has changed in requ
    $changerequcontent = false;
    if (!$isteacher) { // teachers only need to see project
        if ($logs = promising_get_entitychange_logs($course, $timestart, 'changerequ')) {
            // got some, see if any belong to a visible module
            foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid)
                $tempmod = new StdClass;
                $tempmod->course = $course->id;
                $tempmod->id = $log->projectid;
                //Obtain the visible property from the instance
                if (instance_is_visible('promising',$tempmod)) {
                    $changerequcontent = true;
                    break;
                    }
                }
            // if we got some "live" ones then output them
            if ($changerequcontent) {
                print_headline(get_string('projectchangedrequ', 'promising').":");
                foreach ($logs as $log) {
                    //Create a temp valid module structure (only need courseid, moduleid)
                	$tempmod = new StdClass;
                    $tempmod->course = $course->id;
                    $tempmod->id = $log->projectid;
                    //Obtain the visible property from the instance
                    if (instance_is_visible('promising',$tempmod)) {
                        if (!has_capability('mod/promising:gradeproject', $context, $log->userid)) {  // don't break anonymous rule
                            $log->firstname = $course->student;
                            $log->lastname = '';
                        }
                        print_recent_activity_note($log->time, $log, $isteacher, $log->name,
                                                   $CFG->wwwroot.'/mod/promising/'.$log->url);
                    }
                }
            }
        }
    }

    // have a look for what has changed in specs
    $changespeccontent = false;
    if (!$isteacher) { // teachers only need to see project
        if ($logs = promising_get_entitychange_logs($course, $timestart, 'changespec')) {
            // got some, see if any belong to a visible module
            foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid)
                $tempmod->course = $course->id;
                $tempmod->id = $log->projectid;
                //Obtain the visible property from the instance
                if (instance_is_visible('promising',$tempmod)) {
                    $changespeccontent = true;
                    break;
                    }
                }
            // if we got some "live" ones then output them
            if ($changespeccontent) {
                print_headline(get_string('projectchangedspec', 'promising').":");
                foreach ($logs as $log) {
                    //Create a temp valid module structure (only need courseid, moduleid)
                    $tempmod->course = $course->id;
                    $tempmod->id = $log->projectid;
                    //Obtain the visible property from the instance
                    if (instance_is_visible('promising',$tempmod)) {
                        if (!isteacher($course->id, $log->userid)) {  // don't break anonymous rule
                            $log->firstname = $course->student;
                            $log->lastname = '';
                        }
                        print_recent_activity_note($log->time, $log, $isteacher, $log->name,
                                                   $CFG->wwwroot.'/mod/promising/'.$log->url);
                    }
                }
            }
        }
    }

    // have a look for what has changed in tasks
    $changetaskcontent = false;
    if (!$isteacher) { // teachers only need to see project
        if ($logs = promising_get_entitychange_logs($course, $timestart, 'changetask')) {
            // got some, see if any belong to a visible module
            foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid)
                $tempmod->course = $course->id;
                $tempmod->id = $log->projectid;
                //Obtain the visible property from the instance
                if (instance_is_visible('promising',$tempmod)) {
                    $changetaskcontent = true;
                    break;
                    }
                }
            // if we got some "live" ones then output them
            if ($changetaskcontent) {
                print_headline(get_string('projectchangedtask', 'promising').":");
                foreach ($logs as $log) {
                    //Create a temp valid module structure (only need courseid, moduleid)
                    $tempmod->course = $course->id;
                    $tempmod->id = $log->projectid;
                    //Obtain the visible property from the instance
                    if (instance_is_visible('promising',$tempmod)) {
                        if (!isteacher($course->id, $log->userid)) {  // don't break anonymous rule
                            $log->firstname = $course->student;
                            $log->lastname = '';
                        }
                        print_recent_activity_note($log->time, $log, $isteacher, $log->name,
                                                   $CFG->wwwroot.'/mod/promising/'.$log->url);
                    }
                }
            }
        }
    }

    // have a look for what has changed in milestones
    $changemilescontent = false;
    if (!$isteacher) { // teachers only need to see project
        if ($logs = promising_get_entitychange_logs($course, $timestart, 'changemilestone')) {
            // got some, see if any belong to a visible module
            foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid)
                $tempmod->course = $course->id;
                $tempmod->id = $log->projectid;
                //Obtain the visible property from the instance
                if (instance_is_visible('promising',$tempmod)) {
                    $changemilescontent = true;
                    break;
                    }
                }
            // if we got some "live" ones then output them
            if ($changemilescontent) {
                print_headline(get_string('projectchangedmilestone', 'promising').":");
                foreach ($logs as $log) {
                    //Create a temp valid module structure (only need courseid, moduleid)
                    $tempmod->course = $course->id;
                    $tempmod->id = $log->projectid;
                    //Obtain the visible property from the instance
                    if (instance_is_visible('promising',$tempmod)) {
                        if (!isteacher($course->id, $log->userid)) {  // don't break anonymous rule
                            $log->firstname = $course->student;
                            $log->lastname = '';
                        }
                        print_recent_activity_note($log->time, $log, $isteacher, $log->name,
                                                   $CFG->wwwroot.'/mod/promising/'.$log->url);
                    }
                }
            }
        }
    }

    // have a look for what has changed in milestones
    $changedelivcontent = false;
    if (!$isteacher) { // teachers only need to see project
        if ($logs = promising_get_entitychange_logs($course, $timestart, 'changedeliverable')) {
            // got some, see if any belong to a visible module
            foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid)
                $tempmod->course = $course->id;
                $tempmod->id = $log->projectid;
                //Obtain the visible property from the instance
                if (instance_is_visible('promising',$tempmod)) {
                    $changedelivcontent = true;
                    break;
                    }
                }
            // if we got some "live" ones then output them
            if ($changedelivcontent) {
                print_headline(get_string('projectchangeddeliverable', 'promising').":");
                foreach ($logs as $log) {
                    //Create a temp valid module structure (only need courseid, moduleid)
                    $tempmod->course = $course->id;
                    $tempmod->id = $log->projectid;
                    //Obtain the visible property from the instance
                    if (instance_is_visible('promising',$tempmod)) {
                        if (!isteacher($course->id, $log->userid)) {  // don't break anonymous rule
                            $log->firstname = $course->student;
                            $log->lastname = '';
                        }
                        print_recent_activity_note($log->time, $log, $isteacher, $log->name,
                                                   $CFG->wwwroot.'/mod/promising/'.$log->url);
                    }
                }
            }
        }
    }

    // have a look for new gradings for this user (grade)
    $gradecontent = false;
    if ($logs = promising_get_grade_logs($course, $timestart)) {
        // got some, see if any belong to a visible module
        foreach ($logs as $log) {
            // Create a temp valid module structure (only need courseid, moduleid)
            $tempmod->course = $course->id;
            $tempmod->id = $log->projectid;
            //Obtain the visible property from the instance
            if (instance_is_visible('promising',$tempmod)) {
                $gradecontent = true;
                break;
                }
            }
        // if we got some "live" ones then output them
        if ($gradecontent) {
            print_headline(get_string('projectfeedback', 'promising').":");
            foreach ($logs as $log) {
                //Create a temp valid module structure (only need courseid, moduleid)
                $tempmod->course = $course->id;
                $tempmod->id = $log->projectid;
                //Obtain the visible property from the instance
                if (instance_is_visible('promising',$tempmod)) {
                    $log->firstname = $course->teacher;    // Keep anonymous
                    $log->lastname = '';
                    print_recent_activity_note($log->time, $log, $isteacher, $log->name,
                                               $CFG->wwwroot.'/mod/promising/'.$log->url);
                }
            }
        }
    }

    // have a look for new project (only show to teachers) (submit)
    $submitcontent = false;
    if ($isteacher) {
        if ($logs = promising_get_submit_logs($course, $timestart)) {
            // got some, see if any belong to a visible module
            foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid)
                $tempmod->course = $course->id;
                $tempmod->id = $log->projectid;
                //Obtain the visible property from the instance
                if (instance_is_visible('promising',$tempmod)) {
                    $submitcontent = true;
                    break;
                    }
                }
            // if we got some "live" ones then output them
            if ($submitcontent) {
                print_headline(get_string('projectproject', 'promising').":");
                foreach ($logs as $log) {
                    //Create a temp valid module structure (only need courseid, moduleid)
                    $tempmod->course = $course->id;
                    $tempmod->id = $log->projectid;
                    //Obtain the visible property from the instance
                    if (instance_is_visible('promising',$tempmod)) {
                        print_recent_activity_note($log->time, $log, $isteacher, $log->name,
                                                   $CFG->wwwroot.'/mod/promising/'.$log->url);
                    }
                }
            }
        }
    }
    return $changerequcontent or $changespeccontent or $changetaskcontent or $changemilescontent or $changedelivcontent or $gradecontent or $submitcontent;
}

/**
 * Must return an array of grades for a given instance of this module, 
 * indexed by user. It also returns a maximum allowed grade.
 * 
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $newmoduleid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 **/
function promising_grades($cmid) {
    global $CFG, $DB;

    if (!$module = $DB->get_record('course_modules', array('id' => $cmid))){
        return NULL;
    }    

    if (!$project = $DB->get_record('promising', array('id' => $module->instance))){
        return NULL;
    }

    if ($project->grade == 0) { // No grading
        return NULL;
    }

    $query = "
       SELECT
          a.*,
          c.weight
       FROM
          {promising_assessment} as a
       LEFT JOIN
          {promising_criterion} as c
       ON
          a.criterion = c.id
       WHERE
          a.projectid = {$project->id}
    ";
    // echo $query ;
    $grades = $DB->get_records_sql($query);
    if ($grades){
        if ($project->grade > 0 ){ // Grading numerically
            $finalgrades = array();
            foreach($grades as $aGrade){
                $finalgrades[$aGrade->userid] = @$finalgrades[$aGrade->userid] + $aGrade->grade * $aGrade->weight;
                $totalweights[$aGrade->userid] = @$totalweights[$aGrade->userid] + $aGrade->weight;
            }
            foreach(array_keys($finalgrades) as $aUserId){
                if($totalweights[$aGrade->userid] != 0){
                    $final[$aUserId] = round($finalgrades[$aUserId] / $totalweights[$aGrade->userid]);
                }
                else{
                    $final[$aUserId] = 0;
                }
            }
            $return->grades = @$final;
            $return->maxgrade = $project->grade;
        } else { // Scales
            $finalgrades = array();
            $scaleid = - ($project->grade);
            $maxgrade = '';
            if ($scale = $DB->get_record('scale', array('id' => $scaleid))) {
                $scalegrades = make_menu_from_list($scale->scale);
                foreach ($grades as $aGrade) {
                    $finalgrades[$userid] = @$finalgrades[$userid] + $scalegrades[$aGgrade->grade] * $aGrade->weight;
                    $totalweights[$aGrade->userid] = @$totalweights[$aGrade->userid] + $aGrade->weight;
                }
                $maxgrade = $scale->name;

                foreach(array_keys($finalgrades) as $aUserId){
                    if($totalweights[$aGrade->userid] != 0){
                        $final[$userId] = round($finalgrades[$aUserId] / $totalweights[$aGrade->userid]);
                    } else {
                        $final[$userId] = 0;
                    }
                }
            }
            $return->grades = @$final;
            $return->maxgrade = $maxgrade;
        }
        return $return;
    }
    return NULL;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of newmodule. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $moduleid ID of an instance of this module
 * @return mixed boolean/array of students
 **/
function promising_get_participants($moduleid) {
	global $DB;

    $usersreqs = $DB->get_records('promising_requirement', array('projectid' => $moduleid), '', 'userid,userid');
    $usersspecs = $DB->get_records('promising_specification', array('projectid' => $moduleid), '', 'userid,userid');
    $userstasks = $DB->get_records('promising_task', array('projectid' => $moduleid), '', 'userid,userid');
    $userstasksassigned = $DB->get_records('promising_task', array('projectid' => $moduleid), '', 'assignee,assignee');
    $userstasksowners = $DB->get_records('promising_task', array('projectid' => $moduleid), '', 'owner,owner');
    $usersdelivs = $DB->get_records('promising_deliverable', array('projectid' => $moduleid), '', 'userid,userid');
    $usersmiles = $DB->get_records('promising_milestone', array('projectid' => $moduleid), '', 'userid,userid');

    $allusers = array();    
    if(!empty($usersreqs)){
        $allusers = array_keys($usersreqs);
    }
    if(!empty($usersspecs)){
        $allusers = array_merge($allusers, array_keys($usersspecs));
    }
    if(!empty($userstasks)){
        $allusers = array_merge($allusers, array_keys($userstasks));
    }
    if(!empty($userstasksassigned)){
        $allusers = array_merge($allusers, array_keys($userstasksassigned));
    }
    if(!empty($userstasksowned)){
        $allusers = array_merge($allusers, array_keys($userstasksowned));
    }
    if(!empty($userstasksdelivs)){
        $allusers = array_merge($allusers, array_keys($userstasksdelivs));
    }
    if(!empty($userstasksmiles)){
        $allusers = array_merge($allusers, array_keys($userstasksmiles));
    }
    $userlist = implode("','", $allusers);
    $participants = $DB->get_records_list('user', array('id' => "'$userlist'"));
    return $participants;
}

/**
 * This function returns if a scale is being used by one newmodule
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $newmoduleid ID of an instance of this module
 * @return mixed
 **/
function promising_scale_used($cmid, $scaleid) {
	global $DB;

    $return = false;

    // note : scales are assigned using negative index in the grade field of project (see mod/assignement/lib.php) 
    $rec = $DB->get_record('promising', array('id' => $cmid, 'grade' => -$scaleid));

    if (!empty($rec) && !empty($scaleid)) {
        $return = true;
    }
    return $return;
}

/**
 * Serves the promising attachments. Implements needed access control ;-)
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - justsend the file
 */
function promising_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB, $USER;
	
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    $fileareas = array('deliverablearchive','deliverablelocalfile','introimg','requirementdescription', 'specificationdescription', 'milestonedescription', 'taskdescription', 'deliverabledescription', 'abstract', 'rationale', 'environment');
    $areastotables = array('deliverablearchive'=>'promising_milestone','deliverablelocalfile'=>'promising_deliverable','introimg'=>'promising','requirementdescription' => 'promising_requirement', 'specificationdescription' => 'promising_specifciation', 'milestonedescription' => 'promising_milestone', 'taskdescription' => 'promising_task', 'deliverabledescription' => 'promising_deliverable', 'abstract' => 'promising_heading', 'rationale' => 'promising_heading', 'environment' => 'promising_heading');
    if (!in_array($filearea, $fileareas)) {
        return false;
    }
    
    $relatedtable = $areastotables[$filearea];
	
    $entryid = (int)array_shift($args);

    if (!$project = $DB->get_record('promising', array('id' => $cm->instance))) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_promising/$filearea/$entryid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    
    $entry = $DB->get_record($relatedtable, array('id' => $entryid));
    // Make sure groups allow this user to see this file
    if($entry){
		if(isset($entry->groupid)){
			if ($entry->groupid > 0 and $groupmode = groups_get_activity_groupmode($cm, $course)) {   // Groups are being used
			   if (!groups_group_exists($entry->groupid)) { // Can't find group
					return false;                           // Be safe and don't send it to anyone
				}

				if (!groups_is_member($entry->groupid) and !has_capability('moodle/site:accessallgroups', $context)) {
					// do not send posts from other groups when in SEPARATEGROUPS or VISIBLEGROUPS
					return false;
				}
			}
		}
    }
    if ((!isloggedin() || isguestuser()) && !$project->guestsallowed){
        return false;
    }

    // finally send the file
    send_stored_file($file, 0, 0, true); // download MUST be forced - security!
}
