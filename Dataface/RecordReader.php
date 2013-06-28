<?php
/**
 * @brief A class for reading records from the database.  It takes a Xataface
 * query array in the constructor.  This can be iterated just like an array,
 * and the records are loaded from the database as needed.  It keeps an internal
 * buffer for the actual records so that it doesn't need to make a DB request
 * for every record.
 *
 * <h3>Usage</h3>
 *
 * <code>
 * $query = array('-table' => 'People', 'country'=>'Canada');
 * $reader = new Dataface_RecordReader($query);
 * foreach ($reader as $key=>$person){
 *     // do something with $person Dataface_Record object.
 * }
 * </code>
 *
 * @see Dataface_ResultReader for a similar API that works with Raw SQL and
 * StdClass objects.
 *
 * @created June 26, 2012
 * @author Steve Hannah <steve@weblite.ca>
 */
class Dataface_RecordReader implements Iterator {
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
	 * @type array
	 * @brief The associative array query that follows Xataface URL conventions.
	 */
	private $query = null;
	
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
	
	/**
	 * @type boolean
	 * @brief True if the loaded records should be previews.  (Previews truncate long
	 * fields.
	 */
	public $previewRecords = true;
	
	
	/**
	 * @brief Creates a new record reader.
	 * 
	 * @param array $query The associative array with the query information.
	 * @param int $bufferSize The size of the buffer.
	 * @param boolean $previewRecords Whether to return previews of records.  If this
	 * is false, then the full records will be returned, even if some of the fields contain
	 * a lot of text.
	 */
	public function __construct(array $query, $bufferSize=30, $previewRecords = true){
		$this->start = @$query['-skip'] ? intval($query['-skip']):0;
		$this->limit = @$query['-limit'] ? intval($query['-limit']):null;
		$this->query = $query;
		$this->bufferSize = $bufferSize;
		$this->buffer = null;
		$this->bufferStartPos = null;
		$this->currPos = $this->start;
		$this->previewRecords = $previewRecords;
		if ( isset($this->limit) and $this->limit < $this->bufferSize ) $this->bufferSize = $this->limit;
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
		$q = $this->query;
		$q['-skip'] = $this->bufferStartPos;
		$q['-limit'] = $this->bufferSize;
		if ( isset($this->limit) ){
			$q['-limit'] = min($this->bufferSize, $this->start+$this->limit-$this->bufferStartPos);
		}
		if ( $q['-limit'] > 0 ){
			$this->buffer = df_get_records_array($q['-table'], $q, $q['-skip'], $q['-limit'], $this->previewRecords);
		} else {
			$this->buffer = array();
		}
	
	}
	
	/**
	 * @brief Returns the current record.
	 * @returns Dataface_Record The current record.
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
