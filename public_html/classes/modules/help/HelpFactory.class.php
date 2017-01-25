<?php
/**********************************************************************************
 * This file is part of "FairnessTNA", a Payroll and Time Management program.
 * FairnessTNA is copyright 2013-2017 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * others. For full attribution and copyrights details see the COPYRIGHT file.
 *
 * FairnessTNA is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * FairnessTNA is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *********************************************************************************/


/**
 * @package Modules\Help
 */
class HelpFactory extends Factory
{
    protected $table = 'help';
    protected $pk_sequence_name = 'help_id_seq'; //PK Sequence name


    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'type':
                $retval = array(
                    10 => TTi18n::gettext('Form'),
                    20 => TTi18n::gettext('Page')
                );
                break;
            case 'status':
                $retval = array(
                    10 => TTi18n::gettext('NEW'),
                    15 => TTi18n::gettext('Pending Approval'),
                    20 => TTi18n::gettext('ACTIVE')
                );
                break;

        }

        return $retval;
    }


    public function getType()
    {
        return (int)$this->data['type_id'];
    }

    public function setType($type)
    {
        $type = trim($type);

        $key = Option::getByValue($type, $this->getOptions('type'));
        if ($key !== false) {
            $type = $key;
        }

        Debug::Text('bType: ' . $type, __FILE__, __LINE__, __METHOD__, 10);
        if ($this->Validator->inArrayKey('type',
            $type,
            TTi18n::gettext('Incorrect Type'),
            $this->getOptions('type'))
        ) {
            $this->data['type_id'] = $type;

            return false;
        }

        return false;
    }

    public function getStatus()
    {
        return (int)$this->data['status_id'];
    }

    public function setStatus($status)
    {
        $status = trim($status);

        $key = Option::getByValue($status, $this->getOptions('status'));
        if ($key !== false) {
            $status = $key;
        }

        if ($this->Validator->inArrayKey('status',
            $status,
            TTi18n::gettext('Incorrect Status'),
            $this->getOptions('status'))
        ) {
            $this->data['status_id'] = $status;

            return false;
        }

        return false;
    }

    public function getHeading()
    {
        return $this->data['heading'];
    }

    public function setHeading($value)
    {
        $value = trim($value);

        if ($value == null
            or
            $this->Validator->isLength('heading',
                $value,
                TTi18n::gettext('Incorrect Heading length'),
                2, 255)
        ) {
            $this->data['heading'] = $value;

            return false;
        }

        return false;
    }

    public function getBody()
    {
        return $this->data['body'];
    }

    public function setBody($value)
    {
        $value = trim($value);

        if ($value == null
            or
            $this->Validator->isLength('body',
                $value,
                TTi18n::gettext('Incorrect Body length'),
                2, 2048)
        ) {
            $this->data['body'] = $value;

            return false;
        }

        return false;
    }

    public function getKeywords()
    {
        return $this->data['keywords'];
    }

    public function setKeywords($value)
    {
        $value = trim($value);

        if ($value == null
            or
            $this->Validator->isLength('keywords',
                $value,
                TTi18n::gettext('Incorrect Keywords length'),
                2, 1024)
        ) {
            $this->data['keywords'] = $value;

            return false;
        }

        return false;
    }

    public function getPrivate()
    {
        return $this->fromBool($this->data['private']);
    }

    public function setPrivate($bool)
    {
        $this->data['private'] = $this->toBool($bool);

        return true;
    }
}
