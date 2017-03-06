<?php

/**
 * Class RequestHandler
 *
 * This is a general handler
 * Logic to process all of the common API methods is included
 * only get is used in this example
 */
class RequestHandler {
   private $db_conn;
   private $uri;
   private $method;
   private $request;
   private $format;
   private $question_handler;

   /**
    * RequestHandler constructor.
    *
    * @param $db_conn: MySQL connection
    * @param $uri : The endpoint path - specifies what the user wants
    * @param $method : The request method type: one of: GET, POST, PUT, DELETE
    * @param $request : Contains the request parameters
    */
   public function __construct($db_conn, $uri, $method, $request) {
      $this->db_conn = $db_conn;
      $this->uri = $uri;
      $this->method = $method;
      $this->request = $request;
      $this->format = 'json';
      $this->question_handler = new QuestionHandler($this->db_conn);

      if ($request['page']) {
         $this->question_handler->setPagingParams($request['page'], $request['page_size'], $request['order'], $request['order_column']);
      }
   }

   /**
    * Handles the request using data specified in the constructor
    *
    * Figures out what the user wants and performs the appropriate action
    */
   public function handleRequest() {
      $status = 'ok';

      // parse the endpoint the user is hitting and get the action, store in response array
      $response = $this->parseUriAction($this->request, $this->uri);

      // try to get the requested data
      try {
         switch ($this->method) {
            case 'GET':
               $response = $this->doGet($this->request, $response);
               break;
            case 'POST':
               $response = $this->doPost($this->request, $response);
               break;
            case 'PUT':
               $response = $this->doPut($this->request, $response);
               break;
            case 'DELETE':
               $response = $this->doDelete($this->request, $response);
               break;
         }
      } catch (Exception $e) {
         $status = 'server_error';
         $response['errors'] = $e->getMessage();
      }

      // build the final response
      $response = $this->processResponse($response, $status, $this->format);

      // set the response headers
      $this->setResponseHeader($this->format, $status);

      // output the response
      echo $response;
   }

   /**
    * Decide what to do based on the URI specified and the parameters in the request
    * @param $request: Request object
    * @param $uri: un-parsed /api_project... URI of our endpoint
    * @return array: Action telling us what to do 
    */
   private function parseUriAction($request, $uri) {
      $response = array('action' => 'unspecified',
                        'questions' => array());

      $endpoints = $this->parseEndpoint($uri);

      // a search string is specified
      if ($request['pattern']) {
         $response['action'] = 'search';
      }
      // a question id is specified
      else if($endpoints[1]) {
         $response['action'] = 'single';
         $response['question_id'] = $endpoints[1];
      }

      return $response;
   }
   /**
    * Get the action parts of the url without the parameter string
    * @param $uri : The /api/... url
    * @return array: The action endpoints of the url
    */
   private function parseEndpoint($uri) {
      preg_match('/'. Config::$api_path . '\/(.*)(?:\?|(\1.*))/', $uri, $endpoint_matches);
      $endpoints = explode('/', array_pop($endpoint_matches));

      return $endpoints;
   }

   /**
    * GET
    *
    * @param $request : Request params
    * @param $response: Response scaffold containing some important parameters used here
    * @return string : Returned response
    */
   private function doGet($request, $response) {
      switch($response['action']) {
         case 'search':       // Search questions with a string patterm
            $response['questions'] = $this->question_handler->searchQuestion($request['pattern']);
            $response['total_records'] = $this->question_handler->retrieveTotalRecordCount($request['pattern']);
            break;
         case 'single':       // Get a specific question by id
            $response['questions'] = $this->question_handler->retrieveQuestionById($response['question_id']);
            $response['total_records'] = count($response['questions']);
            break;
         case 'unspecified':  // Get All questions
            $response['questions'] = $this->question_handler->retrieveQuestions();
            $response['total_records'] = $this->question_handler->retrieveTotalRecordCount();
      }
      $response['action'] = 'get ' . $response['action'];

      return $response;
   }
   /**
    * POST
    *
    * @param $request: Request params
    * @param $response: Response scaffold containing some important parameters used here
    * @return string: Returned response
    */
   private function doPost($request, $response) {
      switch($response['action']) {
         case 'single':       // Update a single question by id
            $response['questions'][] = $this->question_handler->updateQuestion(json_decode($request['question'], true));
            break;
         case 'unspecified':  // Insert a new question
            $response['questions'] = $this->question_handler->insertQuestion();
            break;
      }
      $response['action'] = 'post ' . $response['action'];

      return $response;
   }
   /**
    * DELETE
    *
    * @param $request: Request params
    * @param $response: Response scaffold containing some important parameters used here
    * @return string: Returned response
    */
   private function doDelete($request, $response) {
      switch($response['action']) {
         case 'single':       // Update a single question by id
            $response['questions'][] = $this->question_handler->deleteQuestion($response['question_id']);
            break;
      }
      $response['action'] = 'delete ' . $response['action'];

      return $response;
   }
   /**
    * PUT
    *
    * @param $request: Request params
    * @param $response: Response scaffold containing some important parameters used here
    * @return string: Returned response
    */
   private function doPut($request, $response) {
      return $response;
   }

   /**
    * Build the final response based on output data and the status
    *
    * @param $response: The requested content
    * @param $status: The response status: ok, error, or server_error
    * @param string $format: The format to encode the response in
    * @return array|string: The encoded response
    */
   private function processResponse($response, $status, $format='json') {
      if (! $response or $response['errors']) {
         $status = 'error';
      }
      if ($format == 'text') {
         $response = $response;
      }
      else {
         $response['question_count'] = count($response['questions']);
         $response['status'] = $status;
      }
      if ($format == 'json') {
         $response = json_encode($response);
      }

      return $response;
   }
   /**
    * Sets the response header based on the response type and response status
    *
    * @param string $format: Currently only JSON is used, but could also be XML or HTML
    * @param string $status: Currently only 'ok': 200 or 'error': 400.
    */
   private function setResponseHeader($format='JSON', $status='ok') {
      $status_codes = array(
         'ok' => 200,
         'error' => 400,
         'server_error' => 500
      );
      $format = strtoupper($format);

      header("Format: $format");
      header("HTTP status: $status_codes[$status]");
   }
}