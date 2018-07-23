<?php

class site_day_statistics_model extends MY_Model{
	protected $_pk = 'id';
	protected $_table = 'site_day_statistics';

    function incr_by_date($date){
        $sql = "INSERT INTO ".$this->db->dbprefix($this->get_table()) . " (views,date) VALUES (1,'$date') ON DUPLICATE KEY UPDATE views=views+1;";
        return $this -> db -> query($sql);
    }
}

/* End of file site_day_statistics_model.php */
/* Location: ./application/models/site_day_statistics_model.php */