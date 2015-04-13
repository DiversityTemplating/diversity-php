<?php

error_reporting(E_ALL | E_STRICT);
if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

require_once(
  dirname(__DIR__) . DS . 'vendor' . DS . 'autoload.php'
);

//TEST CONSTANTS
define('FIXTURES', __DIR__ . DS . 'fixtures' . DS);
