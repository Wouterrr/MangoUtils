<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Sessions Config
 *
 * @author Javier Aranda <internet@javierav.com>
 * @package Kohana
 * @category Session
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero GPL 3
 */

return array(
    'mangoDB' => array(
        'name'          => 'my_session',
        'encrypted'     => FALSE,
        'lifetime'      => 3600,
        'gc'            => 500,
    ),
);