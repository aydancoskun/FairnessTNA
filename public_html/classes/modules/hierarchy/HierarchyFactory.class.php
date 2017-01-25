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
 * @package Modules\Hierarchy
 */
class HierarchyFactory extends Factory
{
    protected $table = 'hierarchy'; //Used for caching purposes only.

    protected $fasttree_obj = null;

    //protected $tmp_data = array(); //Tmp data.

    public function setId($id)
    {
        $this->data['id'] = $id;

        return true;
    }

    public function setHierarchyControl($id)
    {
        $this->data['hierarchy_control_id'] = $id;

        return true;
    }

    public function setPreviousUser($id)
    {
        $this->data['previous_user_id'] = $id;

        return true;
    }

    public function setParent($id)
    {
        $this->data['parent_user_id'] = $id;

        return true;
    }

    public function setUser($id)
    {
        $this->data['user_id'] = $id;

        return true;
    }

    //Use this for completly editing a row in the tree
    //Basically "old_id".

    public function getShared()
    {
        if (isset($this->data['shared'])) {
            return $this->fromBool($this->data['shared']);
        }

        return false;
    }

    public function setShared($bool)
    {
        $this->data['shared'] = $this->toBool($bool);

        return true;
    }

    public function Validate($ignore_warning = true)
    {
        if ($this->getUser() == $this->getParent()) {
            $this->Validator->isTrue('parent',
                false,
                TTi18n::gettext('User is the same as parent')
            );
        }

        //Make sure both user and parent belong to the same company
        $ulf = TTnew('UserListFactory');
        $ulf->getById($this->getUser());
        $user = $ulf->getIterator()->current();
        unset($ulf);

        $ulf = TTnew('UserListFactory');
        $ulf->getById($this->getParent());
        $parent = $ulf->getIterator()->current();
        unset($ulf);


        if ($this->getUser() == 0 and $this->getParent() == 0) {
            $parent_company_id = 0;
            $user_company_id = 0;
        } elseif ($this->getUser() == 0) {
            $parent_company_id = $parent->getCompany();
            $user_company_id = $parent->getCompany();
        } elseif ($this->getParent() == 0) {
            $parent_company_id = $user->getCompany();
            $user_company_id = $user->getCompany();
        } else {
            $parent_company_id = $parent->getCompany();
            $user_company_id = $user->getCompany();
        }

        if ($user_company_id > 0 and $parent_company_id > 0) {
            Debug::Text(' User Company: ' . $user_company_id . ' Parent Company: ' . $parent_company_id, __FILE__, __LINE__, __METHOD__, 10);
            if ($user_company_id != $parent_company_id) {
                $this->Validator->isTrue('parent',
                    false,
                    TTi18n::gettext('User or parent has incorrect company')
                );
            }

            $this->getFastTreeObject()->setTree($this->getHierarchyControl());
            $children_arr = $this->getFastTreeObject()->getAllChildren($this->getUser(), 'RECURSE');
            if (is_array($children_arr)) {
                $children_ids = array_keys($children_arr);

                if (isset($children_ids) and is_array($children_ids) and in_array($this->getParent(), $children_ids) == true) {
                    Debug::Text(' Objects cant be re-parented to their own children...', __FILE__, __LINE__, __METHOD__, 10);
                    $this->Validator->isTrue('parent',
                        false,
                        TTi18n::gettext('Unable to change parent to a child of itself')
                    );
                }
            }
        }

        return true;
    }

    public function getUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }

        return false;
    }

    public function getParent()
    {
        if (isset($this->data['parent_user_id'])) {
            return (int)$this->data['parent_user_id'];
        }

        return false;
    }

    public function getFastTreeObject()
    {
        if (is_object($this->fasttree_obj)) {
            return $this->fasttree_obj;
        } else {
            global $fast_tree_options;
            $this->fasttree_obj = new FastTree($fast_tree_options);

            return $this->fasttree_obj;
        }
    }

    public function getHierarchyControl()
    {
        if (isset($this->data['hierarchy_control_id'])) {
            return (int)$this->data['hierarchy_control_id'];
        }

        return false;
    }

    public function Save($reset_data = true, $force_lookup = false)
    {
        $this->StartTransaction();

        $this->getFastTreeObject()->setTree($this->getHierarchyControl());

        $retval = true;
        if ($this->getId() === false) {
            Debug::Text(' Adding Node ', __FILE__, __LINE__, __METHOD__, 10);
            $log_action = 10;

            //Add node to tree
            if ($this->getFastTreeObject()->add($this->getUser(), $this->getParent()) === false) {
                Debug::Text(' Failed adding Node ', __FILE__, __LINE__, __METHOD__, 10);

                $this->Validator->isTrue('user',
                    false,
                    TTi18n::gettext('Employee is already assigned to this hierarchy')
                );
                $retval = false;
            }
        } else {
            Debug::Text(' Editing Node ', __FILE__, __LINE__, __METHOD__, 10);
            $log_action = 20;

            //Edit node.
            if ($this->getFastTreeObject()->edit($this->getPreviousUser(), $this->getUser()) === true) {
                $retval = $this->getFastTreeObject()->move($this->getUser(), $this->getParent());
            } else {
                Debug::Text(' Failed editing Node ', __FILE__, __LINE__, __METHOD__, 10);

                //$retval = FALSE;
                $retval = true;
            }
        }

        TTLog::addEntry($this->getUser(), $log_action, TTi18n::getText('Hierarchy Tree - Control ID') . ': ' . $this->getHierarchyControl(), null, $this->getTable());

        $this->CommitTransaction();
        //$this->FailTransaction();

        $cache_id = $this->getHierarchyControl() . $this->getParent();
        $this->removeCache($cache_id);

        return $retval;
    }

    public function getId()
    {
        if (isset($this->data['id'])) {
            return $this->data['id'];
        }

        return false;
    }

    public function getPreviousUser()
    {
        if (isset($this->data['previous_user_id'])) {
            return (int)$this->data['previous_user_id'];
        }

        return false;
    }

    public function Delete()
    {
        if ($this->getUser() !== false) {
            return true;
        }

        return false;
    }

    //This table doesn't have any of these columns, so overload the functions.
    public function getDeleted()
    {
        return false;
    }

    public function setDeleted($bool)
    {
        return false;
    }

    public function getCreatedDate()
    {
        return false;
    }

    public function setCreatedDate($epoch = null)
    {
        return false;
    }

    public function getCreatedBy()
    {
        return false;
    }

    public function setCreatedBy($id = null)
    {
        return false;
    }

    public function getUpdatedDate()
    {
        return false;
    }

    public function setUpdatedDate($epoch = null)
    {
        return false;
    }

    public function getUpdatedBy()
    {
        return false;
    }

    public function setUpdatedBy($id = null)
    {
        return false;
    }


    public function getDeletedDate()
    {
        return false;
    }

    public function setDeletedDate($epoch = null)
    {
        return false;
    }

    public function getDeletedBy()
    {
        return false;
    }

    public function setDeletedBy($id = null)
    {
        return false;
    }
}
