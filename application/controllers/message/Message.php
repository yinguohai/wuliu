<?php
defined('BASEPATH') OR exit('No direct script access allowed');
//短信验证功能
class Message  extends CI_Controller {
    function __construct()
    {
        parent::__construct();
    }

    public function send(){
        echo '222';die();
        echo "<pre/>";
        var_dump(debug_backtrace());
    }
}
