<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_scipy_default_language extends CI_Migration {
    public function up(){
        $this->db->insert('languages'
                            , array(
                                'name' => 'Scipy - using jupyter scipy-notebook image',
                                'sorting' => 70,
                                'extension' => 'scipy'
                            )
                        );
    }

    public function down()
    {
        // $this->dbforge->drop_column('queue', 'process_id');
    }
}
?>