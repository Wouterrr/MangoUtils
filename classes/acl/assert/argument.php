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
			$allow_same = TRUE;

			if ( strpos($role_key, '!') === 0)
			{
				$role_key   = substr($role_key, 1);
				$allow_same = ! $allow_same;
			}

			if ( strpos($resource_key, '!') === 0)
			{
				$resource_key = substr($resource_key, 1);
				$allow_same   = ! $allow_same;
			}

			if ( Mango::normalize($role->$role_key) === Mango::normalize($resource->$resource_key))
			{
				if ( ! $allow_same )
				{
					// fields are the same, not allowed
					return FALSE;
				}
			}
			else if ( $allow_same)
			{
				// fields are different, not allowed
				return FALSE;
			}
		}

		return TRUE;
	}
}