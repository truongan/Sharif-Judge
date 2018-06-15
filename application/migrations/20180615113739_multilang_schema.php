<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Multilang_schema extends CI_Migration {

    private function create_and_show_err($table_name){
        if ( ! $this->dbforge->create_table($table_name, TRUE))
            show_error("Error creating database table ".$this->db->dbprefix($table_name));
    }
    public function up()
    {
        $lang = array(
            'C' => array('id' => 1, 'name' => 'C', 'extension' => 'c'),
            'C++' => array('id' => 2, 'name' => 'C++', 'extension' => 'cpp'),
            'Java' => array('id' => 3, 'name' => 'Java', 'extension' => 'java'),
            'Python 3' => array('id' => 4, 'name' => 'Python 3', 'extension' => 'py3'),
            'Python 2' => array('id' => 5, 'name' => 'C', 'extension' => 'py2'),
        );

        // create table 'languages'
        $fields = array(
            'id'            => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'name'    => array('type' => 'VARCHAR', 'constraint' => 45, 'default' => '0'),
            'extension'    => array('type' => 'VARCHAR', 'constraint' => 3),
            'default_timle_limit'      => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'default' => 500),
            'default_memory_limit'      => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'default' => 50000),
        );
        $this->dbforge->add_field($fields)->add_key('id', TRUE); // PRIMARY KEY
        create_and_show_err('languages');

        foreach ($$lang as $key => $value) {
            $this->db->insert('languages', $value);
        }

        // create table 'problem_language'
        $fields = array(
            'language_id'            => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'problem_id'            => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'timle_limit'      => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'default' => 500),
            'memory_limit'      => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'default' => 50000),
        );
        $this->dbforge->add_field($fields)->add_key(array('language_id', 'problem_id')); // PRIMARY KEY
        create_and_show_error('problem_language');
        
        // create table 'problem_assignment'
        $fields = array(
            'assignment_id'            => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'problem_id'            => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'score'             => array('type' => 'INT', 'constraint' => 11),
        );
        $this->dbforge->add_field($fields)->add_key(array('assignment_id', 'problem_id')); // PRIMARY KEY
        create_and_show_error('problem_assignment');
        
        
        /* 
        * Migrate old data
        */
        $this->db->trans_start();
        $query = $this->db->get('problems');
        $new_id = $this->db->count_all('problems') + 1;
        foreach ($query->result() as $prob)
        {
            $where_clause = array('assignment_id' => $prob->assignment,
                                    'problem_id' => $prob->id
                                );
            $this->db->update('submissions', array('problem' => $new_id))->where($where_clause);
            $this->db->update('problems', array('id' => $new_id))->where($where_clause);

            $this->db->insert('problem_assignment',array(
                'assignment_id' => $prob->assignment,
                'problem_id' => $new_id,
                'score' => $this->db->get('assignemnts')->where($where_clause)->result()->score
            ));

            foreach(explode($prob->allowed_languages) as $i){
                $arr = array(
                    'language_id' => $lang[$i]['id'],
                    'problem_id' => $new_id,
                    'memory_limit' => $prob->memory_limit
                );

                if ($i[0] == 'C') $arr['time_limit'] = $prob->c_time_limit;
                else if ($i == 'java') $arr['time_limit'] = $prob->java_time_limit;
                else if ($i[0] == 'P') $arr['time_limit'] = $prob->python_time_limit;

                $this->db->insert('problem_language', $arr);
            }
            
        }

        $this->dbforge->add_column('submissions', array(
                'language_id' => array('type' =>'INT', 'constraint' => 11, 'unsigned' => TRUE)
        ));
        foreach ($lang as  $l) {
            $this->db->update('submissions', array('language_id' => $l['id']))->where('file_type', $l['extension']);
        }
        $this->dbforge->drop_column('submissions', 'file_type');
        $this->dbforge->drop_column('submissions', 'main_file_type');

        $this->dbforge->modify_column('submissions', array('assignment' => array(
            'name' => 'assignment_id',
            'type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE
        )));

    
        $this->dbforge->drop_column('assignments', 'problems');

        $this->dbforge->drop_column('problems'
            , array('assignment', 'score', 'c_time_limit'
                , 'java_time_limit', 'pyton_time_limit'
                , 'allowed_languages', 'memory_limit')
        );
    }

    public function down()
    {
        //Can not be downed. 
        //This is the first imgrations. 
    }
}
?>