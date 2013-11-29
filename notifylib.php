<?php

/*
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
* Notifies all project managers of a new specification being entered
*
*/
function promising_notify_new_specification(&$project, $cmid, &$specification, $currentgroupid){
    global $USER, $COURSE, $CFG, $DB;

    $class = get_string('specification', 'promising');
	if (!$severity = $DB->get_record('promising_qualifier', array('code' => $specification->severity, 'domain' => 'severity', 'projectid' => $project->id))){
	    $severity = $DB->get_record('promising_qualifier', array('code' => $specification->severity, 'domain' => 'severity', 'projectid' => 0));
	}
	if (!$priority = $DB->get_record('promising_qualifier', array('code' => $specification->priority, 'domain' => 'priority', 'projectid' => $project->id))){
	    $priority = $DB->get_record('promising_qualifier', array('code' => $specification->priority, 'domain' => 'priority', 'projectid' => 0));
	}
	if (!$complexity = $DB->get_record('promising_qualifier', array('code' => $specification->complexity, 'domain' => 'complexity', 'projectid' => $project->id))){
	    $complexity = $DB->get_record('promising_qualifier', array('code' => $specification->complexity, 'domain' => 'complexity', 'projectid' => 0));
	}
    if (!$severity) $severity->label = "N.Q.";
    if (!$priority) $priority->label = "N.Q.";
    if (!$complexity) $complexity->label = "N.Q.";
    $qualifiers[] = get_string('severity', 'promising').': '.$severity->label;
    $qualifiers[] = get_string('priority', 'promising').': '.$priority->label;
    $qualifiers[] = get_string('complexity', 'promising').': '.$complexity->label;
	$projectheading = $DB->get_record('promising_heading', array('projectid' => $project->id, 'groupid' => $currentgroupid));
	$message = promising_compile_mail_template('newentrynotify', array(
	    'PROJECT' => $projectheading->title,
	    'CLASS' => $class,
	    'USER' => fullname($USER),
	    'ENTRYNODE' => implode(".", promising_tree_get_upper_branch('promising_specification', $specification->id, true, true)),
	    'ENTRYABSTRACT' => stripslashes($specification->abstract),
	    'ENTRYDESCRIPTION' => $specification->description,
	    'QUALIFIERS' => implode('<br/>', $qualifiers),
	    'ENTRYLINK' => $CFG->wwwroot."/mod/promising/view.php?id={$cmid}&view=specifications&group={$currentgroupid}"
	), 'promising');       		
	$context = context_module::instance($cmid);
	$managers = get_users_by_capability($context, 'mod/promising:manage', 'u.id, firstname, lastname, email, picture, mailformat');
	if (!empty($managers)){
   		foreach($managers as $manager){
       		email_to_user ($manager, $USER, $COURSE->shortname .' - '.get_string('notifynewspec', 'promising'), html_to_text($message), $message);
       	}
    }
}

/**
* Notifies all project managers of a new requirement being entered
*
*
*/
function promising_notify_new_requirement(&$project, $cmid, &$requirement, $currentgroupid){
    global $USER, $COURSE, $CFG, $DB;
    $class = get_string('requirement', 'promising');
	if (!$strength = $DB->get_record('promising_qualifier', array('code' => $requirement->strength, 'domain' => 'strength', 'projectid' => $project->id))){
	    $strength = $DB->get_record('promising_qualifier', array('code' => $requirement->strength, 'domain' => 'strength', 'projectid' => 0));
	}
	if (!$strength) $strength->label = "N.Q.";
	$qualifiers[] = get_string('strength', 'promising').': '.$strength->label;
	$projectheading = $DB->get_record('promising_heading', array('projectid' => $project->id, 'groupid' => $currentgroupid));
	$message = promising_compile_mail_template('newentrynotify', array(
	    'PROJECT' => $projectheading->title,
	    'CLASS' => $class,
	    'USER' => fullname($USER),
	    'ENTRYNODE' => implode(".", promising_tree_get_upper_branch('promising_requirement', $requirement->id, true, true)),
	    'ENTRYABSTRACT' => stripslashes($requirement->abstract),
	    'ENTRYDESCRIPTION' => $requirement->description,
	    'QUALIFIERS' => implode('<br/>', $qualifiers),
	    'ENTRYLINK' => $CFG->wwwroot."/mod/promising/view.php?id={$cmid}&view=requirements&group={$currentgroupid}"
	), 'promising');       		
	$context = context_module::instance($cmid);
	$managers = get_users_by_capability($context, 'mod/promising:manage', 'u.id, firstname, lastname, email, picture, mailformat');
	if (!empty($managers)){
   		foreach($managers as $manager){
       		email_to_user ($manager, $USER, $COURSE->shortname .' - '.get_string('notifynewrequ', 'promising'), html_to_text($message), $message);
       	}
    }
}

