<?php
/*
 *  This file is part of PHPLibs <http://phplibs.kmfa.net/>.
 *  
 *  Copyright (c) 2010 < squarious at gmail dot com > .
 *  
 *  PHPLibs is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  PHPLibs is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with PHPLibs.  If not, see <http://www.gnu.org/licenses/>.
 *  
 */


require_once dirname(__FILE__) . '/ACE.class.php';

//! Implementation of an Access Control List
class Authz_ACL
{

    //! The array with all the Authz_ACE of this ACL.
    private $aces = array();

    //! Add a new entry in list to allow access for a tuple.
    /**
     * @param $role The name of the role or @b null for any role.
     * @param $action The action this entry refers to.
     */
    public function allow($role, $action)
    {
        $ace = new Authz_ACE($role, $action, true);
        $this->aces[$ace->get_dn_hash()] = $ace;
    }
    
    //! Add a new entry in list to deny access for a tuple.
    /**
     * @param $role The name of the role or @b null for any role.
     * @param $action The action this entry refers to.
     */
    public function deny($role, $action)
    {
        $ace = new Authz_ACE($role, $action, false);
        $this->aces[$ace->get_dn_hash()] = $ace;
    }
    
    //! Remove an entry from this list.
    /**
     * @param $role The name of the role as it was given through allow() or deny().
     * @param $action The name of the action as it was given through allow() or deny().
     * @return
     *  - @b true If the ACE was removed.
     *  - @b false If the ACE was not found.
     */
    public function remove_ace($role, $action)
    {
        $ace = new Authz_ACE($role, $action, false);
        if (!isset($this->aces[$ace->get_dn_hash()]))
            return false;
            
        unset($this->aces[$ace->get_dn_hash()]);
        return true;
    }
    
    //! Check if this list is emptry
    public function is_empty()
    {
        return empty($this->aces);
    }
    
    //! Get all the Authz_ACE of this list
    public function get_aces()
    {
        return $this->aces;
    }
        
    //! Get the effective Authz_ACE for the tuple role-action.
    /**
     * Traverse this list and find the most effective ACE for
     * the given tuple.
     * @return
     *  - @b Authz_ACE If the effective ACE was found.
     *  - @b null If no ACE was found for this tuple.
     *  .
     */
    public function effective_ace($role, $action)
    {
        $effective_ace = null;

        foreach($this->aces as $ace)
        {
            if ($ace->get_action() !== $action)
                continue;

            if ($ace->get_role() == $role)
                $effective_ace = $ace;
            
            if ((!$effective_ace) || $effective_ace->is_role_null())
                if ($ace->is_role_null())
                    $effective_ace = $ace;
        }
        
        return $effective_ace;
    }
}

?>
