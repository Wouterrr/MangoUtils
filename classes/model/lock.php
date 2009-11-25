<?php
/**
 * Simple locking mechanism for MongoDB and the Mango library
 *
 * Get a lock for object $object by user $user:

		if ( Mango::factory('lock')->get($object,$user))
		{
			// $user owns lock

			// do something

			// release lock (or let it expire)
			Mango::factory('lock')->release($object,$user);
		}
		else
		{
			// unable to get lock, someone else has it
		}

 * If you don't actively release your locks, the collection will fill up
 * with expired locks that were never actively deleted. To remove those run:
 *
 * Mango::factory('surv')->clear_expired();
 *
 */


class Model_Lock extends Mango {

	protected $_fields = array(
		'ends'      => array('type'=>'int','required' => TRUE),
		'user_id'   => array('type'=>'MongoId','required' => TRUE),
	);

	/**
	 * Try to (update) lock $object by $user for $time seconds
	 *
	 * @param   Mango    locked object
	 * @param   Mango    locking user
	 * @param   int      number of seconds before lock should timeout
	 * @return  boolean  lock retrieved
	 */
	public function get( Mango $object, Mango $user, $time = 600)
	{
		$this->values(array(
			'_id' => $object->_id
		))->load();

		if ( $this->loaded() && (string) $this->user_id !== (string) $user->_id && time() < $this->ends)
		{
			// another user has active lock
			return FALSE;
		}

		$values = array(
			'_id'     => $object->_id,
			'user_id' => $user->_id,
			'ends'    => time() + $time
		);

		if ( ! $this->loaded())
		{
			// create lock
			try
			{
				$this->values($values)->create();
			}
			catch ( Mango_Exception $e)
			{
				// a lock for this object was just created, this one failed
				return FALSE;
			}

			return TRUE;
		}
		else
		{
			// update lock
			$ends = $this->ends;

			// load values
			$this->values($values);

			if ( $this->changed(TRUE) === array())
			{
				// no changes (update same lock in same second)
				return TRUE;
			}
			else
			{
				$this->update(array(
					'ends' => $ends
				));
	
				$err = $this->_db->last_error();

				return (bool) $err['updatedExisting'];
			}
		}
	}

	/**
	 * Release a lock for object $object/user $user
	 *
	 * @param   Mango    locked object
	 * @param   Mango    locking user
	 * @return  void
	 */
	public function release( Mango $object, Mango $user)
	{
		$this->_db->remove( $this->_collection, array(
			'_id'     => $object->_id,
			'user_id' => $user->_id
		));
	}

	/**
	 * Clear all expired locks
	 */
	public function clear_expired()
	{
		$this->_db->remove( $this->_collection, array('ends' => array('$lt' => time())));
	}
}