<?php
namespace app\index\controller;
use app\common\model\UserModel;
use app\common\model\RoomModel;
use think\Controller;
use think\Request;

class IndexController extends Controller
{
    public function test()
    {
        return $this->fetch('test');
    }
	
    public function index()
    {
        return $this->fetch('index');
    }
    
    public function rank()
    {
        $type = Request::instance()->param('type');
        
        if ($type == 'room')
        {
            return $this->rooms();
        }
        
        $sort = $type;
        $time = time();
        $online_time = $time - 900;
        $this->assign('time', $online_time);
        $this->assign('online_time', $online_time);
        $this->assign('rank_name', '码字风云榜');
        if (is_null($type) || $type == 'level')
        {
            $type = 'level';
            $sort = 'level desc';
        }
        else if ($type == 'yestoday')
        {
            $sort = 'words_yestoday desc';
            $this->assign('rank_name', '昨日风云榜');
        }
        else if ($type == 'today')
        {
            $sort = new \think\db\Expression('allwords - allwords_yestoday desc');
            $this->assign('rank_name', '今日风云榜');
        }
        else if ($type == 'room' || $type == 'myroom')
            $sort = 'level DESC';
        else
        {
            $type = 'level';
            $sort = 'level desc';
        }

        $users = new UserModel();
        $online_count = 0;
        if ($type != 'room' && $type != 'myroom')
        {
            $online_count = count($users->where("sync_time >= '$online_time'")->select());
            $users->where("allwords>allwords_yestoday or words_yestoday>0 or VIP_deadline>0 or sync_time>$online_time")
                  ->order($sort);
        }
        else if ($type == 'myroom')
        {
            if (!UserModel::isLogin())
                return $this->error('请先登录', url('User/goLogin'));
            $roomID = session('room_id');
            if ($roomID == '')
                return $this->error('您尚未加入房间', url('Index/rank?type=room'));
            $room = RoomModel::get(['roomID' => $roomID]);
            $this->assign('rank_name', $room->roomname);

            $online_count = count($users->where("roomID = '$roomID' and sync_time >= '$online_time'")->select());
            $users->where("roomID = '$roomID'")
                  ->order($sort);
        }
        
        $users = $users->paginate(30);
        $this->assign('users', $users);
        $this->assign('online_count', $online_count);
        return $this->fetch('rank');
    }
    
    public function rooms()
    {
        $rooms = new RoomModel();
        $rooms->order('level desc');
        $rooms = $rooms->select();
        
        $this->assign('rooms', $rooms);
        return $this->fetch('rooms');
    }
}
