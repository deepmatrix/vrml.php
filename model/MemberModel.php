<?php

class MemberModel extends discuz_table {
    
    protected $_table = 'common_member';
	protected $_pk    = 'uid';
	protected $_pre_cache_key = 'common_member_';
    
    public function count_admins() {
		return DB::result_first("SELECT COUNT(*) FROM ".DB::table($this->_table)." WHERE adminid<>'0' AND adminid<>'-1'");
	}
}