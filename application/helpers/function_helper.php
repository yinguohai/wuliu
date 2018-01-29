<?php
function my_sort($node,$pid=0){
    $arr=array();
    foreach ($node as $k=>$v){
        if($v['pid']!=$pid)continue;
        unset($node[$k]);
        $v['children']=my_sort($node,$v['id']);
        $arr[]=$v;
    }
    return $arr;
}

function p($arr,$type=1){
    echo '<pre/>';

    if($type ==1){
        print_r($arr);
    }else{
        var_dump($arr);
    }
}

/**
 * @param $str
 * @param $type  HEIMAO  ---黑猫物流   ，   JIALI  ---  嘉里物流
 * @return array|bool   切割后的字符串
 */
function split_html($str,$type){
    if(empty($str) or empty($type)) return false;
    switch ($type){
        case JIALI:
            $firstpos= strpos($str,'<p');
            $tmp=substr($str,$firstpos,380);
            //不需要进一步处理
            return explode('   ',trim(strip_tags( preg_replace('/[\s|'.PHP_EOL.']+/',' ',$tmp))));
        case HEIMAO:
            $firstpos=strpos($str,'<table');
            $endstpos=strpos($str,'</table');
            if( ($errpos=strpos($str,'非有效單號')) !==FALSE ){
                //找出非法的订单号
                $result['errnum']=preg_replace('/[\D]+/','', substr($str,$errpos-20,20));
                return $result;
            }elseif(strpos($str,'NotFound')!==false){
                //无状态
                $errstart=strpos($str,'yellow');
                //分析页面可知需要往后添加10个字符串才能获取<span>标签
                $errinfo=substr($str,$errstart+10);
                $errnum=mb_substr($errinfo,0,mb_strpos($errinfo,'</span>'));
                $result['errnum']= explode('**',trim(strip_tags(str_replace('<br>','**',trim($errnum))),'**'));
                return $result;
            }

            $tmp=substr($str,$firstpos+810,$endstpos-$firstpos-810);
            break;
        case SUNF:
            //顺丰快递
            $tmp=json_decode($str,true);
            if(!empty($tmp['data'][0])){
                unset($tmp['data'][0]['time']);
                unset($tmp['data'][0]['location']);
                $tmp['data'][0]['nu']=$tmp['nu'];
                return $tmp['data'][0];
            }else{
                return FALSE ;
            }
        case SLD:
            $start_sign='alert-danger';
            $end_sign='</div>';
            $firstpos=strpos($str,$start_sign)+strlen($start_sign)+2;
            $endstpos=strpos($str,$end_sign,$firstpos);
            $tmp=substr($str,$firstpos,$endstpos-$firstpos);
            break;
        case SH:
            $start_sign='<tr align="center">';
            $end_sign='</tr>';
            $firstpos=mb_strpos($str,$start_sign);
            $html=mb_substr($str,$firstpos);
            $length=mb_strpos($html,$end_sign);
            $html=mb_substr($html,0,$length);
            $result=explode('###' ,preg_replace('/[\s|'.PHP_EOL.']{2,}/','###',trim(strip_tags($html))));
            if(strpos($result[0],'$')!==false){
                //查找失败
                return false;
            }
            array_shift($result);
            return $result;
            break;
        case RIBEN:
            $html=tags_content($str,'table',1);
            $html=preg_replace('/<td[a-z|\s|\d|=|_|"]*>/','###',$html);

            $result=trim(preg_replace('/[\s|'.PHP_EOL.']{2,}/','',strip_tags($html,'<br>')),'@@');
//            $result=preg_replace('/@@<br>@@/',' ',$result);
            $check=explode('###',$result);
            array_shift($check);
            $ok=$err=[];
            //检查异常情况
            foreach($check as $k  => $v){
                if(strpos($v,'＊＊')!==false){
                    array_push($err, $check[$k-1]);
                    //删除正常数据中的异常数据，默认是最后一个元素
                    array_pop($ok);
                }else{
                    //保存正常数据
                    array_push($ok,$v);
                }
            }
            $tmp=array_chunk($ok,7);
            return ['ok'=>$tmp,'err'=>$err];
            break;
    }
    //如果是黑猫，则需要进一步处理
    if($type==HEIMAO){
        $tmpresult= trim(preg_replace('/[\s|'.PHP_EOL.']{2,}/', '###' ,preg_replace('/(\d{7,})/','***'.'$1',strip_tags($tmp))),'###***');
        $tmpresult=explode('***',$tmpresult);
        $result=array();
        foreach($tmpresult as $v){
            if(strpos($v,'&nbsp;')!==false) $v=str_replace('&nbsp;','**',$v);
            array_push($result,explode('###',trim($v,'###')));
        }
        return $result;
    }elseif($type==SLD){
        preg_match('/[\d]+/',$tmp,$no);
        if(!isset($no[0]) || empty($no)) return false;
        $tmpresult=trim(strip_tags( preg_replace('/[\s|'.PHP_EOL.']+/',' ',$tmp)));
        $tmpresult = mb_substr($tmpresult,mb_strrpos($tmpresult,'-')-7);

        $result=explode('·',$tmpresult);
        array_unshift($result,$no[0]);
        return $result;
    }
}
/**
 * 查询参数需要变换格式
 * @$str  参数 字符串
 * @$type
 */
function query_param($str,$type=''){
    if(empty($str)) return false;
    $str=trim($str);
    switch($type){
        case 1:
                return '(\''.$str.'\')';
        default :
            return $str;
    }
}

/**
 * 获取指定页面的指定标签内容
 * @param $html  页面源代码
 * @param $tag   查找的目标标签，eg:  tr
 * @param $num   指定标签的指定个数
 */
function tags_content($html,$tag,$num){
    if(mb_strpos($tag,'<')!==false) return false;
    //目标标签的起始标签位置
    $tag_start_index=0;
    //目标标签起始标签
    $tag_start='<'.$tag;
    //目标标签闭合标签
    $tag_end='</'.$tag.'>';
    //目标标签起始标签位置查找
    for($i=1;$i<=$num;$i++){
        $firstpos=mb_strpos($html,$tag_start,$tag_start_index+strlen($tag_start));
        //如果没有找到，则退出
        if($firstpos===FALSE && $num==1) break;
        $html=mb_substr($html,$firstpos);
    }
    //目标标签结束位置查找
    $tag_end_index=mb_strpos($html,$tag_end);
    return mb_substr($html,0,$tag_end_index+mb_strlen($tag_end));
}