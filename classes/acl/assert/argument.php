<?php

/*
 * Modification of default Acl_Assert_Argument in ACL module in that it
 * Mango::normalizes the arguments before comparing.
 *
 * This allows (for example) comparing _id values
 */

class Acl_Assert_Argument implements Acl_Assert_Interface {

	protected $_arguments;

	public function __construct($arguments)
	{
		$this->_arguments = $arguments;
	}

	public function assert(Acl $acl, $role = null, $resource = null, $privilege = null)
	{
		foreach ( $this->_arguments as $role_key => $resource_key)
		{
			// normalize arguments & compare
			if ( Mango::normalize($role->$role_key) !== Mango::normalize($resource->$resource_key))
			{
				return FALSE;
			}
		}

		return TRUE;
	}
}