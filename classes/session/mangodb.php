<?php defined('SYSPATH') or die('No direct script access.');

/**
 * MongoDB Session Driver
 *
 * @author Javier Aranda <internet@javierav.com>
 * @package Kohana
 * @category Session
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero GPL 3
 */

class Session_MangoDB extends Session
{
  /**
   * @var garbage collection requests
   */
  protected $_gc = 500;

  /**
   * @var string the current session id
   */
  protected $_session_id;

  /**
   * @var string id the old session id
   */
  protected $_update_id;

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
    if ($id OR $id = Cookie::get($this->_name))
    {
      $session = Mango::factory('session', array('session_id' => $id))->load();

      if ( $session->loaded() )
      {
        $this->_session_id = $this->_update_id = $id;

        $data = $session->as_array();

        return $data['contents'];
      }
    }

    // Create new session id
    $this->_regenerate();

    return NULL;
  }

  /**
   * Create new session
   */
  protected function _regenerate()
  {
    do
    {
      // generate new identifier
      $id = str_replace('.', '-', uniqid(NULL, TRUE));

      $session = Mango::factory('session', array('session_id' => $id))->load();
    }
    while($session->loaded());

    return $this->_session_id = $id;
  }

  /**
   * Write session data
   */
  protected function _write()
  {
    if ($this->_update_id === NULL)
    {
      // New session
      $data = array(
          'session_id' => $this->_session_id,
          'last_active' => $this->_data['last_active'],
          'contents' => $this->__toString()
      );

      Mango::factory('session', $data)->create();
    }
    else
    {
      // Update
      $session = Mango::factory('session', array('session_id' => $this->_update_id))->load();

      $session->last_active = $this->_data['last_active'];
      $session->contents = $this->__toString();

      if ($this->_update_id !== $this->_session_id)
      {
        $session->session_id = $this->_session_id;
      }

      $session->update();
    }

    $this->_update_id = $this->_session_id;

    // Update cookie
    Cookie::set($this->_name, $this->_session_id, $this->_lifetime);

    return TRUE;
  }

  /**
   * Delete session
   */
  protected function _destroy()
  {
    if ($this->_update_id === NULL)
    {
      return TRUE;
    }

    try
    {
      // Delete actual session
      Mango::factory('session', array('session_id' => $this->_update_id))->delete();

      // Delete cookie
      Cookie::delete($this->_name);
    }
    catch (Exception $e)
    {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Garbage Collector to delete old sessions
   */
  protected function _gc()
  {
    if ($this->_lifetime)
    {
      $expires = $this->_lifetime;
    }
    else
    {
      $expires = Date::MONTH;
    }

    // Delete old sessions
    $sessions = Mango::factory('session')->load(false, null, null, array(), array('last_active' => array('$lt' => time() - $expires)));

    foreach($sessions as $session)
    {
      $session->delete();
    }
  }
}