<?php

namespace app\web\controller;

use think\facade\Db;

class Record extends Base
{

    /**
     * 获取ai检测/改写记录
     */
    public function Index()
    {
        $type = input('type', 1,); //1=检测 2=改写
        $limit = input('page_size', '10', 'intval');

        $where = [
            ['site_id', '=', self::$site_id],
            ['user_id', '=', self::$user['id']],
            ['is_delete', '=', 0]
        ];

        if ($type == 1) {
            $dbName = 'msg_detect';
        } else {
            $dbName = 'msg_wyccheck';
        }


        $list = Db::name($dbName)
            ->where($where)
            ->field('id,message_input,response,create_time')
            ->order('id desc')
            ->paginate($limit)
            ->toArray();
        foreach ($list['data'] as $k => $v) {
            $list['data'][$k]['num'] = mb_strlen($v['message_input'], 'utf8');
            $list['data'][$k]['num2'] = mb_strlen($v['response'], 'utf8');
            $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            if($type == 1){
                $response = json_decode($v['response'],true);
                if(isset($response['percent'])){
                    $text = '混合撰写';
                    if($response['percent'] == 0){
                        $text = '人工撰写';
                    }
                    if($response['percent'] == 100){
                        $text = 'AI智能';
                    }
                    $list['data'][$k]['response'] = $text.'(AI撰写率:'.$response['percent'].'%)';
                }

            }
        }

        return successJson([
            'list' => $list
        ]);
    }

}
