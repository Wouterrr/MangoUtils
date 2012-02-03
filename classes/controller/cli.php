<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Extend this controller to make controllers accessible by CLI only
 */

abstract class Controller_CLI extends Controller {

	public function before()
	{
		parent::before();

		if ( Kohana::$environment !== Kohana::DEVELOPMENT && ! Kohana::$is_cli)
		{
			// Fake 404 error
			throw new HTTP_Exception_404('Unable to find a route to match the URI: :uri', array(
				':uri' => $this->request->uri(),
			));
		}
	}
}