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
 * Mango::factory('lock')->clear_expired();
 *
 */


class Model_Lock extends Mango {

	protected $_fields = array(
		'_id'       => array('type' => 'string', 'required' => TRUE),
		'ends'      => array('type' => 'int', 'required' => TRUE),
		'user_id'   => array('type' => 'MongoId', 'required' => TRUE)
	);

	/**
	 * Try to (update) lock $object by $user for $time seconds
	 *
	 * @param   Mango|string   locked object or unique lock string
	 * @param   Mango          locking user
	 * @param   int            number of seconds before lock should timeout
	 * @return  float|boolean  FALSE when failed, otherwise time (in seconds from epoch) when lock will expire
	 */
	public function get( $lock_key, Mango $user, $time = 600)
	{
		if ( $lock_key instanceof Mango)
		{
			$lock_key = $lock_key->_id;
		}

		$this->values( array(
			'_id' => (string) $lock_key
		))->load();

		$now = time();

		if ( $this->loaded() && (string) $this->user_id !== (string) $user->_id && $now < $this->ends)
		{
			// another user has active lock
			return FALSE;
		}

		$values = array(
			'_id'     => (string) $lock_key,
			'user_id' => $user->_id,
			'ends'    => $now + $time
		);

		if ( ! $this->loaded())
		{
			// create
			try
			{
				$this->values($values)->create();

				return $this->ends;
			}
			catch ( Mango_Exception $e)
			{
				// a lock for this object was just created, this one failed
				return FALSE;
			}
		}
		else
		{
			// update criteria
			$criteria = array(
				'ends' => $this->ends
			);

			// update lock
			$this->values($values)->update($criteria);

			$err = $this->_db->last_error();

			return (bool) $err['updatedExisting']
				? $this->ends
				: FALSE;
		}
	}

	/**
	 * Release a lock for object $object/user $user
	 *
	 * @param   Mango|string  locked object | lock string
	 * @param   Mango         locking user
	 * @param   float         expire time of lock (optional)
	 * @return  void
	 */
	public function release( $lock_key, Mango $user, $ends = NULL)
	{
		if ( $lock_key instanceof Mango)
		{
			$lock_key = $lock_key->_id;
		}

		$criteria = array(
			'_id'     => (string) $lock_key,
			'user_id' => $user->_id
		);

		if ( $ends !== NULL)
		{
			$criteria['ends'] = (float) $ends;
		}

		$this->db()->remove( $this->_collection, $criteria);
	}

	/**
	 * Clear all expired locks
	 */
	public function clear_expired()
	{
		$this->_db->remove( $this->_collection, array('ends' => array('$lt' => round(microtime(true),4))));
	}
}