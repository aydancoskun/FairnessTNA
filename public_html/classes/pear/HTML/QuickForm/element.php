<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997, 1998, 1999, 2000, 2001 The PHP Group             |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Adam Daniel <adaniel1@eesus.jnj.com>                        |
// |          Bertrand Mansion <bmansion@mamasam.com>                     |
// +----------------------------------------------------------------------+
//
// $Id: element.php,v 1.32 2004/10/14 20:00:49 avb Exp $

require_once('HTML/Common.php');

/**
 * Base class for form elements
 * 
 * @author       Adam Daniel <adaniel1@eesus.jnj.com>
 * @author       Bertrand Mansion <bmansion@mamasam.com>
 * @version      1.3
 * @since        PHP4.04pl1
 * @access       public
 * @abstract
 */
class HTML_QuickForm_element extends HTML_Common
{
    // {{{ properties

    /**
     * Label of the field
     * @var       string
     * @since     1.3
     * @access    private
     */
    static $_label = '';

    /**
     * Form element type
     * @var       string
     * @since     1.0
     * @access    private
     */
    static $_type = '';

    /**
     * Flag to tell if element is frozen
     * @var       boolean
     * @since     1.0
     * @access    private
     */
    static $_flagFrozen = false;

    /**
     * Does the element support persistant data when frozen
     * @var       boolean
     * @since     1.3
     * @access    private
     */
    static $_persistantFreeze = false;
    
    // }}}
    // {{{ constructor
    
    /**
     * Class constructor
     * 
     * @param    string     Name of the element
     * @param    mixed      Label(s) for the element
     * @param    mixed      Associative array of tag attributes or HTML attributes name="value" pairs
     * @since     1.0
     * @access    public
     * @return    void
     */
    function HTML_QuickForm_element($elementName=null, $elementLabel=null, $attributes=null)
    {
        //HTML_Common::HTML_Common($attributes); // cannot call like this, this will cause Fatal error: Non-static method HTML_Common::HTML_Common() cannot be called statically
        new HTML_Common($attributes);
        if (isset($elementName)) {
            self::setName($elementName);
        }
        if (isset($elementLabel)) {
            self::setLabel($elementLabel);
        }
    } //end constructor
    
    // }}}
    // {{{ apiVersion()

    /**
     * Returns the current API version
     *
     * @since     1.0
     * @access    public
     * @return    float
     */
    static function apiVersion()
    {
        return 2.0;
    } // end func apiVersion

    // }}}
    // {{{ getType()

    /**
     * Returns element type
     *
     * @since     1.0
     * @access    public
     * @return    string
     */
    static function getType()
    {
        return self::$_type;
    } // end func getType

    // }}}
    // {{{ setName()

    /**
     * Sets the input field name
     * 
     * @param     string    $name   Input field name attribute
     * @since     1.0
     * @access    public
     * @return    void
     */
    static function setName($name)
    {
        // interface method
    } //end func setName
    
    // }}}
    // {{{ getName()

    /**
     * Returns the element name
     * 
     * @since     1.0
     * @access    public
     * @return    string
     */
    static function getName()
    {
        // interface method
    } //end func getName
    
    // }}}
    // {{{ setValue()

    /**
     * Sets the value of the form element
     *
     * @param     string    $value      Default value of the form element
     * @since     1.0
     * @access    public
     * @return    void
     */
    static function setValue($value)
    {
        // interface
    } // end func setValue

    // }}}
    // {{{ getValue()

    /**
     * Returns the value of the form element
     *
     * @since     1.0
     * @access    public
     * @return    mixed
     */
    static function getValue()
    {
        // interface
        return null;
    } // end func getValue
    
    // }}}
    // {{{ freeze()

    /**
     * Freeze the element so that only its value is returned
     * 
     * @access    public
     * @return    void
     */
    static function freeze()
    {
        self::$_flagFrozen = true;
    } //end func freeze

    // }}}
    // {{{ unfreeze()

   /**
    * Unfreezes the element so that it becomes editable
    *
    * @access public
    * @return void
    * @since  3.2.4
    */
    static function unfreeze()
    {
        self::$_flagFrozen = false;
    }

    // }}}
    // {{{ getFrozenHtml()

    /**
     * Returns the value of field without HTML tags
     * 
     * @since     1.0
     * @access    public
     * @return    string
     */
    static function getFrozenHtml()
    {
        $value = self::getValue();
        return ('' != $value? htmlspecialchars($value): '&nbsp;') .
               self::_getPersistantData();
    } //end func getFrozenHtml
    
    // }}}
    // {{{ _getPersistantData()

   /**
    * Used by getFrozenHtml() to pass the element's value if _persistantFreeze is on
    * 
    * @access private
    * @return string
    */
    static function _getPersistantData()
    {
        if (!self::$_persistantFreeze) {
            return '';
        } else {
            $id = self::getAttribute('id');
            return '<input type="hidden"' .
                   (isset($id)? ' id="' . $id . '"': '') .
                   ' name="' . self::getName() . '"' .
                   ' value="' . htmlspecialchars(self::getValue()) . '" />';
        }
    }

    // }}}
    // {{{ isFrozen()

