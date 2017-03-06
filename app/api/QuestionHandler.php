<?php
/**
 * Created by PhpStorm.
 * User: Gene
 * Date: 3/4/2017
 * Time: 11:52 PM
 */

/**
 * Class QuestionHandler
 *
 * Handler for getting and storing questions
 * Converts relation database data into json objects
 * Takes care of paging for large data sets
 */
class QuestionHandler {
   private $db_conn;       /** @var  DBConnector: MySQL database connection */
   private $page;          /** @var  int: The page number requested */
   private $page_size;     /** @var  int: The page size */
   private $offset;        /** @var  int: The start record offset */
   private $order;         /** @var  string: Question sort order, either ascending or descending */
   private $order_column;  /** @var  string: The column to order by */

   /**
    * QuestionORM constructor.
    * Instantiates the database connection
    * @param $db_conn: Database connection
    */
   public function __construct($db_conn) {
      $this->db_conn = $db_conn;
      $this->page = 1;
      $this->page_size = 100;
      $this->offset = ($this->page - 1) * $this->page_size;
      $this->order = 'asc';
      $this->order_column = 'question';
   }

   /**
    * Set the paging params to be used when querying data
    * @param $page: Page number
    * @param $page_size: Size of page
    * @param $order: Ascending or descending
    * @param $order_column: Column to order by
    */
   public function setPagingParams($page, $page_size, $order, $order_column) {
      $this->page = $page;
      $this->page_size = $page_size;
      if ($this->page_size == 0) {
         $this->page_size = 1;
      }
      $this->offset = ($this->page - 1) * $this->page_size;
      $this->order = $order;
      $this->order_column = $order_column;
   }

   /** GET */

   /**
    * Search for a matching string in the fields: question, answer, distractor
    * @param $search_string : User entered search string
    * @return array: Question objects
    */
   public function searchQuestion($search_string) {
      $search_string = $this->db_conn->escape($search_string);

      $where_clause = "question LIKE '%{$search_string}%' OR 
                       answer LIKE '%{$search_string}%' OR 
                       distractor LIKE '%{$search_string}%'";

      $questions = $this->retrieveQuestions($where_clause);

      return $questions;
   }
   /**
    * Get a question based on its id
    * @param $question_id: Record id of the question
    * @return array: Question objects - a single question is still wrapped in an array for consistency
    */
   public function retrieveQuestionById($question_id) {
      $question_id = $this->db_conn->escape($question_id);

      $where_clause = "question_id = {$question_id}";
      $questions = $this->retrieveQuestions($where_clause);
      
      return $questions;
   }
   /**
    * Get all questions from the database
    * But return a limited amount based on the paging parameters
    * @param $where_clause: Optional MySql where clause
    * @return array: Array of question objects
    */
   public function retrieveQuestions($where_clause) {
      $questions = array();

      $where = "1=1";
      // set an optional where clause if specified
      if ($where_clause) {
         $where = $where_clause;
      }
      // we use GROUP_CONCAT so that we can sort by by the distractor field and still use one query to get all the data
      $query = "SELECT question_id,
                       question,
                       answer,
                       GROUP_CONCAT(distractor) as distractors,
                       GROUP_CONCAT(distractor_id) AS distractor_ids 
                  FROM DEF_QUESTION 
                     INNER JOIN LKP_QUESTION_DISTRACTOR USING(question_id)
                  WHERE {$where}
                  GROUP BY question_id 
                  ORDER BY {$this->order_column} {$this->order}
                  LIMIT {$this->offset}, {$this->page_size}";

      $records = $this->db_conn->getResultArray($query);
      /* Sample data format returned from the database
      +-------------+----------------------+---------+---------------------+---------------------+
      | question_id | question             | answer  | distractors          | distractor_ids      |
      +-------------+----------------------+---------+---------------------+---------------------+
      |         312 | What is 1047 - 5390? | -4343   | 9832                | 749                 |
      |        2334 | What is 1049 + 2776? | 3825    | 932,6868,1635,9883  | 5773,5774,5775,5776 |
      +-------------+----------------------+---------+---------------------+---------------------+ */

      // format the flat data set into json objects
      foreach($records as $row) {

         // start off each question object by setting it to the result row
         $question = $row;

         // get the distractor and their corresponding ids from the concatenated fields
         $distractors = explode(',', $question['distractors']);
         $distractor_ids = explode(',', $question['distractor_ids']);

         // remove unnecessary ids key from the question and reinitialize its distractors value as an array
         unset($question['distractor_ids']);
         $question['distractors'] = array();

         // parse and add an array of distracors to our question
         for($d_index=0; $d_index<count($distractor_ids) && $d_index<count($distractors); $d_index++) {

            $question['distractors'][] = array('distractor_id' => $distractor_ids[$d_index],
                                              'distractor' => $distractors[$d_index],
                                              'question_id' => $question['question_id']);
         }
         // add the question object to the result array
         $questions[] = $question;
      }

      return $questions;
   }

