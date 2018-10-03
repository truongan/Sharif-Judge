<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Old_schema extends CI_Migration {

    private function create_and_show_err($table_name){
        if ( ! $this->dbforge->create_table($table_name, TRUE))
            show_error("Error creating database table ".$this->db->dbprefix($table_name));
    }
    public function up()
    {
        $DATETIME = 'DATETIME';
        if ($this->db->dbdriver === 'postgre')
            $DATETIME = 'TIMESTAMP';
            
        // create table 'sessions'
        $fields = array(
            'session_id'    => array('type' => 'VARCHAR', 'constraint' => 40, 'default' => '0'),
            'ip_address'    => array('type' => 'VARCHAR', 'constraint' => 45, 'default' => '0'),
            'user_agent'    => array('type' => 'VARCHAR', 'constraint' => 120),
            'last_activity' => array('type' => 'INT', 'constraint' => 10, 'unsigned' => TRUE, 'default' => '0'),
            'user_data'     => array('type' => 'TEXT'),
        );
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('session_id', TRUE); // PRIMARY KEY
        $this->dbforge->add_key('last_activity');
        if ( ! $this->dbforge->create_table('sessions', TRUE))
            show_error("Error creating database table ".$this->db->dbprefix('sessions'));


        // create table 'submissions'
        $fields = array(
            'submit_id'     => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'username'      => array('type' => 'VARCHAR', 'constraint' => 20),
            'assignment'    => array('type' => 'SMALLINT', 'constraint' => 4, 'unsigned' => TRUE),
            'problem'       => array('type' => 'SMALLINT', 'constraint' => 4, 'unsigned' => TRUE),
            'is_final'      => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'time'          => array('type' => $DATETIME),
            'status'        => array('type' => 'VARCHAR', 'constraint' => 100),
            'pre_score'     => array('type' => 'INT', 'constraint' => 11),
            'coefficient'   => array('type' => 'VARCHAR', 'constraint' => 6),
            'file_name'     => array('type' => 'VARCHAR', 'constraint' => 30),
            'main_file_name'=> array('type' => 'VARCHAR', 'constraint' => 30),
            'file_type'     => array('type' => 'VARCHAR', 'constraint' => 6),
        );
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key(array('assignment', 'submit_id'));
        if ( ! $this->dbforge->create_table('submissions', TRUE))
            show_error("Error creating database table ".$this->db->dbprefix('submissions'));

        // create table 'assignments'
        $fields = array(
            'id'            => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'name'          => array('type' => 'VARCHAR', 'constraint' => 50, 'default' => ''),
            'problems'      => array('type' => 'SMALLINT', 'constraint' => 4, 'unsigned' => TRUE),
            'total_submits' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'open'          => array('type' => 'TINYINT', 'constraint' => 1),
            'scoreboard'    => array('type' => 'TINYINT', 'constraint' => 1),
            'javaexceptions'=> array('type' => 'TINYINT', 'constraint' => 1),
            'description'   => array('type' => 'TEXT'),
            'start_time'    => array('type' => $DATETIME),
            'finish_time'   => array('type' => $DATETIME),
            'extra_time'    => array('type' => 'INT', 'constraint' => 11),
            'late_rule'     => array('type' => 'TEXT'),
            'participants'  => array('type' => 'TEXT'),
            'moss_update'   => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'Never'),
        );
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('id', TRUE); // PRIMARY KEY
        if ( ! $this->dbforge->create_table('assignments', TRUE))
            show_error("Error creating database table ".$this->db->dbprefix('assignments') . print_r($this->db->error(), true));


        // create table 'notifications'
        $fields = array(
            'id'            => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'title'         => array('type' => 'VARCHAR', 'constraint' => 200, 'default' => ''),
            'text'          => array('type' => 'TEXT'),
            'time'          => array('type' => $DATETIME),
        );
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('id', TRUE); // PRIMARY KEY
        if ( ! $this->dbforge->create_table('notifications', TRUE))
            show_error("Error creating database table ".$this->db->dbprefix('notifications'));

        // create table 'problems'
        $fields = array(
            'assignment'        => array('type' => 'SMALLINT', 'constraint' => 4, 'unsigned' => TRUE),
            'id'                => array('type' => 'SMALLINT', 'constraint' => 4, 'unsigned' => TRUE),
            'name'              => array('type' => 'VARCHAR', 'constraint' => 50, 'default' => ''),
            'score'             => array('type' => 'INT', 'constraint' => 11),
            'is_upload_only'    => array('type' => 'TINYINT', 'constraint' => 1, 'default' => '0'),
            'c_time_limit'      => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'default' => 500),
            'python_time_limit' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'default' => 1500),
            'java_time_limit'   => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'default' => 2000),
            'memory_limit'      => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'default' => 50000),
            'allowed_languages' => array('type' => 'TEXT'),
            'diff_cmd'          => array('type' => 'VARCHAR', 'constraint' => 20, 'default' => 'diff'),
            'diff_arg'          => array('type' => 'VARCHAR', 'constraint' => 20, 'default' => '-bB'),
        );
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key(array('assignment', 'id'));
        if ( ! $this->dbforge->create_table('problems', TRUE))
            show_error("Error creating database table ".$this->db->dbprefix('problems'));

        // create table 'queue'
        $fields = array(
            'id'                => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'submit_id'         => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'username'          => array('type' => 'VARCHAR', 'constraint' => 20),
            'assignment'        => array('type' => 'SMALLINT', 'constraint' => 4, 'unsigned' => TRUE),
            'problem'           => array('type' => 'SMALLINT', 'constraint' => 4, 'unsigned' => TRUE),
            'type'              => array('type' => 'VARCHAR', 'constraint' => 8),
        );
        $this->dbforge->add_key('id', TRUE); // PRIMARY KEY
        $this->dbforge->add_field($fields);
        if ( ! $this->dbforge->create_table('queue', TRUE))
            show_error("Error creating database table ".$this->db->dbprefix('queue'));

        // create table 'scoreboard'
        $fields = array(
            'assignment'        => array('type' => 'SMALLINT', 'constraint' => 4, 'unsigned' => TRUE),
            'scoreboard'        => array('type' => 'LONGTEXT'),
        );
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('assignment');
        if ( ! $this->dbforge->create_table('scoreboard', TRUE))
            show_error("Error creating database table ".$this->db->dbprefix('scoreboard'));
 
        // create table 'settings'
        $fields = array(
            'shj_key'        => array('type' => 'VARCHAR', 'constraint' => 50),
            'shj_value'      => array('type' => 'TEXT'),
        );
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('shj_key');
        if ( ! $this->dbforge->create_table('settings', TRUE))
            show_error("Error creating database table ".$this->db->dbprefix('settings'));

        // create table 'users'
        $fields = array(
            'id'                  => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'username'            => array('type' => 'VARCHAR', 'constraint' => 20),
            'password'            => array('type' => 'VARCHAR', 'constraint' => 100),
            'display_name'        => array('type' => 'VARCHAR', 'constraint' => 40, 'default' => ''),
            'email'               => array('type' => 'VARCHAR', 'constraint' => 40),
            'role'                => array('type' => 'VARCHAR', 'constraint' => 20),
            'passchange_key'      => array('type' => 'VARCHAR', 'constraint' => 60, 'default' => ''),
            'passchange_time'     => array('type' => $DATETIME, 'null' => TRUE),
            'first_login_time'    => array('type' => $DATETIME, 'null' => TRUE),
            'last_login_time'     => array('type' => $DATETIME, 'null' => TRUE),
            'selected_assignment' => array('type' => 'SMALLINT', 'constraint' => 4, 'unsigned' => TRUE, 'default' => 0),
        );
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('id', TRUE); // PRIMARY KEY
        $this->dbforge->add_key('username'); // @todo is this needed?
        if ( ! $this->dbforge->create_table('users', TRUE))
            show_error("Error creating database table ".$this->db->dbprefix('users'));

    }

    public function down()
    {
        //Can not be downed. 
        //This is the first imgrations. 
    }
}
?>