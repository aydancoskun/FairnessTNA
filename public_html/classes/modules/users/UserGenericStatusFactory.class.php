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
 * @package Modules\Users
 */
class UserGenericStatusFactory extends Factory
{
    protected static $static_queue = null;
        protected $table = 'user_generic_status'; //PK Sequence name
    protected $pk_sequence_name = 'user_generic_status_id_seq'; //PK Sequence name
protected $batch_sequence_name = 'user_generic_status_batch_id_seq';
    protected $batch_id = null;
    protected $queue = null;

    public static function isStaticQueue()
    {
        if (is_array(self::$static_queue) and count(self::$static_queue) > 0) {
            return true;
        }

        return false;
    }

    public static function getStaticQueue()
    {
        return self::$static_queue;
    }

    public static function queueGenericStatus($label, $status, $description = null, $link = null)
    {
        Debug::Text('Add Generic Status row to queue... Label: ' . $label . ' Status: ' . $status, __FILE__, __LINE__, __METHOD__, 10);
        $arr = array(
            'label' => $label,
            'status' => $status,
            'description' => $description,
            'link' => $link
        );

        self::$static_queue[] = $arr;

        return true;
    }

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'status':
                $retval = array(
                    10 => TTi18n::gettext('Failed'),
                    20 => TTi18n::gettext('Warning'),
                    //25 => TTi18n::gettext('Notice'), //Friendly than a warning.
                    30 => TTi18n::gettext('Success'),
                );
                break;
            case 'columns':
                $retval = array(
                    '-1010-label' => TTi18n::gettext('Label'),
                    '-1020-status' => TTi18n::gettext('Status'),
                    '-1030-description' => TTi18n::gettext('Description'),

                    '-2000-created_by' => TTi18n::gettext('Created By'),
                    '-2010-created_date' => TTi18n::gettext('Created Date'),
                    '-2020-updated_by' => TTi18n::gettext('Updated By'),
                    '-2030-updated_date' => TTi18n::gettext('Updated Date'),
                );
                break;
            case 'list_columns':
                $retval = Misc::arrayIntersectByKey($this->getOptions('default_display_columns'), Misc::trimSortPrefix($this->getOptions('columns')));
                break;
            case 'default_display_columns': //Columns that are displayed by default.
                $retval = array(
                    'label',
                    'status',
                    'description',
                );
                break;

        }

        return $retval;
    }

    public function setUser($id)
    {
        $id = trim($id);

        $ulf = TTnew('UserListFactory');

        if ($this->Validator->isResultSetWithRows('user',
            $ulf->getByID($id),
            TTi18n::gettext('Invalid User')
        )
        ) {
            $this->data['user_id'] = $id;

            return true;
        }

        return false;
    }

    public function getStatus()
    {
        if (isset($this->data['status_id'])) {
            return (int)$this->data['status_id'];
        }

        return false;
    }

    public function setStatus($status)
    {
        $status = trim($status);

        if ($this->Validator->inArrayKey('status',
            $status,
            TTi18n::gettext('Incorrect Status'),
            $this->getOptions('status'))
        ) {
            $this->data['status_id'] = $status;

            return true;
        }

        return false;
    }

    public function getLabel()
    {
        if (isset($this->data['label'])) {
            return $this->data['label'];
        }

        return false;
    }

    public function setLabel($val)
    {
        $val = trim($val);
        if ($this->Validator->isLength('label',
            $val,
            TTi18n::gettext('Invalid label'),
            1, 1024)
        ) {
            $this->data['label'] = $val;

            return true;
        }

        return false;
    }

    public function getDescription()
    {
        if (isset($this->data['description'])) {
            return $this->data['description'];
        }

        return false;
    }

    public function setDescription($val)
    {
        $val = trim($val);
        if ($val == ''
            or
            $this->Validator->isLength('description',
                $val,
                TTi18n::gettext('Invalid description'),
                1, 1024)
        ) {
            $this->data['description'] = $val;

            return true;
        }

        return false;
    }

    public function getLink()
    {
        if (isset($this->data['link'])) {
            return $this->data['link'];
        }

        return false;
    }

    public function setLink($val)
    {
        $val = trim($val);
        if ($val == ''
            or
            $this->Validator->isLength('link',
                $val,
                TTi18n::gettext('Invalid link'),
                1, 1024)
        ) {
            $this->data['link'] = $val;

            return true;
        }

        return false;
    }

    public function setQueue($queue)
    {
        $this->queue = $queue;

        UserGenericStatusFactory::clearStaticQueue();

        return true;
    }

    //Static Queue functions

    public static function clearStaticQueue()
    {
        self::$static_queue = null;

        return true;
    }

    public function saveQueue()
    {
        if (is_array($this->queue)) {
            Debug::Arr($this->queue, 'Generic Status Queue', __FILE__, __LINE__, __METHOD__, 10);
            foreach ($this->queue as $key => $queue_data) {
                $ugsf = TTnew('UserGenericStatusFactory');
                $ugsf->setUser($this->getUser());
                if ($this->getBatchId() > 0) {
                    $ugsf->setBatchID($this->getBatchID());
                } else {
                    $this->setBatchId($this->getNextBatchId());
                }

                $ugsf->setLabel($queue_data['label']);
                $ugsf->setStatus($queue_data['status']);
                $ugsf->setDescription($queue_data['description']);
                $ugsf->setLink($queue_data['link']);

                if ($ugsf->isValid()) {
                    $ugsf->Save();

                    unset($this->queue[$key]);
                }
            }

            return true;
        }

        Debug::Text('Generic Status Queue Empty', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }

        return false;
    }

    public function getBatchID()
    {
        if (isset($this->data['batch_id'])) {
            return (int)$this->data['batch_id'];
        }

        return false;
    }


    //Non-Static Queue functions

    public function setBatchID($val)
    {
        $val = trim($val);
        if ($this->Validator->isNumeric('batch_id',
            $val,
            TTi18n::gettext('Invalid Batch ID'))
        ) {
            $this->data['batch_id'] = $val;

            return true;
        }

        return false;
    }

    public function getNextBatchID()
    {
        $this->batch_id = $this->db->GenID($this->batch_sequence_name);

        return $this->batch_id;
    }

    /*
    function addGenericStatus($label, $status, $description = NULL, $link = NULL ) {
        $this->setLabel( $label );
        $this->setStatus( $status );
        $this->setDescription( $description );
        $this->setLink( $link );

        $batch_id = $this->getBatchId();
        $user_id = $this->getUser();

        if ( $this->isValid() ) {
            $this->Save();

            $this->setBatchId( $batch_id );
            $this->setUser( $user_id );

            return TRUE;
        }

        return FALSE;
    }
    */

    public function preSave()
    {
        return true;
    }
}
