<?php

    /**
    *
    * This screen show tasks plan grouped by worktype.
    *
	* @package mod-promising
	* @category mod
	* @author Yohan Thomas - W3C2i (support@w3c2i.com)
	* @date 30/09/2013
	* @version 3.0
	* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
	*
	*/

	if (!defined('MOODLE_INTERNAL'))  die('You cannot use this script that way');

	echo $pagebuffer;

    $TIMEUNITS = array(get_string('unset','promising'),get_string('hours','promising'),get_string('halfdays','promising'),get_string('days','promising'));
    /** useless ?
    if (!groups_get_activity_groupmode($cm, $project->course)){
        $groupusers = get_course_users($project->course);
    } else {
        $groupusers = get_group_users($currentGroupId);
    }*/
    // get tasks by worktype
    $query = "
       SELECT
          t.*
       FROM
          {promising_task} as t
       LEFT JOIN
          {promising_qualifier} as qu
       ON 
          qu.code = t.worktype AND
          qu.domain = 'worktype'
       WHERE
          t.projectid = {$project->id} AND
          t.groupid = {$currentGroupId}
       ORDER BY
          qu.id ASC
    ";
    if ($tasks = $DB->get_records_sql($query)){
    ?>
    <script type="text/javascript">
    function sendgroupdata(){
        document.groupopform.submit();
    }
    </script>
    <form name="groupopform" action="view.php" method="post">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="work" value="groupcmd" />
    <input type="hidden" name="view" value="tasks" />
    <?php    
        foreach($tasks as $aTask){
            $sortedtasks[$aTask->worktype][] = $aTask;
        }
        foreach(array_keys($sortedtasks) as $aWorktype){
        	$hidesub = "<a href=\"javascript:toggle('{$aWorktype}','sub{$aWorktype}');\"><img name=\"img{$aWorktype}\" src=\"{$CFG->wwwroot}/mod/promising/pix/p/switch_minus.gif\" alt=\"collapse\" style=\"background-color : #E0E0E0\" /></a>";
            $theWorktype = promising_get_option_by_key('worktype', $project->id, $aWorktype);
            if ($aWorktype == ''){
                 $worktypeicon = '';
                 $theWorktype->label = format_text(get_string('untypedtasks', 'promising'), FORMAT_HTML)."</span>";
            } else {
                 $worktypeicon = "<img src=\"{$CFG->wwwroot}/mod/promising/pix/p/{$theWorktype->code}.gif\" title=\"{$theWorktype->description}\" style=\"background-color : #F0F0F0\" />";
            }
            echo $OUTPUT->box($hidesub.' '.$worktypeicon.' <span class="worktypesheadingcontent">'.$theWorktype->label.'</span>', 'center', '100%', 'white', 4, 'worktypesbox');
            echo "<div id=\"sub{$aWorktype}\">";
            foreach($sortedtasks[$aWorktype] as $aTask){
                promising_print_single_task($aTask, $project, $currentGroupId, $cm->id, count($sortedtasks[$aWorktype]), 'SHORT_WITHOUT_TYPE');
            }
            echo '</div>';
        }
        echo '<p>';
    	promising_print_group_commands();
        echo '</p>';
    ?>
    </form>
<?php
    } else {
       echo $OUTPUT->box(get_string('notasks', 'promising'), 'center', '70%');
    }
?>