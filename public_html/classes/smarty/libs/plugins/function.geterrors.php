<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

function smarty_function_geterrors($params, &$smarty)
{
    $objects = $params['object'];

    //Allow specifying multiple comma separated objects at once.
    $split_objects = explode(',', $objects);

    $errors = null;
    foreach ($split_objects as $key => $object) {
        if (is_object($smarty->_tpl_vars[$object]->Validator)) {
            //return $smarty->_tpl_vars[$object]->Validator->getErrors();
            $errors .= $smarty->_tpl_vars[$object]->Validator->getErrors();
        } elseif (is_object($smarty->_tpl_vars[$object])) {
            //return $smarty->_tpl_vars[$object]->getErrors();
            $errors .= $smarty->_tpl_vars[$object]->getErrors();
        }
    }

    if ($errors !== null) {
        return $errors;
    }

    return false;
}

/* vim: set expandtab: */;
