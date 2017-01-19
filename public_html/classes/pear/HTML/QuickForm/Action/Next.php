<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Alexey Borzov <avb@php.net>                                  |
// +----------------------------------------------------------------------+
//
// $Id: Next.php,v 1.3 2004/03/02 21:15:45 avb Exp $

require_once 'HTML/QuickForm/Action.php';

/**
 * The action for a 'next' button of wizard-type multipage form. 
 * 
 * @author  Alexey Borzov <avb@php.net>
 * @package HTML_QuickForm_Controller
 * @version $Revision: 1.3 $
 */
class HTML_QuickForm_Action_Next extends HTML_QuickForm_Action
{
    function perform(&$page, $actionName)
    {
        // save the form values and validation status to the session
        $page->isFormBuilt() or $page->buildForm();
        $pageName =  $page->getAttribute('id');
        $data     = $page->controller->container();
        $data['values'][$pageName] = $page->exportValues();
        $data['valid'][$pageName]  = $page->validate();

        // Modal form and page is invalid: don't go further
        if ($page->controller->isModal() && !$data['valid'][$pageName]) {
            return $page->handle('display');
        }
        // More pages?
        if (null !== ($nextName = $page->controller->getNextName($pageName))) {
            $next = $page->controller->getPage($nextName);
            $next->handle('jump');
        // Consider this a 'finish' button, if there is no explicit one
        } elseif($page->controller->isModal()) {
            if ($page->controller->isValid()) {
                $page->handle('process');
            } else {
                // this should redirect to the first invalid page
                $page->handle('jump');
            }
        } else {
            $page->handle('display');
        }
    }
}

?>
