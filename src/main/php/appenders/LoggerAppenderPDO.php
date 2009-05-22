<?php
/*
 * Licensed to the Apache Software Foundation (ASF) under one or more
 * contributor license agreements.  See the NOTICE file distributed with
 * this work for additional information regarding copyright ownership.
 * The ASF licenses this file to You under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with
 * the License.  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Appends log events to a db table using PDO
 *
 * <p>This appender uses a table in a database to log events.</p>
 * <p>Parameters are {@link $host}, {@link $user}, {@link $password},
 * {@link $database}, {@link $createTable}, {@link $table} and {@link $sql}.</p>
 *
 * @package log4php
 * @subpackage appenders
 * @since 2.0
 */
class LoggerAppenderPDO extends LoggerAppender {
    /** Create the log table if it does not exists (optional). */
	private $createTable = true;
    
    /** Database user name */
    private $user = '';
    
    /** Database password */
    private $password = '';
    
    /** Name of the database to connect to */
    private $database;
	
	/** DSN string for enabling a connection */    
    private $dsn;
    
    /** A {@link LoggerPatternLayout} string used to format a valid insert query (mandatory) */
    private $sql;
    
    /** Table name to write events. Used only if {@link $createTable} is true. */    
    private $table = 'log4php_log';
    
    /** The instance */
    private $db = null;
    
    /** boolean used to check if all conditions to append are true */
    private $canAppend = true;
    
    /**
     * Constructor.
     * This apender doesn't require a layout.
     * @param string $name appender name
     */
    function __construct($name) {
        parent::__construct($name);
        $this->requiresLayout = false;
    }

    /**
     * Setup db connection.
     * Based on defined options, this method connects to db defined in {@link $dsn}
     * and creates a {@link $table} table if {@link $createTable} is true.
     * @return boolean true if all ok.
     */
    public function activateOptions() {
    	$this->db = new PDO($this->dsn,$this->password,$this->password);
		
		if($this->db == null) {
			$this->db = null;
			$this->closed = true;
			$this->canAppend = false;
			return;
			// TODO throw exception instead?
		}
		
        // test if log table exists
        $result = $this->db->query('select * from ' . $this->table . ' where 1 = 0');
        if ($result == false and $this->createTable) {
        	// TODO mysql syntax?
            $query = "CREATE TABLE {$this->table} (	 timestamp varchar(32)," .
            										"logger varchar(32)," .
            										"level varchar(32)," .
            										"message varchar(64)," .
            										"thread varchar(32)," .
            										"file varchar(64)," .
            										"line varchar(4) );";

            $result = $this->db->query($query);
            if (!$result) {
                $this->canAppend = false;
                return;
                // TODO throw exception?
            }
        }
        
        if($this->sql == '' || $this->sql == null) {
            $this->sql = "INSERT INTO $this->table ( timestamp, " .
            										"logger, " .
            										"level, " .
            										"message, " .
            										"thread, " .
            										"file, " .
            										"line" .
						 ") VALUES ('%d','%c','%p','%m','%t','%F','%L')";
        }
        
		$this->layout = LoggerLayout::factory('LoggerLayoutPattern');
        $this->layout->setConversionPattern($this->sql);
        $this->canAppend = true;
        return true;
    }
    
    /**
     * Appends a new event to the database using the sql format.
     */
     // TODO:should work with prepared statement
    public function append($event) {
        if ($this->canAppend) {
            $query = $this->layout->format($event);
            $this->db->exec($query);
        }
    }
    
    /**
     * Closes the connection to the logging database
     */
    public function close() {
        if ($this->db !== null) {
            $db = null;
        }
        $this->closed = true;
    }
    
    /**
     * Indicator if the logging table should be created on startup,
     * if its not existing.
     */
    public function setCreateTable($flag) {
        $this->createTable = LoggerOptionConverter::toBoolean($flag, true);
    }
   
   	/**
     * Sets the username for this connection. 
     * Defaults to ''
     */
    function setUser($newUser) {
        $this->user = $newUser;
    }
    
    /**
     * Sets the password for this connection. 
     * Defaults to ''
     */
    public function setPassword($newPassword) {
        $this->password = $newPassword;
    }
    
    /**
     * Sets the SQL string into which the event should be transformed.
     * Defaults to:
     * 
     * INSERT INTO $this->table 
     * ( timestamp, logger, level, message, thread, file, line) 
     * VALUES 
     * ('%d','%c','%p','%m','%t','%F','%L')
     * 
     * It's not necessary to change this except you have customized logging'
     */
    public function setSql($sql) {
        $this->sql = $sql;    
    }
    
    /**
     * Sets the tablename to which this appender should log.
     * Defaults to log4php_log
     */
    public function setTable($table) {
        $this->table = $table;
    }
    
    /**
     * Sets the DSN string for this connection. In case of
     * SQLite it could look like this: 'sqlite:appenders/pdotest.sqlite'
     */
    public function setDSN($dsn) {
        $this->dsn = $dsn;
    }
    
    /**
     * Sometimes databases allow only one connection to themselves in one thread.
     * SQLite has this behaviour. In that case this handle is needed if the database
     * must be checked for events
     */
    public function getDatabaseHandle() {
        return $this->db;
    }
}

