<?php defined('SYSPATH') or die('No direct script access.');
/**
 * MangoDB log writer.
 */
class Log_Mango extends Kohana_Log_Writer {

	/*
	 * Collection to write log data to
	 * Make this is a capped collection for better performance
	 * http://www.mongodb.org/display/DOCS/Capped+Collections
	 */
	protected $_collection;

	/*
	 * Name of MangoDB configuration
	 */
	protected $_name;

	/*
	 * MangoDB reference
	 */
	protected $_db;

	/**
	 * Creates a new mangoDB logger.
	 *
	 * @param   string  log directory
	 * @param   string  name of MangoDB configuration
	 * @return  void
	 */
	public function __construct($collection, $name = 'default')
	{
		$this->_collection = $collection;
		$this->_name = $name;
	}

	/**
	 * Writes each of the messages into the collection
	 *
	 * @param   array   messages
	 * @return  void
	 */
	public function write(array $messages)
	{
		if ( $this->_db === NULL)
		{
			$this->_db = MangoDB::instance($this->_name);
		}

		$this->_db->batch_insert($this->_collection,$messages);
	}

} // End Log_Mango