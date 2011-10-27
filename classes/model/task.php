<?php
class Model_Task extends Mango {

	protected $_fields = array(
		'request'  => array('type' => 'string', 'filters' => array(array('serialize'))),
		'uri'      => array('type' => 'string'),
		'status'   => array('type' => 'enum', 'values' => array('queued', 'active', 'failed', 'completed')),
		'message'  => array('type' => 'string'),
		'create'   => array('type' => 'int'),
		'updated'  => array('type' => 'int')
	);

	public function create($safe = TRUE)
	{
		$this->created = time();

		return parent::create($safe);
	}

	public function update( $criteria = array(), $safe = TRUE)
	{
		$this->updated = time();

		return parent::update($criteria, $safe);
	}

	public function requeue()
	{
		$this->status  = 'queued';
		$this->created = time();
		$this->update();
	}

	public function fail($message)
	{
		$this->message = $message;
		$this->status  = 'failed';
		$this->update();
	}

	public function complete()
	{
		$this->status = 'completed';
		$this->update();
	}

	public function get_next()
	{
		$values = $this->db()->command( array(
			'findAndModify' => $this->_collection,
			'query'         => array(
				'status'    => array_search('queued', $this->_fields['status']['values'])
			),
			'sort'          => array('created' => 1),
			'update'        => array(
				'$set'    => array(
					'updated' => time()
					'status'  => array_search('active', $this->_fields['status']['values'])
				)
			),
			'new'           => TRUE
		));

		return $this->values( Arr::get($values,'value', array()), TRUE);
	}
}