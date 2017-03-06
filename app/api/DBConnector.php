<?php
/**
 * Created by PhpStorm.
 * User: Gene
 * Date: 12/11/2016
 * Time: 6:36 AM
 */

/**
 * Class DBConnector
 * Wrapper for the PHP mysqli database connector object
 * Main query methods are:
 *    getResultArray(): returns <array(<array(key=>value)>)>
 *    getResultRow(): returns <array(key=>value)>
 *    query(): returns <int|bool>
 */
class DBConnector {
   private $conn;
   private $host;
   private $user;
   private $password;
   private $database;

   /**
    * DBConnector constructor.
    * May be called with no parameters. In this case the connect() method called at the end of the constructor will
    * do nothing and we will need to establish a db connection later on by manually calling connect() with the correct
    * database parameters
    * @param $host
    * @param $user
    * @param $password
    * @param $database
    */
   function __construct($host=null, $user=null, $password=null, $database=null) {
      $this->conn = null;
      $this->host = $host;
      $this->user = $user;
      $this->password = $password;
      $this->database = $database;

      $this->connect();
   }

   /**
    * Initiate MySQL database connection.
    * Method may be called with no parameters. In this case it will use the parameters previously set in the constructor
    * @param $host
    * @param $user
    * @param $password
    * @param $database
    */
   public function connect($host=null, $user=null, $password=null, $database=null) {
      // if new parameters were specified, set the class variables to them
      if ($host && $user && $password && $database) {
         $this->host = $host;
         $this->user = $user;
         $this->password = $password;
         $this->database = $database;
      }

      // try to initiate a sql connection if db parameters are set
      if ($this->host && $this->user && $this->password && $this->database) {

         $this->conn = mysqli_connect($this->host, $this->user, $this->password);

         if (!mysqli_select_db($this->conn, $this->database)) {
            print "Not connected to database.";
            $this->conn = null;
         }
      }
   }

   /**
    * Check to make sure $this->conn is not null and if it is, attempt to open it
    */
   private function checkConnection() {
      if ($this->conn != null) {
         return true;
      } else {
         $this->connect();
         if ($this->conn == null) {
            return false;
         } else {
            return true;
         }
      }
   }

   /**
    * Get a list of results from the database, multiple rows
    * @param $query
    * @return array
    */
   public function getResultArray($query) {
      $result_rows = array();
      
      if (! $this->checkConnection()) {
         return $result_rows;
      }
      
      $result = mysqli_query($this->conn, $query);

      while ($result_row = mysqli_fetch_assoc($result)) {
         $result_rows[] = $result_row;
      }
      return $result_rows;
   }

   /**
    * Get a single row result from the database
    * @param $query
    * @return array|null
    */
   public function getResultRow($query) {
      $result_row = array();

      if (! $this->checkConnection()) {
         return $result_row;
      }
      
      $result = mysqli_query($this->conn, $query);

      $result_row = mysqli_fetch_assoc($result);

      return $result_row;
   }

   /**
    * Execute a query that does not return any data such as an INSERT or UPDATE statement
    * returns true if the query succeeded and false if something went wrong
    * @param $query
    * @return bool|mysqli_result|void
    */
   public function query($query) {
      if (! $this->checkConnection()) {
         return false;
      }
      $result = mysqli_query($this->conn, $query);

      return $result;
   }

   /**
    * Returns the last insert id from the mysqli connection object
    * @return int|string
    */
   public function lastInsertId() {
      return mysqli_insert_id($this->conn);
   }

   /**
    * User the mysqli object to escape a string and returns the result
    * @param $string: String to be escaped
    * @return string: Escaped string
    */
   public function escape($string) {
      return mysqli_real_escape_string($this->conn, $string);
   }
}