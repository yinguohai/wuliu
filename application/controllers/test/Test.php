<?php
defined('BASEPATH') OR exit('No direct script access allowed');
//短信验证功能
class Test extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->helper('function');
    }
    public function test(){

        $data = array(
            '1' => Array
            (
                'name' =>'超级管理员',
                'id' => 1,
                'pid' => 0,
            ),

            '4' => Array
                (
                    'name' =>'└ 董事长',
                    'id' => 4,
                    'pid' => 1,
                ),

            '14' => Array
                (
                    'name' =>'└ 总经理',
                    'id' => 14,
                    'pid' => 4,
                ),

            '2' => Array
                (
                    'name' =>'├ 运营经理(一三部)',
                    'id' => 2,
                    'pid' => 14,
                ),

            '3' => Array
                (
                    'name' =>'│ ├ 运营一部主管',
                    'id' => 3,
                    'pid' => 2,
                ),

            '5' => Array
                (
                    'name' =>'│ │ ├ 运营一部A组长',
                    'id' => 5,
                    'pid' => 3,
                ),

            '6' => Array
                (
                    'name' =>'│ │ │ └ 运营一部A组员',
                    'id' => 6,
                    'pid' => 5,
                ),

            '10' => Array
                (
                    'name' =>'│ │ ├ 运营一部B组长',
                    'id' => 10,
                    'pid' => 3,
                ),

            '11' => Array
                (
                    'name' =>'│ │ │ └ 运营一部B组员',
                    'id' => 11,
                    'pid' => 10,
                ),
        );
        echo "<pre/>";

        $result=my_sort($data);
        print_r($result);
    }
}