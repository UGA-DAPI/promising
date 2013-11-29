<?php

    /**
    *
    * Requirements operations.
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

    $usehtmleditor = can_use_html_editor();
    $defaultformat = FORMAT_MOODLE;

/// Controller

	$validsessid = required_param('validid', PARAM_INT);
	if (!$validsession = $DB->get_record('promising_valid_session', array('id' => $validsessid))){
		print_error('errorbadvalidsessionid', 'promising');
	}

	if ($formdata = data_submitted()){
		$statekeys = preg_grep('/state_\d+/', array_keys($_POST));
		if (!empty($statekeys)){
			foreach($statekeys as $statekey){
				$stateid = str_replace('state_', '', $statekey);
				$staterec = $DB->get_record('promising_valid_state', array('id' => $stateid));
				$staterec->status = clean_param($_POST[$statekey], PARAM_TEXT);
				$staterec->comment = clean_param($_POST['comment_'.$stateid], PARAM_TEXT);
				$staterec->validatorid = $USER->id;
				$staterec->lastchangedate = time();
				$DB->update_record('promising_valid_state', $staterec);
			}
		}		
	}
	echo $pagebuffer;
	echo $OUTPUT->heading(get_string('updatevalidation', 'promising'));

	promising_print_validation_states_form($validsessid, $project, $currentGroupId, 0, $cm->id);

	echo '<br/>';
	echo '<center>';
	echo '<hr>';
	$options['id'] = $cm->id;
	$options['view'] = 'validations';
	echo $OUTPUT->single_button(new moodle_url($CFG->wwwroot."/mod/promising/view.php", $options), get_string('backtosessions', 'promising'), 'get');
	echo '</center>';
?>