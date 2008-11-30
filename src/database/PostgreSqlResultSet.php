<?php
/**
 * This class is the PostgreSQL implementation of a result set. This implementation allows access to
 * the rows either through iteration or direct array access.
 */
class PostgreSqlResultSet extends ResultSetArray {
	/**
	 * Caches the number of rows in this result set, after the first call to <code>count()</code>.
	 * @var int
	 */
	private $numRows;

	/**
	 * Creates a new result set backed by the supplied PostgreSQL result resource.
	 * 
	 * @param result $result Result resource.
	 */
	public function __construct ($conn, $result) {
		$this->conn     = $conn;
		$this->result   = $result;
		$this->position = 0;
	}

	/**
	 * Returns the row at the specified offset as an associative array.
	 *
	 * @param int $offset Row to retrieve.
	 * @return array      The row found.
	 */
	public function offsetGet ($offset) {
		return pg_fetch_assoc($this->result, $offset);
	}

	/**
	 * Returns the number of rows in this result set.
	 *
	 * @throws Exception If an error occured while retrieving the row count.
	 * @return int       Number of rows.
	 */
	public function count () {
		if (!isset($this->numRows)) {
			if (($this->numRows = pg_num_rows($this->result)) == -1) {
				throw new Exception('Error while trying to get number of rows in result set');
			}
		}
		return $this->numRows;
	}

	/**
	 * Returns the number of rows affected by the query which produced this result. Only relevant
	 * after INSERT, UPDATE or DELETE queries.
	 *
	 * @return int Number of affected rows.
	 */
	public function numAffectedRows () {
		return pg_affected_rows($this->result);
	}

	/**
	 * Converts the supplied value, as returned from the database, to a PHP data type. Specifically,
	 * textual boolean values are converted to true PHP boolean values.
	 * 
	 * @param mixed $value Value as returned from the database.
	 * @return mixed       The value converted.
	 */
	public function convertType ($value) {
		if ($value == 't' || $value == 'f') {
			return $value == 't' ? true : false;
		}
		return $value;
	}
}
?>