/**
* Notifies all project managers of a new task being entered
*
*/
function promising_notify_new_task(&$project, $cmid, &$task, $currentgroupid){
    global $USER, $COURSE, $CFG, $DB;
    $class = get_string('task', 'promising');
    if (!$worktype = $DB->get_record('promising_qualifier', array('code' => $task->worktype, 'domain' => 'worktype', 'projectid' => $project->id))){
        $worktype = $DB->get_record('promising_qualifier', array('code' => $task->worktype, 'domain' => 'worktype', 'projectid' => 0));
    }
    if ($task->assignee){
        $assignee = fullname($DB->get_record('user', array('id' => $task->assignee)));
    } else {
        $assignee = get_string('unassigned', 'promising');
    }
    $status = $DB->get_record('promising_status', array('status' => $task->status));
    $planned = $task->planned;
    if (!$risk = $DB->get_record('promising_qualifier', array('code' => $task->risk, 'domain' => 'risk', 'projectid' => $project->id))){
        $risk = $DB->get_record('promising_qualifier', array('code' => $task->risk, 'domain' => 'risk', 'projectid' => 0));
    }

	if (!$worktype) $worktype->label = "N.Q.";
	if (!$status) $status->label = "N.Q.";
	if (!$risk) $risk->label = "N.Q.";

	$qualifiers[] = get_string('worktype', 'promising').': '.$worktype->label;
	$qualifiers[] = get_string('assignee', 'promising').': '.$assignee;
	$qualifiers[] = get_string('status', 'promising').': '.$status->label;
	$qualifiers[] = get_string('planned', 'promising').': '.$planned.' '.$TIMEUNITS[$project->timeunit];
	$qualifiers[] = get_string('risk', 'promising').': '.$risk->label;
	$projectheading = $DB->get_record('promising_heading', array('projectid' => $project->id, 'groupid' => $currentgroupid));
	$message = promising_compile_mail_template('newentrynotify', array(
	    'PROJECT' => $projectheading->title,
	    'CLASS' => $class,
	    'USER' => fullname($USER),
	    'ENTRYNODE' => implode(".", promising_tree_get_upper_branch('promising_task', $task->id, true, true)),
	    'ENTRYABSTRACT' => stripslashes($task->abstract),
	    'ENTRYDESCRIPTION' => $task->description,
	    'QUALIFIERS' => implode('<br/>', $qualifiers),
	    'ENTRYLINK' => $CFG->wwwroot."/mod/promising/view.php?id={$cmid}&view=tasks&group={$currentgroupid}"
	), 'promising');       		
	$context = context_module::instance($cmid);
	$managers = get_users_by_capability($context, 'mod/promising:manage', 'u.id, firstname, lastname, email, picture, mailformat');
	if (!empty($managers)){
   		foreach($managers as $manager){
       		email_to_user ($manager, $USER, $COURSE->shortname .' - '.get_string('notifynewtask', 'promising'), html_to_text($message), $message);
       	}
    }
}


/**
* Notifies all project managers of a new task being entered
*
*/
function promising_notify_new_milestone(&$project, $cmid, &$milestone, $currentgroupid){
    global $USER, $COURSE, $CFG, $DB;
    $class = get_string('milestone', 'promising');
	$qualifiers[] = get_string('datedued', 'promising').': '.userdate($milestone->deadline);
	$projectheading = $DB->get_record('promising_heading', array('projectid' => $project->id, 'groupid' => $currentgroupid));
	$message = promising_compile_mail_template('newentrynotify', array(
	    'PROJECT' => $projectheading->title,
	    'CLASS' => $class,
	    'USER' => fullname($USER),
	    'ENTRYNODE' => implode(".", promising_tree_get_upper_branch('promising_milestone', $milestone->id, true, true)),
	    'ENTRYABSTRACT' => stripslashes($milestone->abstract),
	    'ENTRYDESCRIPTION' => $milestone->description,
	    'QUALIFIERS' => implode('<br/>', $qualifiers),
	    'ENTRYLINK' => $CFG->wwwroot."/mod/promising/view.php?id={$cmid}&view=milestones&group={$currentgroupid}"
	), 'promising');       		
	$context = context_module::instance($cmid);
	$managers = get_users_by_capability($context, 'mod/promising:manage', 'u.id, firstname, lastname, email, picture, mailformat');
	if (!empty($managers)){
   		foreach($managers as $manager){
       		email_to_user ($manager, $USER, $COURSE->shortname .' - '.get_string('notifynewmile', 'promising'), html_to_text($message), $message);
       	}
    }
}

