<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_pascal_default_languages extends CI_Migration {
    public function up(){
        $this->db->set('sorting', 'sorting*10', FALSE)->update('languages');
        $this->db->insert('settings', array('shj_key' => 'default_language_number', 'shj_value' => 2));
        $this->db->insert('languages'
                            , array(
                                'name' => 'Free Pascal',
                                'sorting' => 60,
                                'extension' => 'pas'
                            )
                        );
    }

    public function down()
    {
        // $this->dbforge->drop_column('queue', 'process_id');
    }
}
?>