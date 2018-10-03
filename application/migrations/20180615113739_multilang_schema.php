<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Multilang_schema extends CI_Migration {

    private function create_and_show_err($table_name){
        if ( ! $this->dbforge->create_table($table_name, TRUE))
            show_error("Error creating database table ".$this->db->dbprefix($table_name));
    }
    private function echo_error(){
        echo "Error executing " 
            . $this->db->last_query() 
            . " message: " . $this->db->_error_message();
        die();
    }
    private $lang = array(
        'C++' => array('id' => 2, 'name' => 'C++', 'extension' => 'cpp'),
        'C' => array('id' => 1, 'name' => 'C', 'extension' => 'c'),
        'Java' => array('id' => 3, 'name' => 'Java', 'extension' => 'java'),
        'Python 3' => array('id' => 4, 'name' => 'Python 3', 'extension' => 'py3'),
        'Python 2' => array('id' => 5, 'name' => 'Python 2', 'extension' => 'py2'),
    );

    private function migrate_old_data(){
        /* 
        * Migrate old data
        */

        $query = $this->db->get('problems');
        $new_id = $this->db->count_all('problems') + 1;

        $assignments_root = rtrim($this->settings_model->get_setting('assignments_root'), '/');
        if ($assignments_root == NULL) {
            //We have no assignments folder, this could mean this is a freshly installed judge
            return ;
        }
        $problems_dir = $assignments_root . "/problems/";
        if ( ! file_exists($problems_dir) ){
            var_dump("creating directory $problems_dir as problem directory");
            mkdir($problems_dir, 0700);
        }

        foreach ($query->result() as $prob)
        {
            $where_clause 
                = array('assignment' => $prob->assignment, 'id' => $prob->id);
                                
            $score_query = $this->db->where($where_clause)
                            ->get('problems')->row();

            //Create new problem - assignment relation
            if (!$this->db->insert('problem_assignment',array(
                'assignment_id' => $prob->assignment,
                'problem_id' => $new_id,
                'score' => $score_query->score,
                'ordering' => $prob->id,
                'problem_name' => $prob->name,
            ))) echo_error();
          
            //Moving directory
            $old_p = $assignments_root . '/assignment_' . $prob->assignment . '/p' . $prob->id;
            $new_p = $assignments_root . '/problems/' . $new_id;    
            $new_prob_submission = $assignments_root . '/assignment_' . $prob->assignment . '/problem_' . $new_id;    
            
            if ( ! file_exists($new_p) )
                mkdir($new_p, 0700);
            
            foreach(array('in', 'out', 'tester*', 'template.*', 'desc.*', '*.pdf' , 'solution.*') as $i){
                $cmd = "mv $old_p/$i $new_p";
                //var_dump ($cmd);
                shell_exec($cmd);
            }
            rename($old_p, $new_prob_submission);

            //Update submission with new problem ID
            $this->db->where(array('assignment' => $prob->assignment, 'problem' => $prob->id))
                    ->update('submissions', array('problem' => $new_id));
            $this->db->where($where_clause)->update('problems', array('id' => $new_id));
            
            //Create new problem - language relation
            foreach(explode(",", $prob->allowed_languages) as $i){
                $arr = array(
                    'language_id' => $this->lang[$i]['id'],
                    'problem_id' => $new_id,
                    'memory_limit' => $prob->memory_limit
                );

                if ($i[0] == 'C') $arr['time_limit'] = $prob->c_time_limit;
                else if ($i == 'java') $arr['time_limit'] = $prob->java_time_limit;
                else if ($i[0] == 'P') $arr['time_limit'] = $prob->python_time_limit;

                $this->db->insert('problem_language', $arr);
                //var_dump($this->db->last_query());
            }
            
            $new_id++;
        }

        foreach ($this->lang as  $l) {
            $this->db->where('file_type', $l['extension'])
                    ->update('submissions', array('language_id' => $l['id']));
        }
    }

    private function create_new_table(){
        // create table 'languages'
        $fields = array(
            'id'            => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'name'    => array('type' => 'VARCHAR', 'constraint' => 45, 'default' => '0', 'unique' => true),
            'extension'    => array('type' => 'VARCHAR', 'constraint' => 8, 'unique' => true),
            'default_time_limit'      => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'default' => 500),
            'default_memory_limit'      => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'default' => 50000),
        );
        $this->dbforge->add_field($fields)->add_key('id', TRUE); // PRIMARY KEY
        $this->create_and_show_err('languages');

        foreach ($this->lang as $key => $value)
            $this->db->insert('languages', $value);

        // create table 'problem_language'
        $fields = array(
            'language_id'            => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'problem_id'            => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'time_limit'      => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'default' => 500),
            'memory_limit'      => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'default' => 50000),
        );
        $this->dbforge->add_field($fields)->add_key(array('language_id', 'problem_id'), TRUE); // PRIMARY KEY
        $this->create_and_show_err('problem_language');
        
        // create table 'problem_assignment'
        $fields = array(
            'assignment_id'            => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'problem_id'            => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'score'             => array('type' => 'INT', 'constraint' => 11),
            'ordering'             => array('type' => 'INT', 'constraint' => 11),
            'problem_name'             => array('type' => 'VARCHAR', 'constraint' => 150, 'default' => ''),
        );
        $this->dbforge->add_field($fields)->add_key(array('assignment_id', 'problem_id'), TRUE); // PRIMARY KEY
        $this->create_and_show_err('problem_assignment');


    }

    public function up(){
        $this->db->trans_start();

        $this->create_new_table();

        $this->dbforge->add_column('submissions', array(
            'language_id' => array('type' =>'INT', 'constraint' => 11, 'unsigned' => TRUE)
        ));
        $this->migrate_old_data();

        /*
        * Alter old table structure
        */
        $this->dbforge->drop_column('submissions', 'file_type');
        $this->dbforge->drop_column('submissions', 'main_file_name');

        $this->dbforge->modify_column('submissions', array('assignment' => array(
            'name' => 'assignment_id',
            'type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE
        )));
        $this->dbforge->modify_column('submissions', array('problem' => array(
            'name' => 'problem_id',
            'type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE
        )));
        var_dump($this->db->last_query());
        $table = $this->db->dbprefix('submissions');
        $this->db->query("ALTER TABLE $table ADD PRIMARY KEY(submit_id, assignment_id)");
        var_dump($this->db->last_query());
        
        $this->dbforge->drop_column('assignments', 'problems');
        $this->dbforge->modify_column('assignments', array('name' => array(
            'type' => 'VARCHAR', 'constraint' => 150, 'default' => ''
        )));

        foreach (array('assignment','score','c_time_limit','java_time_limit','python_time_limit','allowed_languages','memory_limit') as $i)
            $this->dbforge->drop_column('problems', $i);
        $this->dbforge->add_column('problems'
                    , array('admin_note' => array('type' => 'VARCHAR', 'constraint' => 1500, 'default' => '')));
        
        $this->dbforge->modify_column('problems', array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'name' => array('type' => 'VARCHAR', 'constraint' => 150, 'default' => '')
        ));
        var_dump($this->db->last_query());
        
        $table = $this->db->dbprefix('problems');
        $this->db->query("ALTER TABLE $table ADD PRIMARY KEY(id)");
        var_dump($this->db->last_query());
        
        $this->db->trans_complete();
    }

    public function down()
    {
        //Can not be downed. 
        //This is the first imgrations. 
    }
}
?>