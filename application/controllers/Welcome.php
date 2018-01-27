<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {
    //国际物流
    private $url='http://www.1express.net/getPodInfoByWaybillNum.html';
    //黑猫物流
    private $heurl='http://www.t-cat.com.tw/Inquire/trace.aspx';
    //嘉里物流
    private $jlurl='http://hk.kerryexpress.com/track?track=';
    //快递100
    private $kd100='http://www.kuaidi100.com/query?type=';
    //速利达
    private $sld='http://www.sldex.com/index_DetailSearchResult.php?ship_no=';
    //森鸿
    private $sh='http://211.102.91.188:8019/query.aspx';
    //日本邮局
    private $riben='https://trackings.post.japanpost.jp/services/srv/search?';
    //批量分割数组的长度，默认40个
    private $arr_splice=10;
    public function __construct(){
        parent::__construct();
        $this->load->helpers('function');
    }
    public function index(){
        $this->load->view('index.html');
    }

    /**
     * 国际查询
     * @param $num
     * @return bool
     */
    private function handle_url($num){
        $handle=curl_init();
        curl_setopt($handle,CURLOPT_URL,$this->url);
        curl_setopt($handle,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($handle,CURLOPT_POST,1);
        curl_setopt($handle,CURLOPT_CONNECTTIMEOUT,10);
        curl_setopt($handle,CURLOPT_TIMEOUT,20);
        curl_setopt($handle,CURLOPT_POSTFIELDS,array('waybillNum'=>$num));
        $output=curl_exec($handle);
        curl_close($handle);
        if($output===FALSE){
            return false;
        }
        $result=json_decode($output,true);
        if(empty($result)) return false;
        return $result[0];
    }
    /**
     * curl  post  方式查询
     * @param $num
     * @return array|bool
     */
    private function curl_post($arr,$type='sh'){
        if($type=='sh') $url=$this->sh;
        if(empty($arr)) return [];
        //初始化创建一个curl_multi句柄
        $mh=curl_multi_init();
        //设置活动连接数，防止清楚掉已存在的连接数
        curl_multi_setopt($mh,CURLMOPT_MAXCONNECTS,10);
        $handles=array();
        $results['ok']=array();
        $results['err']=array();
        $readinfo=array();
        $curlerr=array();
        $options=array(
            CURLOPT_RETURNTRANSFER=>TRUE,
            CURLOPT_HEADER=>FALSE,
            CURLOPT_TIMEOUT=>20,
            CURLOPT_POST=>TRUE,
            CURLOPT_REFERER=>$this->sh,
            CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36',
        );
        foreach($arr as $ck => $cv){
            $tmpcm=curl_init($this->sh);
            array_push($handles,$tmpcm);
            curl_setopt_array($tmpcm,$options);
            curl_setopt($tmpcm,CURLOPT_POSTFIELDS,array(
                '__VIEWSTATE'=>'/wEPDwUKMTg2MTYyMDc5M2RkJ9KJGhh8uhyhbJTlvKsHNKphQ+0=',
                '__EVENTVALIDATION'=>'/wEWAwKV4/WjBAKvgcuQCQKM54rGBmM4dpSXavZzZP0mtF+DL1no+d54',
                'TrackNum'=>$cv,
                'Button1'=>'追踪'
            ));
            curl_multi_add_handle($mh,$tmpcm);
        }

        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            $selectinfo=curl_multi_select($mh);
            if ($selectinfo == -1) {
                usleep(1);
            }
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }
        foreach($handles as $kk => $vv){
            if($type=='sh'){
                $tmpcontent=curl_multi_getcontent($handles[$kk]);
                $content=split_html($tmpcontent,SH);
            }
            $tmp=curl_multi_info_read($mh);
            if($tmp['result'] > 0 || count($content)==1 || $content==FALSE){
                array_push($results['err'],$arr[$kk]);
            }else{
                array_push($results['ok'],$content);
            }
        }
        foreach ($handles as $rk => $rv){
            curl_multi_remove_handle($mh,$handles[$rk]);
            curl_close($handles[$rk]);
        }
        curl_multi_close($mh);
        return $results;
    }

    private function curl_post_test(){
        $mch=curl_multi_init();
        $ch=curl_init();
        $options=array(
            CURLOPT_URL=>$this->sh,
            CURLOPT_RETURNTRANSFER=>TRUE,
            CURLOPT_HEADER=>FALSE,
            CURLOPT_TIMEOUT=>20,
            CURLOPT_POST=>TRUE,
            CURLOPT_REFERER=>$this->sh,
            CURLOPT_COOKIE=>'',
            CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36',
        );
        curl_setopt_array($ch,$options);
        curl_setopt($ch,CURLOPT_POSTFIELDS,array(
            '__VIEWSTATE'=>'/wEPDwUKMTg2MTYyMDc5M2RkJ9KJGhh8uhyhbJTlvKsHNKphQ+0=',
            '__EVENTVALIDATION'=>'/wEWAwKV4/WjBAKvgcuQCQKM54rGBmM4dpSXavZzZP0mtF+DL1no+d54',
            'TrackNum'=>'5145178812',
            'Button1'=>'追踪'
        ));
        $ch1=curl_init();
        curl_setopt_array($ch1,$options);
        curl_setopt($ch1,CURLOPT_POSTFIELDS,array(
            '__VIEWSTATE'=>'/wEPDwUKMTg2MTYyMDc5M2RkJ9KJGhh8uhyhbJTlvKsHNKphQ+0=',
            '__EVENTVALIDATION'=>'/wEWAwKV4/WjBAKvgcuQCQKM54rGBmM4dpSXavZzZP0mtF+DL1no+d54',
            'TrackNum'=>'1605563639',
            'Button1'=>'追踪'
        ));

        curl_multi_add_handle($mch,$ch);
        curl_multi_add_handle($mch,$ch1);

        do{
            $status=curl_multi_exec($mch,$running);
        }while($status==CURLM_CALL_MULTI_PERFORM);

        while($running && $status==CURLM_OK){
            if(curl_multi_select($mch) == -1){
                usleep(1);
            }
            do{
                $status=curl_multi_exec($mch,$running);
            }while($status==CURLM_CALL_MULTI_PERFORM);
        }

        $output=curl_multi_getcontent($ch);
        $output1=curl_multi_getcontent($ch1);

        curl_multi_remove_handle($mch,$ch);
        curl_multi_remove_handle($mch,$ch1);
        curl_multi_close($mch);
        curl_close($ch1);
        curl_close($ch);
    }

    /**
     * 黑猫批量查询
     */
    private function heimao_multi_handle($nums,$type=''){
        $curlh=curl_init($this->heurl);
        $result['err']=array();
        $mcurl=array(
            CURLOPT_TIMEOUT=>'20',
            CURLOPT_POST=>TRUE,
            CURLOPT_CONNECTTIMEOUT=>20,
            CURLOPT_HEADER=>FALSE,
            CURLOPT_RETURNTRANSFER=>TRUE,
            CURLOPT_POSTFIELDS=>array('__EVENTTARGET'=>'ctl00$ContentPlaceHolder1$btnSend',
                '__EVENTARGUMENT'=>'',
                '__VIEWSTATE'=>'/wEPDwULLTE2ODAyMTAzNDBkZHngR2yLNdcoB1YXtf+bAIxi/AHF',
                '__EVENTVALIDATION'=>'/wEWDALXz8K8AwKUhrKJAQL5nJT0BgLes/beDALDytjJAgKo4bq0CAKN+JyfDgLyjv+JBAKHub7IDALsz6CzAgKUhvK7DAK97Mp+etzK3cOKerX3pzYyBL/kZYAJxkM=',
                'q'=>'站內搜尋',
                'cx'=>'05475758396817196247:vpg-mgvhr44',
                'cof'=>'FORID:11',
                'ie'=>'UTF-8'
            ),
            CURLOPT_REFERER=>'http://www.t-cat.com.tw/Inquire/trace.aspx',
        );


        repeatpos:
        for($i=1; $i<11;$i++){
            $mcurl[CURLOPT_POSTFIELDS]['ctl00$ContentPlaceHolder1$txtQuery'.$i] = empty($nums[$i-1]) ? '' : $nums[$i - 1];
        }

        curl_setopt_array($curlh, $mcurl);

        $output=curl_exec($curlh);
	    curl_close($curlh);
        $content=split_html($output,HEIMAO);
        if(isset($content['errnum']) && !empty($content['errnum'])){
            if(is_array($content['errnum'])){
                $result['err']=array_merge_recursive($result['err'],$content['errnum']);
            }else{
                array_push($result['err'],$content['errnum']);
            }
            if(is_array($content['errnum'])){
                //如果错误订单号有多个则遍历去除。
                foreach ($nums as $v){
                    unset($nums[array_search($v,$nums)]);
                }
            }else{
                unset($nums[array_search($content['errnum'],$nums)]);
            }
            unset($content['errnum']);
            if(!empty($nums))
                goto repeatpos;
        }
        $result['ok']=$content;

        return $result;
    }

    /**
     * 日本邮编查询
     * @param array $arr
     */
    public function curl_get_riben($arr=[]){
        //固定参数
        $paramfix=[
            'search.x'=>101,
            'search.y'=>16,
            'locale'=>'ja'
        ];
        $i=1;
        foreach($arr as $v){
            $paraSeach['requestNo'.$i++]= empty($v)?'':$v;
        }
        //生成连接
        $param=http_build_query(array_merge($paraSeach,$paramfix));
        //规避 ssl 证书
        $ch=curl_init($this->riben.$param);
        curl_setopt($ch,CURLOPT_HEADER,FALSE);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
        curl_setopt($ch,CURLOPT_TIMEOUT,20);
        $headers = array();
        $headers[] = 'Host: trackings.post.japanpost.jp';
        $headers[] = 'Upgrade-Insecure-Requests: 1';
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.62 Safari/537.36';

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:')); //避免data数据过长问题
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 检查证书中是否设置域名（为0也可以，就是连域名存在与否都不验证了）
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
        $content=curl_exec($ch);
        curl_close($ch);
        //处理获取的字符串
        $content=split_html($content,RIBEN);
        return  ['ok'=>$content['ok'],'err'=>$content['err']];
    }
    /**
     * 多线程查询,一次最多处理40个。
     * @param array $arr  需要处理的数据
     * @param string $type 快递类型
     * @return array  查询处理的结果
     */
   private function curl_get($arr=array(''),$type='jl'){
        if(empty($arr)) return [];
        switch($type){
            case 'jl':
                    $url=$this->jlurl;
                    break;
            case 'sf':
                    $url=$this->kd100.'shunfeng&postid=';
                    break;
            case 'sld':
                    $url=$this->sld;
                    break;
            case 'riben':
                    $url=$this->riben;
                    break;
        }
        //初始化创建一个curl_multi句柄
        $mh=curl_multi_init();
        //设置活动连接数，防止清楚掉已存在的连接数
        curl_multi_setopt($mh,CURLMOPT_MAXCONNECTS,10);
        //CURLMOPT_PIPELINING  这个值实质就是将多线程改成单线程，慎用。
        curl_multi_setopt($mh,CURLMOPT_PIPELINING,0);
        $handles=array();
        $results['ok']=array();
        $results['err']=array();
        $readinfo=array();
        $curlerr=array();
        $options=array(
            CURLOPT_RETURNTRANSFER=>TRUE,
            CURLOPT_HEADER=>FALSE,
            CURLOPT_TIMEOUT=>10,
        );
        foreach($arr as $ck => $cv){
                if($type=='sld'){
                    array_push($handles,curl_init($url.query_param($cv,1)));
                }else{
                    array_push($handles,curl_init($url.query_param($cv,0)));
                }
        }

        foreach($handles as $hk=>$hv){
            curl_setopt_array($handles[$hk],$options);
            curl_multi_add_handle($mh,$handles[$hk]);
        }

        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) == -1) {
                usleep(1);
            }
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }
        foreach($handles as $kk => $vv){
            if($type=='jl'){
                $content=split_html(curl_multi_getcontent($handles[$kk]),JIALI);
            }elseif($type=='sf'){
                $content=split_html(curl_multi_getcontent($handles[$kk]),SUNF);
            }elseif($type=='sld'){
                $content=split_html(curl_multi_getcontent($handles[$kk]),SLD);
            }
            $tmp=curl_multi_info_read($mh);
            if($tmp['result'] > 0 || count($content)==1 || $content==FALSE){
                array_push($results['err'],$arr[$kk]);
            }else{
                array_push($results['ok'],$content);
            }
        }
        foreach ($handles as $rk => $rv){
            curl_multi_remove_handle($mh,$handles[$rk]);
            curl_close($handles[$rk]);
        }
        curl_multi_close($mh);
        return $results;
    }
    //代用ssh参数的连接
    /**
     * 快递入口
     */
    public function handle(){
        set_time_limit(0);
        $result_ok=[];
        $result_err=[];
        $curl_result=[];
        $data=addslashes( htmlspecialchars(strip_tags( $this->input->get_post('data'))));
        $type=addslashes( htmlspecialchars(strip_tags($this->input->get_post('logistics_type'))));
        $num=preg_split('/\n/',trim($data));

        //最多查100条
        $num = array_slice($num,0,100);

        switch($type){
            case 'guoji':
                //url_type  用来设定需要调用的函数。
                $url_type='handle_url';

                foreach($num as $value){
                    if(empty($value)) continue;
                    $curl_result = $this->$url_type($value);
                    if(empty($curl_result)){
                        array_push($result_err,$value);
                    }else{
                        array_push($result_ok,$curl_result);
                    }
                }
                break;
            case 'heimao':
            case 'sh':
            case 'sf':
            case 'sld':
            case 'jl':
            case 'riben':
//                $url_type= $type=='heimao'?'heimao_multi_handle':'curl_get';
                if($type=='heimao'){
                    $url_type='heimao_multi_handle';
                }elseif($type=='sh') {
                    $url_type = 'curl_post';
                }elseif($type='riben'){
                    $url_type='curl_get_riben';
                }else{
                    $url_type='curl_get';
                }

                $num_index=count($num);
                if($num_index>SPLIT_LENGTH){
                    for($i=0;$i < $num_index;$i=$i+SPLIT_LENGTH){
                        //10会发生变化
                        $tmp=$this->$url_type(array_splice($num,0,SPLIT_LENGTH),$type);
                        if(empty($tmp)) continue;
                        $result_ok=array_merge_recursive($result_ok,$tmp['ok']);
                        $result_err=array_merge_recursive($result_err,$tmp['err']);
                    }
                }else{
                    $tmp=$this->$url_type($num,$type);
                    $result_ok=array_merge_recursive($result_ok,$tmp['ok']);
                    $result_err=array_merge_recursive($result_err,$tmp['err']);
                }
                break;
            default: break;
        }
        exit(json_encode(array('ok'=>$result_ok,'err'=>$result_err)));
    }
}
