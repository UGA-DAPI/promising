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
	if ($work == 'dodelete') {
		$requid = required_param('requid', PARAM_INT);
		promising_tree_delete($requid, 'promising_requirement');

        // delete all related records
		$DB->delete_records('promising_spec_to_req', array('reqid' => $requid));
        add_to_log($course->id, 'promising', 'changerequirement', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentGroupId}", 'delete', $cm->id);
	}
	elseif ($work == 'domove' || $work == 'docopy') {
		$ids = required_param('ids', PARAM_INT);
		$to = required_param('to', PARAM_ALPHA);
		$autobind = false;
		$bindtable = '';
		switch($to){
		    case 'specs' :
		    	$table2 = 'promising_specification'; 
		    	$redir = 'specification'; 
		    	$autobind = false;
		    	break;
		    case 'specswb' :
		    	$table2 = 'promising_specification'; 
		    	$redir = 'specification'; 
		    	$autobind = true;
		    	$bindtable = 'promising_spec_to_req';
		    	break;
		    case 'tasks' : 
		    	$table2 = 'promising_task'; 
		    	$redir = 'task'; 
		    	break;
		    case 'deliv' : 
		    	$table2 = 'promising_deliverable'; 
		    	$redir = 'deliverable'; 
		    	break;
		    default:
		    	error('Bad copy case', $CFG->wwwroot."/mod/promising/view.php?id=$cm->id");
		}
		promising_tree_copy_set($ids, 'promising_requirement', $table2, 'description,format,abstract,projectid,groupid,ordering', $autobind, $bindtable);
        add_to_log($course->id, 'promising', "change{$redir}", "view.php?id={$cm->id}&amp;view={$redir}s&amp;group={$currentGroupId}", 'delete', $cm->id);
		if ($work == 'domove'){
		    // bounce to deleteitems
		    $work = 'dodeleteitems';
		    $withredirect = 1;
		} else {
		    redirect("{$CFG->wwwroot}/mod/promising/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'promising') . ' : ' . get_string($redir, 'promising'));
	    }
	}
	if ($work == 'dodeleteitems') {
		$ids = required_param('ids', PARAM_INT);
		foreach($ids as $anItem){

    	    // save record for further cleanups and propagation
    	    $oldRecord = $DB->get_record('promising_requirement', array('id' => $anItem));
		    $childs = $DB->get_records('promising_requirement', array('fatherid' => $anItem));
		    // update fatherid in childs 
		    $query = "
		        UPDATE
		            {promising_requirement}
		        SET
		            fatherid = $oldRecord->fatherid
		        WHERE
		            fatherid = $anItem
		    ";
		    $DB->execute($query);

            // delete record for this item
    		$DB->delete_records('promising_requirement', array('id' => $anItem));
            // delete all related records for this item
    		$DB->delete_records('promising_spec_to_req', array('projectid' => $project->id, 'groupid' => $currentGroupId, 'reqid' => $anItem));
    	}
        add_to_log($course->id, 'promising', 'deleterequirement', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentGroupId}", 'deleteItems', $cm->id);
    	if (isset($withredirect) && $withredirect){
		    redirect("{$CFG->wwwroot}/mod/promising/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'promising') . ' : ' . get_string($redir, 'promising'));
		}
	}
	elseif ($work == 'doclearall') {
        // delete all records. POWERFUL AND DANGEROUS COMMAND.
		$DB->delete_records('promising_requirement', array('projectid' => $project->id, 'groupid' => $currentGroupId));
		$DB->delete_records('promising_spec_to_req', array('projectid' => $project->id, 'groupid' => $currentGroupId));
        add_to_log($course->id, 'promising', 'changerequirement', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentGroupId}", 'clear', $cm->id);
	}
	elseif ($work == 'doexport') {
	    $ids = required_param('ids', PARAM_INT);
	    $idlist = implode("','", $ids);
	    $select = "
	       id IN ('$idlist')	       
	    ";
	    $requirements = $DB->get_records_select('promising_requirement', $select);
	    $strengthes = $DB->get_records_select('promising_qualifier', " projectid = $project->id AND domain = 'strength' ");
	    if (empty($strenghes)){
	        $strengthes = $DB->get_records_select('promising_qualifier', " projectid = 0 AND domain = 'strength' ");
	    }
	    include "xmllib.php";
	    $xmlstrengthes = recordstoxml($strengthes, 'strength', '', false, 'promising');
	    $xml = recordstoxml($requirements, 'requirement', $xmlstrengthes);
	    $escaped = str_replace('<', '&lt;', $xml);
	    $escaped = str_replace('>', '&gt;', $escaped);
	    echo $OUTPUT->heading(get_string('xmlexport', 'promising'));
	    print_simple_box("<pre>$escaped</pre>");
        add_to_log($course->id, 'promising', 'changerequirement', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentGroupId}", 'export', $cm->id);
        echo $OUTPUT->continue_button("view.php?view=requirements&amp;id=$cm->id");
        return;
	}
	elseif ($work == 'up') {
		$requid = required_param('requid', PARAM_INT);
		promising_tree_up($project, $currentGroupId, $requid, 'promising_requirement');
	}
	elseif ($work == 'down') {
		$requid = required_param('requid', PARAM_INT);
		promising_tree_down($project, $currentGroupId, $requid, 'promising_requirement');
	}
	elseif ($work == 'left') {
		$requid = required_param('requid', PARAM_INT);
		promising_tree_left($project, $currentGroupId, $requid, 'promising_requirement');
	}
	elseif ($work == 'right') {
		$requid = required_param('requid', PARAM_INT);
		promising_tree_right($project, $currentGroupId, $requid, 'promising_requirement');
	}
