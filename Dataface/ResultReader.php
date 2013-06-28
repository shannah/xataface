<?php
/**
 * @brief A class for reading records from the database.
 *  
 *
 * <h3>Usage</h3>
 *
 * <code>
 * $query = "select * from people";
 * $reader = new Dataface_ResultReader($query, $db);
 * foreach ($reader as $key=>$person){
 *     // do something with $person Dataface_Record object.
 * }
 * </code>
 *
 * @see Dataface_RecordReader For a similar API that works with Xataface query
 * associative arrays and Dataface_Record objects.
 *
 * @created June 26, 2012
 * @author Steve Hannah <steve@weblite.ca>
 *
 */
class Dataface_ResultReader implements Iterator {

	private $sql = null;
	
	/**
	 * @type int
	 * @brief The start position in the data set to return.
	 */
	private $start = null;
	
	/**
	 * @type int
	 * @brief The limit within the data set that is the maximum.  This is 
	 * not to be confused with the buffer size.
	 */
	private $limit = null;
	
	
	/**
	 * @type int
	 * @brief The size of the record buffer.  This is maximum number of records
	 * that will be held at a time.
	 */
	private $bufferSize = 30;
	
	/**
	 * @type array(Dataface_Record)
	 * @brief The buffer that stores the loaded records.
	 */
	private $buffer = null;
	
	/**
	 * @type int
	 * @brief The start position of the current buffer relative 
	 * to the beginning of the found set.  This is zero-based and not "start"-based.
	 * I.e. The lowest this value will go is $this->start.
	 */
	private $bufferStartPos = null;
	
	/**
	 * @type int
	 * @brief The current position of the iterator.
	 */
	private $currPos = null;
	
	private $db = null;
	
	private $decorator = null;
	
	/**
	 * @brief Creates a new record reader.
	 * 
	 * @param string $sql The SQL query for the set to retrieve.
	 * @param resource $db The database resource connection.
	 * @param int $bufferSize The size of the buffer.
	 */
	public function __construct( $sql, $db,  $bufferSize=30, $decorator = null){
		$this->db = $db;
		$this->start = 0;
		$this->limit = null;
		$this->bufferSize = $bufferSize;
		$this->buffer = null;
		$this->bufferStartPos = null;
		$this->currPos = $this->start;
		$this->sql = $sql;
		$this->decorator = $decorator;
		
		if ( preg_match('/^([\s\S]+)\slimit\s+(\d+)(\s*,\s*(\d+)\s*)?\s*$/i', $this->sql, $matches) ){
			$this->sql = $matches[1];
			if ( isset($matches[4]) ){
				$this->limit = intval($matches[4]);
				$this->start = intval($matches[2]);
			} else {
				$this->start = 0;
				$this->limit = intval($matches[2]);
			}
		}
		if ( isset($this->limit) and  $this->limit < $this->bufferSize ) $this->bufferSize = $this->limit;
	}
	
	/**
	 * @brief Destructor.  Frees memory.
	 */
	public function __destruct(){
		$this->buffer = null;
	}
	
	
	/**
	 * @brief Rewinds the pointer to the beginning of the found set.
	 */
	public function rewind(){
		$this->currPos = $this->start;
		
		if ( !isset($this->bufferStartPos) ){
			// The buffer isn't set yet so we are at the beginning
			return;
		} else if ( $this->bufferStartPos > $this->start ){
			// The buffer is beyond the first buffer set
			// so we reset the buffer now
			$this->buffer = null;
			$this->bufferStartPos = null;
		} else {
			// The buffer is not beyond the first set
			// so the buffer is good.
		}
		
	}
	
	public function getQuery($start, $limit){
		return $this->sql.' limit '.$start.', '.$limit;
	}
	
	/**
	 * @brief Loads the buffer according to the current position of the found set.
	 */
	private function loadBuffer(){
		if ( $this->currPos >= $this->bufferStartPos + $this->bufferSize ){
			$this->bufferStartPos += $this->bufferSize;
		} else if ( !isset($this->bufferStartPos) ){
			$this->bufferStartPos = $this->start;
		} else {
			return;
		}
		$q = array();
		$q['-skip'] = $this->bufferStartPos;
		$q['-limit'] = $this->bufferSize;
		if ( isset($this->limit ) ){
			$q['-limit'] = min($this->bufferSize, $this->start+$this->limit-$this->bufferStartPos);
		}
		if ( $q['-limit'] > 0 ){
			$this->buffer = array();
			$res = mysql_query($this->getQuery($q['-skip'], $q['-limit']), $this->db);
			if ( !$res ) throw new Exception(mysql_error($this->db));
			
			while ($row = mysql_fetch_object($res) ){
				if ( isset($this->decorator) and is_callable($this->decorator) ){
					$row = call_user_func($this->decorator, $row);
				}
				$this->buffer[] = $row;
			}
			@mysql_free_result($res);
		} else {
			$this->buffer = array();
		}
	
	}
	
	/**
	 * @brief Returns the current record.
	 * @returns StdClass The current record.
	 */
	public function current(){
		$this->loadBuffer();
		return $this->buffer[$this->currPos-$this->bufferStartPos];
	}
	
	/**
	 * @brief Returns the index of the current position.
	 * @returns int The current position within the foundset.
	 */
	public function key(){
		return $this->currPos;
	}
	
	/**
	 * @brief Increments the current position to the next position.
	 */
	public function next(){
		++$this->currPos;
	}
	
	/**
	 * @brief Checks to see if the current position is valid.
	 */
	public function valid(){
		$this->loadBuffer();
		return isset($this->buffer[$this->currPos-$this->bufferStartPos]);
	}
}
