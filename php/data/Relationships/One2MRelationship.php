<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2011 SugarCRM Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by SugarCRM".
 ********************************************************************************/


require_once("data/Relationships/M2MRelationship.php");

/**
 * Represents a one to many relationship that is table based.
 */
class One2MRelationship extends M2MRelationship
{

    public function __construct($def)
    {
        global $dictionary;

        $this->def = $def;
        $this->name = $def['name'];

        $this->selfReferencing = $def['lhs_module'] == $def['rhs_module'];
        $lhsModule = $def['lhs_module'];
        $rhsModule = $def['rhs_module'];

        if ($this->selfReferencing)
        {
            $links = VardefManager::getLinkFieldForRelationship(
                $lhsModule, BeanFactory::getBeanName($lhsModule), $this->name
            );
            if (empty($links))
            {
                $GLOBALS['log']->fatal("No Links found for relationship {$this->name}");
            }
            if (!isset($links[0])) //Only one link for a self referencing relationship, this is BAAAD
                $this->lhsLinkDef = $this->rhsLinkDef = $links;
            else
            {
                if ((!empty($links[0]['side']) && $links[0]['side'] == "right")
                        || (!empty($links[0]['link_type']) && $links[0]['link_type'] == "one"))
                {
                    //$links[0] is the RHS
                    $this->lhsLinkDef = $links[1];
                    $this->rhsLinkDef = $links[0];
                } else
                {
                    //$links[0] is the LHS
                    $this->lhsLinkDef = $links[0];
                    $this->rhsLinkDef = $links[1];
                }
            }
        } else
        {
            $this->lhsLinkDef = VardefManager::getLinkFieldForRelationship(
                $lhsModule, BeanFactory::getBeanName($lhsModule), $this->name
            );
            $this->rhsLinkDef = VardefManager::getLinkFieldForRelationship(
                $rhsModule, BeanFactory::getBeanName($rhsModule), $this->name
            );
            if (!isset($this->lhsLinkDef['name']) && isset($this->lhsLinkDef[0]))
            {
              $this->lhsLinkDef = $this->lhsLinkDef[0];
            }
            if (!isset($this->rhsLinkDef['name']) && isset($this->rhsLinkDef[0])) {
                $this->rhsLinkDef = $this->rhsLinkDef[0];
            }
        }
        $this->lhsLink = $this->lhsLinkDef['name'];
        $this->rhsLink = $this->rhsLinkDef['name'];
    }

    /**
     * @param  $lhs SugarBean left side bean to add to the relationship.
     * @param  $rhs SugarBean right side bean to add to the relationship.
     * @param  $additionalFields key=>value pairs of fields to save on the relationship
     * @return boolean true if successful
     */
    public function add($lhs, $rhs, $additionalFields = array())
    {
        $dataToInsert = $this->getRowToInsert($lhs, $rhs, $additionalFields);
        //If the current data matches the existing data, don't do anything
        if (!$this->checkExisting($dataToInsert))
        {
            $rhsLinkName = $this->rhsLink;
            //In a one to many, any existing links from the many (right) side must be removed first
            $rhs->load_relationship($rhsLinkName);
            $this->removeAll($rhs->$rhsLinkName);
            parent::add($lhs, $rhs, $additionalFields);
        }
    }
}