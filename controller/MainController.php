<?php

class MainController extends Controller {
    
    public function index(){
        
        /**
         * Discuz自带的table类，可以直接调用，autoload会自动找到类
         */
        $member = new table_common_member();
        $admin_count = $member->count_admins();
        
        /**
         * 自写模型类，同样继承discuz_table，autoload到model目录找
         */
        $member_obj2 = new MemberModel();
        $admin_count2 = $member_obj2->count_admins();
        
        /**
         * 视图文件存放在view下对应控制器目录main
         * 完全兼容Discuz视图模式
         * 可以传递变量到视图
         */
        $this->render('index', array('a' => 200, 'uid' => $this->uid, 'admin_count' => $admin_count2));
    }
}