/**
* Notifies an assignee when loosing a task monitoring
*
*/
function promising_notify_task_unassign(&$project, &$task, $oldassigneeid, $currentgroupid){
    global $USER, $COURSE, $DB;    

	$oldAssignee = $DB->get_record('user', array('id' => $oldassigneeid));
	if (!$owner = $DB->get_record('user', array('id' => $task->owner))){
	    $owner = $USER;
	}
	if (!$worktype = $DB->get_record('promising_qualifier', array('code' => $task->worktype, 'domain' => 'worktype', 'projectid' => $project->id))){
	    if (!$worktype = $DB->get_record('promising_qualifier', array('code' => $task->worktype, 'domain' => 'worktype', 'projectid' => 0))){
	        $worktype->label = get_string('unqualified', 'promising');
	    }
	}
	$projectheading = $DB->get_record('promising_heading', array('projectid' => $project->id, 'groupid' => $currentgroupid));
	$message = promising_compile_mail_template('taskreleasenotify', array(
	    'PROJECT' => $projectheading->title,
	    'OWNER' => fullname($owner),
	    'TASKNODE' => implode(".", promising_tree_get_upper_branch('promising_task', $task->id, true, true)),
	    'TASKABSTRACT' => stripslashes($task->abstract),
	    'TASKDESCRIPTION' => $task->description,
	    'WORKTYPE' => $worktype->label,
	    'DONE' => $task->done
	), 'promising');
	email_to_user ($oldAssignee, $owner, $COURSE->shortname .' - '.get_string('notifyreleasedtask', 'promising'), html_to_text($message), $message);
}


/**
* Notifies an assignee when getting assigned
*
*/
function promising_notify_task_assign(&$project, &$task, $currentgroupid){
    global $COURSE, $USER, $DB;    

	if (!$assignee = $DB->get_record('user', array('id' => $task->assignee))){
	    return;
	}
	if (!$owner = $DB->get_record('user', array('id' => $task->owner))){
	    $owner = $USER;
	}
	if (!$worktype = $DB->get_record('promising_qualifier', array('code' => $task->worktype, 'domain' => 'worktype', 'projectid' => $project->id))){
	    if (!$worktype = $DB->get_record('promising_qualifier', array('code' => $task->worktype, 'domain' => 'worktype', 'projectid' => 0))){
	        $worktype->label = get_string('unqualified', 'promising');
	    }
	}
	$projectheading = $DB->get_record('promising_heading', array('projectid' => $project->id, 'groupid' => $currentgroupid));
	$message = promising_compile_mail_template('newtasknotify', array(
	    'PROJECT' => $projectheading->title,
	    'OWNER' => fullname($owner),
	    'TASKNODE' => implode(".", promising_tree_get_upper_branch('promising_task', $task->id, true, true)),
	    'TASKABSTRACT' => stripslashes($task->abstract),
	    'TASKDESCRIPTION' => $task->description,
	    'WORKTYPE' => $worktype->label,
	    'DONE' => $task->done
	), 'promising');
	email_to_user ($assignee, $owner, $COURSE->shortname .' - '.get_string('notifynewtask', 'promising'), html_to_text($message), $message);
}
/**
* Notifications changement de statut d'une étape
*
*/
function promising_notify_milestone_change(&$project, $milestoneid, $typeDemande, $cmid, $currentgroupid){
    global $COURSE, $USER, $DB,$CFG;
	$milestone = $DB->get_record('promising_milestone', array('id' => $milestoneid));
	$context = get_context_instance(CONTEXT_MODULE, $cmid);
	//list($assignableroles, $assigncounts, $nameswithcounts) = get_assignable_roles($context, ROLENAME_BOTH, true);
	$role = 0;
	$notifydescription = '';
	if($typeDemande==1){//Demande de validation envoi aux tuteurs enseignants
		$subject = get_string('notifymilestonchangeaskvalid', 'promising').$project->name;
		$role = $DB->get_record('role', array('shortname' => 'projectens'));//seul les enseignants recoive une notification
		/*
		utile si notification aux entreprises aussi
		$roleEnt = $DB->get_record('role', array('shortname' => 'projectent'));//tuteur entreprises
		$role = array_merge($role,$roleEnt);//on prend les enseigntans et les entreprises
		*/
		$notifydescription = "Une demande de validation de l'étape a été faite";
	}elseif($typeDemande==2){//Demande de révision envoi aux étudiants
		$subject = get_string('notifymilestonchangeaskrevalid', 'promising').$project->name;
		$role = $DB->get_record('role', array('shortname' => 'projectetu'));
		$notifydescription = "Une demande de révision de l'étape a été faite";
	}elseif($typeDemande==3){//étape validé envoi aux étudiants
		$subject = get_string('notifymilestonchangevalid', 'promising').$project->name;
		$role = $DB->get_record('role', array('shortname' => 'projectetu'));
		$notifydescription = "L'étape a été validée";
	}
	$message = promising_compile_mail_template('milestonechangestatut', array(
		'PROJECT' => $project->name,
		'MILESTONE' => $milestone->abstract,
		'MILESTONEDESCRIPTION' => $notifydescription,
		'USER' => fullname($USER),
		'MILESTONELINK' => $CFG->wwwroot."/mod/promising/view.php?id={$cmid}&view=milestones&group={$currentgroupid}"
	), 'promising');
	$roleusers = get_role_users($role->id, $context, false);
	$groupeName = $DB->get_record('role', array('id' => $project->projectgrpid));//nom du groupe auquel le projet est associé
	if (!empty($roleusers)) {
		foreach ($roleusers as $userto) {
			email_to_user ($userto,$USER, $groupeName->name.' - '.$subject, html_to_text($message), $message);
			//email_to_user($otheruser, $supportuser, $subject, $message, $messagehtml);
		}
	}
}
?>