<?php

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * Milestone operations.
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
    * a form constraint checking function
    * @param object $project the surrounding project cntext
    * @param object $milestone form object to be checked
    * @return a control hash array telling error statuses
    */
/// Controller


if ($work == 'add' || $work == 'update') {
	include 'edit_milestone.php';
/// Clear all *********************************************************

} elseif ($work == 'clearall') {
	echo $pagebuffer;
    echo '<center>';
	echo $OUTPUT->heading(get_string('clearallmilestones','promising')); 
    echo $OUTPUT->box (get_string('clearwarning','promising'), $align = 'center', $width = '80%', $color = '#FF3030', $padding = 5, $class = 'generalbox'); 
    ?>
    <script type="text/javascript">
    function senddata(){
        document.clearmilestoneform.work.value='doclearall';
        document.clearmilestoneform.submit();
    }
    function cancel(){
        document.clearmilestoneform.submit();
    }
    </script>
    <form name="clearmilestoneform" method="post" action="view.php">
    <input type="hidden" name="work" value="" />
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="button" name="go_btn" value="<?php print_string('yes') ?>"  onclick="senddata();"/>
    <input type="button" name="cancel_btn" value="<?php print_string('no') ?>" onclick="cancel();" />
    </form>
    </center>
    <?php
} else {
	if ($work){
		$url="";
		$urlretour=$CFG->wwwroot."/mod/promising/view.php?id={$cm->id}&amp;view=milestones";
		include 'milestones.controller.php';
	}
	echo $pagebuffer;
	//messages de changement de satut des étapes !!
	if($work =='askvalider'){
		echo $OUTPUT->confirm("Votre étape a bien été soumise à la validation. Vous pouvez, de plus, commenter cette demande de validation.<br /><br />Voulez-vous laisser un commentaire sur cette demande dans la partie messages ?", $url, $urlretour);
	}elseif($work =='refuser'){
		echo $OUTPUT->confirm("La mise en révision de l'étape a bien été faite. Vous pouvez, de plus, commenter cette demande de révision.<br /><br />Voulez-vous laisser un commentaire sur cette demande dans la partie messages ?", $url, $urlretour);
	}else{
		promising_print_milestones($project, $currentGroupId, NULL, $cm->id);
		   if ($USER->editmode == 'on' && (has_capability('mod/promising:changemiles', $context)) && $project->etat==0) {
			echo "<br/><a href='view.php?id={$cm->id}&amp;work=add'>".get_string('addmilestone','promising')."</a>";
			//echo " - <a href='view.php?id={$cm->id}&amp;work=clearall'>".get_string('clearall','promising')."</a>";
			//echo " - <a href='view.php?id={$cm->id}&amp;work=sortbydate'>".get_string('sortbydate','promising')."</a>";
		}
	}
}
?>