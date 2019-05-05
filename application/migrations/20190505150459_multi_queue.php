<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Multi_queue extends CI_Migration {
    public function up(){
        $this->dbforge->add_column('queue', array(
            'process_id' => array('type' =>'INT', 'constraint' => 11, 'unsigned' => TRUE, 'null' => TRUE)
        ));
    }

    public function down()
    {
        $this->dbforge->drop_column('queue', 'process_id');
    }
}
?>