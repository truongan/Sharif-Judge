<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Sort_language extends CI_Migration {
    public function up(){
        $this->dbforge->add_column('languages', array(
            'sorting' => array('type' =>'INT', 'constraint' => 11, 'unsigned' => TRUE)
        ));
        $this->db->update('languages', array('sorting' => 2), array('id' => 1));
        $this->db->update('languages', array('sorting' => 1), array('id' => 2));
        $this->db->update('languages', array('sorting' => 3), array('id' => 3));
        $this->db->update('languages', array('sorting' => 4), array('id' => 4));
        $this->db->update('languages', array('sorting' => 5), array('id' => 5));
    }

    public function down()
    {
        $this->dbforge->drop_column('languages', 'sorting');
        //Can not be downed. 
        //This is the first imgrations. 
    }
}
?>