<?php
class Model_Queue_Task extends Mango {

	protected $_fields = array(
		'request'  => array('type' => 'string', 'filters' => array(array('serialize'))),
		'uri'      => array('type' => 'string'),
		'status'   => array('type' => 'enum', 'values' => array('queued', 'active', 'failed', 'completed')),
		'message'  => array('type' => 'string'),
		'created'  => array('type' => 'int'),
		'updated'  => array('type' => 'int'),
		'response' => array('type' => 'string')
	);

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
					'updated' => time(),
					'status'  => array_search('active', $this->_fields['status']['values'])
				)
			),
			'new'           => TRUE
		));

		return $this->values( Arr::get($values,'value', array()), TRUE);
	}

	public function create($safe = TRUE)
	{
		$this->status  = 'queued';
		$this->created = time();

		return parent::create($safe);
	}

	public function update( $criteria = array(), $safe = TRUE)
	{
		$this->updated = time();

		return parent::update($criteria, $safe);
	}

	public function valid()
	{
		try
		{
			return $this->request() instanceof Request;
		}
		catch ( Exception $e)
		{
			// error during unserialization
			return FALSE;
		}
	}

	public function request()
	{
		return isset($this->uri)
			? Request::factory($this->uri)
			: unserialize($this->request);
	}

	public function execute($max_tries = 1)
	{
		$request = $this->request();

		// execute request
		for ( $i = 0; $i < $max_tries; $i++)
		{
			$error = NULL;

			try
			{
				// execute task
				$response = $request->execute();

				// store response in task
				$this->response = $response->render();

				// analyse response
				if ( $response->status() > 199 && $response->status() < 300)
				{
					// task completed
					break;
				}
				else
				{
					// server error
					$error = strtr("Invalid response status (:status) while executing :uri", array(
						':uri'      => $request->uri(),
						':status'   => $response->status(),
					));
				}
			}
			catch ( Exception $e)
			{
				// request error
				$error = strtr("Unable to execute task: :uri, (:msg)", array(
					':uri'     => $request->uri(),
					':msg'     => $e->getMessage(),
				));
			}
		}

		// update status
		$this->status  = isset($error) ? 'failed' : 'completed';
		$this->message = isset($error) ? $error   : NULL;

		return $this->status === 'completed';
	}

	public function as_array( $clean = TRUE )
	{
		$array = parent::as_array($clean);

		if ( ! $clean)
		{
			$array['valid'] = $this->valid();

			if ( $array['valid'] && ! isset($array['uri']))
			{
				$array['uri'] = $this->request()->uri();
			}
		}

		return $array;
	}
}