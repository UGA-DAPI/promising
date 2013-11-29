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
    require_once $CFG->dirroot.'/mod/promising/importlib.php';
    if (!has_capability('mod/promising:viewprojectcontrols', $context) && !has_capability('mod/promising:manage', $context)){
        print_error(get_string('notateacher','promising'));
        return;
    }
/// perform local use cases
    /******************************* exports as XML a full project description **************************/
    include_once($CFG->libdir."/uploadlib.php");

	echo $pagebuffer;

    if ($work == 'doexportall'){
        $xml = promising_get_full_xml($project, $currentGroupId);
        echo $OUTPUT->heading(get_string('xmlexport', 'promising'));
        $xml = str_replace('<', '&lt;', $xml);
        $xml = str_replace('>', '&gt;', $xml);
        echo $OUPTUT->box("<pre>$xml</pre>");
        echo $OUTPUT->continue_button("view.php?id={$cm->id}");    
        return;
    }
    /************************************ clears an existing XSL sheet *******************************/
    if ($work == 'loadxsl'){
        $uploader = new upload_manager('xslfilter', false, false, $course->id, true, 0, true);
        $uploader->preprocess_files();
        $project->xslfilter = $uploader->get_new_filename();
        $DB->update_record('promising', addslashes_recursive($project));
        if (!empty($project->xslfilter)){
            $uploader->save_files("{$course->id}/moddata/promising/{$project->id}");
        }
    }
    /************************************ clears an existing XSL sheet *******************************/
    if ($work == 'clearxsl'){
        include_once "filesystemlib.php";
        $xslsheetname = $DB->get_field('promising', 'xslfilter', array('id' => $project->id));    
        filesystem_delete_file("{$course->id}/moddata/promising/{$project->id}/$xslsheetname");
        $DB->set_field('promising', 'xslfilter', '', array('id' => $project->id));
        $project->xslfilter = '';
    }
    /************************************ clears an existing XSL sheet *******************************/
    if ($work == 'loadcss'){
        $uploader = new upload_manager('cssfilter', false, false, $course->id, true, 0, true);
        $uploader->preprocess_files();
        $project->cssfilter = $uploader->get_new_filename();
        $DB->update_record('promising', addslashes_recursive($project));
        if (!empty($project->cssfilter)){
            $uploader->save_files("{$course->id}/moddata/promising/{$project->id}");
        }
    }
    /************************************ clears an existing XSL sheet *******************************/
    if ($work == 'clearcss'){
        include_once "filesystemlib.php";
        $csssheetname = $DB->get_field('promising', 'cssfilter', array('id' => $project->id));    
        filesystem_delete_file("{$course->id}/moddata/promising/{$project->id}/$csssheetname");
        $DB->set_field('promising', 'cssfilter', '', array('id' => $project->id));
        $project->cssfilter = '';
    }

    if ($work == 'importdata'){
    	$entitytype = required_param('entitytype', PARAM_ALPHA);
        $uploader = new upload_manager('entityfile', true, false, $course->id, false, 0, false);
        $uploader->preprocess_files();
        $uploader->process_file_uploads($CFG->dataroot.'/tmp');
        $file = $uploader->get_new_filepath();
        $data = implode('', file($file));
        promising_import_entity($project->id, $id, $data, $entitytype, $currentGroupId);
    }
/// write output view
    echo $OUTPUT->heading(get_string('importsexports', 'promising'));
    echo $OUTPUT->heading(get_string('imports', 'promising'), '3');
    echo $OUTPUT->box_start();
?>    
    <form name="importdata" method="post" enctype="multipart/form-data" style="display:block">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="view" value="teacher_load" />
    <input type="hidden" name="work" value="importdata" />
    <select name="entitytype" />
    	<option value="requs"><?php print_string('requirements', 'promising') ?></option>
    	<option value="specs"><?php print_string('specifications', 'promising') ?></option>
    	<option value="tasks"><?php print_string('tasks', 'promising') ?></option>
    	<option value="deliv"><?php print_string('deliverables', 'promising') ?></option>
	</select>
	<?php echo $OUTPUT->help_icon('importdata', 'promising') ?>
    <input type="file" name="entityfile" />
    <input type="submit" name="go_btn" value="<?php print_string('import', 'promising') ?>" />
    </form>
<?php  
    echo $OUTPUT->box_end();
    echo $OUTPUT->heading(get_string('exports', 'promising'), '3');
    echo $OUTPUT->box_start();
?>
    <ul>
    <li><a href="?work=doexportall&amp;id=<?php p($cm->id) ?>"><?php print_string('exportallforcurrentgroup', 'promising') ?></a></li>
    <?php
    if (has_capability('mod/promising:manage', $context)){
    ?>
    <li><a href="Javascript:document.forms['export'].submit()"><?php print_string('loadcustomxslsheet', 'promising') ?></a>
    <form name="export" method="post" enctype="multipart/form-data" style="display:inline">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="view" value="teacher_load" />
    <input type="hidden" name="work" value="loadxsl" />
    <?php
        if (@$project->xslfilter){
            echo '('.get_string('xslloaded', 'promising').": {$project->xslfilter}) ";
        }
        else{
            echo '('.get_string('xslloaded', 'promising').': '.get_string('default', 'promising').') ';
        }
    ?>
    <input type="file" name="xslfilter" />
    </form>
    <a href="view.php?id=<?php p($cm->id)?>&amp;work=clearxsl"><?php print_string('clearcustomxslsheet', 'promising') ?></a>
    </li>
    <?php
    }
    ?>
    <?php
    if (has_capability('mod/promising:manage', $context)){
    ?>
    <li><a href="Javascript:document.forms['exportcss'].submit()"><?php print_string('loadcustomcsssheet', 'promising') ?></a>
    <form name="exportcss" method="post" enctype="multipart/form-data" style="display:inline">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="view" value="teacher_load" />
    <input type="hidden" name="work" value="loadcss" />
    <?php
        if (@$project->cssfilter){
            echo '('.get_string('cssloaded', 'promising').": {$project->cssfilter}) ";
        }
        else{
            echo '('.get_string('cssloaded', 'promising').': '.get_string('default', 'promising').') ';
        }
    ?>
    <input type="file" name="cssfilter" />
    </form>
    <a href="view.php?id=<?php p($cm->id)?>&amp;work=clearcss"><?php print_string('clearcustomcsssheet', 'promising') ?></a>
    </li>
    <?php
    }
    ?>
    <li><a href="xmlview.php?id=<?php p($cm->id) ?>" target="_blank"><?php print_string('makedocument', 'promising') ?></a></li>
    </ul>
    <?php
    echo $OUTPUT->box_end();
?>
