<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] ='';
$route['Message/(:any)'] ='message/Message/$1';
$route['Test/(:any)'] ='test/Test/$1';