<?php
/**
* $Id: nl.php,v 1.6 2004/10/29 10:19:58 mike Exp $
*/

$this->dateFormats = array(
    I18Nv2_DATETIME_SHORT     =>  '%d/%m/%y',
    I18Nv2_DATETIME_DEFAULT   =>  '%d-%b-%Y',
    I18Nv2_DATETIME_MEDIUM    =>  '%d-%b-%Y',
    I18Nv2_DATETIME_LONG      =>  '%d %B %Y',
    I18Nv2_DATETIME_FULL      =>  '%A, %d %B %Y'
);
$this->timeFormats = array(
    I18Nv2_DATETIME_SHORT     =>  '%H:%M',
    I18Nv2_DATETIME_DEFAULT   =>  '%H:%M:%S',
    I18Nv2_DATETIME_MEDIUM    =>  '%H:%M:%S',
    I18Nv2_DATETIME_LONG      =>  '%H:%M:%S %Z',
    I18Nv2_DATETIME_FULL      =>  '%H:%M %Z'
);
?>