   /**
    * Get the total record count for our paging situation
    * We take into account the where clause but ignore the limit
    * @param $search_string: Optional search string - we still want to page through a searched result
    * @return int: record count
    */
   public function retrieveTotalRecordCount($search_string) {
      $where_clause = "1=1";

      if ($search_string) {
         $search_string = $this->db_conn->escape($search_string);

         $where_clause = "question LIKE '%{$search_string}%' OR 
                       answer LIKE '%{$search_string}%' OR 
                       distractor LIKE '%{$search_string}%'";
      }
      $query = "SELECT count(*) as total_pages FROM DEF_QUESTION
                  INNER JOIN LKP_QUESTION_DISTRACTOR USING(question_id)
                    WHERE {$where_clause}";

      $total_pages = $this->db_conn->getResultRow($query);
      if ($total_pages) {
         return $total_pages['total_pages'];
      } else {
         return 0;
      }
   }

   /** UPDATE */

   /**
    * Update an existing question with new data
    * @param $question: question object
    * @return mixed: updated question object
    */
   public function updateQuestion($question) {
      // try updating the question distractors first
      if (! $this->updateDistractors($question)) {
         return array();  // if the update fails
      }
      $query = "UPDATE DEF_QUESTION
                  SET question = '{$question['question']}',
                      answer = '{$question['answer']}'
                WHERE question_id = {$question['question_id']}";

      $update_result = $this->db_conn->query($query);

      if ($update_result) {
         return $question;
      } else {
         return array();  // if the update fails
      }
   }

   /**
    * Update the distractors tied to each question
    * @param $question: Question object
    * @return bool: True is successful, false if not
    */
   private function updateDistractors($question) {
      $distractor_query = "SELECT distractor_id
                              FROM LKP_QUESTION_DISTRACTOR
                           WHERE question_id = {$question['question_id']}";

      $current_distractor_ids = $this->db_conn->getResultArray($distractor_query);

      // transform the result into a flat array of ids
      $current_distractor_ids = array_map(create_function('$arg', 'return $arg["distractor_id"];'), $current_distractor_ids);
      $updated_distractor_ids = array_map(create_function('$arg', 'return $arg["distractor_id"];'), $question['distractors']);

      $ids_to_delete = array_diff($current_distractor_ids, $updated_distractor_ids);

      // delete some old distractors
      if (count($ids_to_delete)) {
         $distractor_ids = implode(',', $ids_to_delete);
         $delete_query = "DELETE FROM LKP_QUESTION_DISTRACTOR
                            WHERE distractor_id IN ({$distractor_ids})";

         $delete_result = $this->db_conn->query($delete_query);

         if (! $delete_result) {
            return false;
         }
      }
      // update current distractors and insert new ones if they do not exist
      $updated_distractors = array();
      foreach($question['distractors'] as $distractor) {
         $update_query = "INSERT INTO LKP_QUESTION_DISTRACTOR
                            (distractor_id, distractor, question_id)
                             VALUES ('{$distractor['distractor_id']}', 
                                     '{$distractor['distractor']}', 
                                     '{$distractor['question_id']}')
                          ON DUPLICATE KEY UPDATE distractor='{$distractor['distractor']}'";

         $update_result = $this->db_conn->query($update_query);

         if($this->db_conn->lastInsertId()) {
            $distractor['distractor_id'] = $this->db_conn->lastInsertId();
         };

         $updated_distractors[] = $distractor;

         if (! $update_result) {
            return false;
         }
      }
      $question['distractors'] = $updated_distractors;

      return $question;
   }

   /** INSERT */

   /**
    * Insert a new question into the database, creating a new id
    * @return mixed: New question object with a new id and default parameteres
    */
   public function insertQuestion() {
      $question = array();

      // insert the new question record
      $question_query = "INSERT INTO DEF_QUESTION
                            SET question_id = NULL";
      $result = $this->db_conn->query($question_query);

      if (! $result) {
         return array();   // if the update fails
      }
      $question['question_id'] = $this->db_conn->lastInsertId();
      // insert one new distractor record to keep the question company
      $distractor_query = "INSERT INTO LKP_QUESTION_DISTRACTOR
                              SET question_id = {$question['question_id']}";
      $result = $this->db_conn->query($distractor_query);
      if (! $result) {
         return array();   // if the update fails
      }
      // get and return the inserted question object
      $question = $this->retrieveQuestionById($question['question_id']);

      return $question;
   }

   /** DELETE */

   /**
    * Delete a question from the database
    * @param $question_id: Id of question to delete
    * @return mixed: Deleted question object
    */
   public function deleteQuestion($question_id) {
      $delete_query = "DELETE FROM DEF_QUESTION
                          WHERE question_id = {$question_id}";

      $delete_result = $this->db_conn->query($delete_query);
      // No need to delete the distractors for the questions - foreign keys will take care of that for us
      if (! $delete_result) {
         return array();   // if the deletion fails
      }

      return array('question_id' => $question_id);
   }


}