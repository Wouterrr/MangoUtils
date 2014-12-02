<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Data model for storage Sessions in MongoDB
 *
 * @author Javier Aranda <internet@javierav.com>
 * @package Kohana
 * @category Session
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero GPL 3
 *
 * Modified by Wouter
 * http://github.com/Wouterrr
 */

class Model_Session extends Mango
{
  protected $_fields = array(
      'token'       => array('type' => 'MongoId', 'required' => true),
      'last_active' => array('type' => 'int', 'required' => true),
      'contents'    => array('type' => 'string')
  );
}