    /**
     * Returns whether or not the element is frozen
     *
     * @since     1.3
     * @access    public
     * @return    bool
     */
    static function isFrozen()
    {
        return self::$_flagFrozen;
    } // end func isFrozen

    // }}}
    // {{{ setPersistantFreeze()

    /**
     * Sets wether an element value should be kept in an hidden field
     * when the element is frozen or not
     * 
     * @param     bool    $persistant   True if persistant value
     * @since     2.0
     * @access    public
     * @return    void
     */
    static function setPersistantFreeze($persistant=false)
    {
        self::$_persistantFreeze = $persistant;
    } //end func setPersistantFreeze

    // }}}
    // {{{ setLabel()

    /**
     * Sets display text for the element
     * 
     * @param     string    $label  Display text for the element
     * @since     1.3
     * @access    public
     * @return    void
     */
    static function setLabel($label)
    {
        self::$_label = $label;
    } //end func setLabel

    // }}}
    // {{{ getLabel()

    /**
     * Returns display text for the element
     * 
     * @since     1.3
     * @access    public
     * @return    string
     */
    static function getLabel()
    {
        return self::$_label;
    } //end func getLabel

    // }}}
    // {{{ _findValue()

    /**
     * Tries to find the element value from the values array
     * 
     * @since     2.7
     * @access    private
     * @return    mixed
     */
    static function _findValue(&$values)
    {
        if (empty($values)) {
            return null;
        }
        $elementName = self::getName();
        if (isset($values[$elementName])) {
            return $values[$elementName];
        } elseif (strpos($elementName, '[')) {
            $myVar = "['" . str_replace(array(']', '['), array('', "']['"), $elementName) . "']";
            return eval("return (isset(\$values$myVar)) ? \$values$myVar : null;");
        } else {
            return null;
        }
    } //end func _findValue

    // }}}
    // {{{ onQuickFormEvent()

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param     string    $event  Name of event
     * @param     mixed     $arg    event arguments
     * @param     object    $caller calling object
     * @since     1.0
     * @access    public
     * @return    void
     */
    static function onQuickFormEvent($event, $arg, &$caller)
    {
        switch ($event) {
            case 'createElement':
                $className = get_class($this);
                self::$className($arg[0], $arg[1], $arg[2], $arg[3], $arg[4]);
                break;
            case 'addElement':
                self::onQuickFormEvent('createElement', $arg, $caller);
                self::onQuickFormEvent('updateValue', null, $caller);
                break;
            case 'updateValue':
                // constant values override both default and submitted ones
                // default values are overriden by submitted
                $value = self::_findValue($caller->_constantValues);
                if (null === $value) {
                    $value = self::_findValue($caller->_submitValues);
                    if (null === $value) {
                        $value = self::_findValue($caller->_defaultValues);
                    }
                }
                if (null !== $value) {
                    self::setValue($value);
                }
                break;
            case 'setGroupValue':
                self::setValue($arg);
        }
        return true;
    } // end func onQuickFormEvent

    // }}}
    // {{{ accept()

   /**
    * Accepts a renderer
    *
    * @param object     An HTML_QuickForm_Renderer object
    * @param bool       Whether an element is required
    * @param string     An error message associated with an element
    * @access public
    * @return void 
    */
    static function accept(&$renderer, $required=false, $error=null)
    {
        $renderer->renderElement($this, $required, $error);
    } // end func accept

    // }}}
    // {{{ _generateId()

   /**
    * Automatically generates and assigns an 'id' attribute for the element.
    * 
    * Currently used to ensure that labels work on radio buttons and
    * checkboxes. Per idea of Alexander Radivanovich.
    *
    * @access private
    * @return void 
    */
    static function _generateId()
    {
        static $idx = 1;

        if (!self::getAttribute('id')) {
            self::updateAttributes(array('id' => 'qf_' . substr(md5(microtime() . $idx++), 0, 6)));
        }
    } // end func _generateId

    // }}}
    // {{{ exportValue()

   /**
    * Returns a 'safe' element's value
    *
    * @param  array   array of submitted values to search
    * @param  bool    whether to return the value as associative array
    * @access public
    * @return mixed
    */
    static function exportValue(&$submitValues, $assoc = false)
    {
        $value = self::_findValue($submitValues);
        if (null === $value) {
            $value = self::getValue();
        }
        return self::_prepareValue($value, $assoc);
    }
    
    // }}}
    // {{{ _prepareValue()

   /**
    * Used by exportValue() to prepare the value for returning
    *
    * @param  mixed   the value found in exportValue()
    * @param  bool    whether to return the value as associative array
    * @access private
    * @return mixed
    */
    static function _prepareValue($value, $assoc)
    {
        if (null === $value) {
            return null;
        } elseif (!$assoc) {
            return $value;
        } else {
            $name = self::getName();
            if (!strpos($name, '[')) {
                return array($name => $value);
            } else {
                $valueAry = array();
                $myIndex  = "['" . str_replace(array(']', '['), array('', "']['"), $name) . "']";
                eval("\$valueAry$myIndex = \$value;");
                return $valueAry;
            }
        }
    }
    
    // }}}
} // end class HTML_QuickForm_element
?>