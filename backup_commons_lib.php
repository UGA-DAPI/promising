<?php

/**
*
* used in restorelib.php for restoring entities.
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

global $SITE;
$SITE->PROMISING_BACKUP_FIELDS['requirement'] = 'abstract,description,format,strength,heavyness';
$SITE->PROMISING_BACKUP_FIELDS['specification'] = 'abstract,description,format,priority,severity,complexity';
$SITE->PROMISING_BACKUP_FIELDS['task'] = 'owner,assignee,abstract,description,format,worktype,status,costrate,planned,quoted,done,used,spent,risk,,milestoneid,taskstartenable,taskstart,taskendenable,taskend';
$SITE->PROMISING_BACKUP_FIELDS['milestone'] = 'abstract,description,format,deadline,deadlineenable';
$SITE->PROMISING_BACKUP_FIELDS['deliverable'] = 'abstract,description,format,status,milestoneid,localfile,url';

// used in restorelib.php for restoring associations.
$SITE->PROMISING_ASSOC_TABLES['specid'] = 'promising_specification';
$SITE->PROMISING_ASSOC_TABLES['reqid'] = 'promising_requirement';
$SITE->PROMISING_ASSOC_TABLES['delivid'] = 'promising_deliverable';
$SITE->PROMISING_ASSOC_TABLES['taskid'] = 'promising_task';
$SITE->PROMISING_ASSOC_TABLES['master'] = 'promising_task';
$SITE->PROMISING_ASSOC_TABLES['slave'] = 'promising_task';

if (!function_exists('backup_get_new_id')){

    /**
    * an utility function for cleaning restorelib.php code
    * @param restore the restore info structure
    * @return the new integer id
    */
    function backup_get_new_id($restorecode, $tablename, $oldid){
        $status = backup_getid($restorecode, $tablename, $oldid);
        if (is_object($status))
            return $status->new_id;
        return 0;
    }
}
?>