<?php
class Model_Task extends Mango {

	protected $_fields = array(
		'request'  => array('type' => 'string', 'required' => TRUE, 'filters' => array(array('serialize'))),
		'active'   => array('type' => 'boolean'),
		'failed'   => array('type' => 'boolean'),
		'error'    => array('type' => 'string'),
		'time'     => array('type' => 'int')
	);

	public function create($safe = TRUE)
	{
		$this->time = time();
		return parent::create($safe);
	}

	public function requeue()
	{
		unset($this->active, $this->failed, $this->error);

		$this->time = time();
		$this->update(array(), TRUE);
	}

	public function get_next()
	{
		$query = array(
			'active' => array('$exists' => FALSE),
			'failed' => array('$exists' => FALSE)
		);

		$values = $this->db()->command( array(
			'findAndModify' => $this->_collection,
			'query'         => $query,
			'sort'          => array('time' => 1),
			'update'        => array('$set' => array('active' => TRUE)),
			'new'           => TRUE
		));

		return $this->values( Arr::get($values,'value', array()), TRUE);
	}
}