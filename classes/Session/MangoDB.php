<?php defined('SYSPATH') or die('No direct script access.');

/**
 * MongoDB Session Driver
 *
 * @author Javier Aranda <internet@javierav.com>
 * @package Kohana
 * @category Session
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero GPL 3
 *
 * Modified by Wouter
 * http://github.com/Wouterrr
 */

class Session_MangoDB extends Session
{
	/**
	 * @var garbage collection requests
	 */
	protected $_gc = 500;
	
	/**
	 * @var string the current session
	 */
	protected $_session;

	/**
	 * Constructor
	 */
	public function __construct(array $config = NULL, $id = NULL)
	{
		// Load aditional config

		if ( isset($config['gc']) )
		{
			$this->_gc = (int) $config['gc'];
		}

		parent::__construct($config, $id);

		if ( mt_rand(0, $this->_gc) === $this->_gc )
		{
			// Collect
			$this->_gc();
		}
	}

    /**
     * Read session data
     *
     * @param integer $id
     */
    protected function _read($id = NULL)
    {
        if ( $id OR $id = Cookie::get($this->_name))
        {
            $id = explode('.', $id);

            if ( count($id) === 2)
            {
                try {
                    $session = Mango::factory('session', array(
                        '_id'   => $id[0],
                        'token' => $id[1]
                    ));

                    $session->load();

                    if ( $session->loaded() )
                    {
                        $this->_session = $session;

                        return $this->_session->contents;
                    }
                } catch ( MongoException $e) {
                    Kohana::$log->add(Log::ERROR, 'Unable to load session :id :token (:error)', array(
                        ':id' => $id[0],
                        ':token' => $id[1],
                        ':error' => $e->getMessage(),
                    ));
                }
            }
        }

        return NULL;
    }

    /**
     * Restarts the current session.
     *
     * @return  boolean
     */
    protected function _restart()
    {
        // nothing here as the token is regenerated no matter what
    }

  /**
   * Create new session
   */
	protected function _regenerate()
	{
		// nothing here as the token is regenerated no matter what
	}

  /**
   * Write session data
   */
    /**
     * Write session data
     */
    protected function _write()
    {
        if ( $this->_session === NULL)
        {
            $this->_session = Mango::factory('session');
        }

        $this->_session->values( array(
            'last_active' => $this->_data['last_active'],
            'contents'    => (string) $this,
            'token'       => new MongoId // regenerate against session fixation attacks
        ));

        try {
            $this->_session->loaded()
                ? $this->_session->update()
                : $this->_session->create();

            // Update cookie
            Cookie::set($this->_name, $this->_session->_id . '.' . $this->_session->token, $this->_lifetime);
        } catch (MongoException $e) {
            Kohana::$log->add(Log::ERROR, 'Unable to write session :token (:error)', array(
                ':token' => $this->_session->token,
                ':error' => $e->getMessage(),
            ));
        }

        return TRUE;
    }

  /**
   * Delete session
   */
  protected function _destroy()
  {
		if ( $this->_session !== NULL && $this->_session->loaded())
		{
			$this->_session->delete();
			$this->_session = NULL;

			Cookie::delete($this->_name);
		}

		return TRUE;
	}

  /**
   * Garbage Collector to delete old sessions
   */
  protected function _gc()
  {
    if ( $this->_lifetime)
    {
      $expires = $this->_lifetime;
    }
    else
    {
      $expires = Date::MONTH;
    }

    // Delete old sessions
    Mango::factory('session')->db()->remove('sessions', array('last_active' => array('$lt' => time() - $expires)));
  }
}