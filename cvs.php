<?php

    /*
    *
    * This screen allows remote code repository setup and control.
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

    if (!has_capability('mod/promising:manage', $context)){
        print_error(get_string('notateacher','promising'));
        return;
    }

	echo $pagebuffer;

    echo $OUTPUT->box(get_string('notimplementedyet', 'promising'), 'center', '50%');
?>