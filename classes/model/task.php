<?php
class Model_Task extends Mango {

	protected $_fields = array(
		'route'  => array('type' => 'string','default' => 'default'),
		'uri'    => array('type' => 'array'),
		'e'      => array('type' => 'boolean')
	);
}