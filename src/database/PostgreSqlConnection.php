<?php
require(ROOT . '/database/PostgreSqlResultSet.php');

/**
 * This class extends the <code>DatabaseConnection</code> class to support connections to a
 * PostgreSQL database. This class may also implement PostgreSQL-specific features. This
 * implementation relies on a configuration section in production.ini (or any other environment
 * configuration) like the following:
 *
 *   [database]
 *   type = "PostgreSql"
 *   host = ""			; Hostname of database server, omit to use local Unix domain socket
 *   port = ""			; Port, omit to use default of 5432
 *   name = ""			; Name of database to connect to
 *   username = ""		; PostgreSQL username
 *   password = ""		; Password of PostgreSQL user
 *   autocommit = Off	; Optional: Defaults to Off if not specified
 *
 * A configuration section named 'database' as shown specifies the default database. Others
 * can be configured with sections named 'database.slave1' or similar, where 'slave1' is the
 * custom name you specify to DatabaseFactory to obtain a connection:
 *   $conn = DatabaseFactory::getConnection('slave1');
 *
 * See the class documentation in <code>DatabaseConnection</code> for general documentation of
 * using database connection classes.
 */
class PostgreSqlConnection extends DatabaseConnection {
	private $host;
	private $port;
	private $username;
	private $password;
	private $database;
	private $autocommit;

	/**
	 * Creates a new instance of this class. No connection to the database will be established before
	 * calling <code>connect()</code> or executing the first query.
	 *
	 * @param array $conf Database configuration.
	 */
	public function __construct ($conf) {
		$this->host       = isset($conf['host']) ? $conf['host'] : null;
		$this->port       = isset($conf['port']) ? $conf['port'] : null;
		$this->username   = $conf['username'];
		$this->password   = $conf['password'];
		$this->database   = $conf['name'];
		$this->autocommit = isset($conf['autocommit']) ? $conf['autocommit'] : false;
	}

	/**
	 * Connects to the PostgreSQL database.
	 * XXX: Do we want to support pg_pconnect()?
	 */
	public function connect () {
		$host = $this->host === null ? '' : 'host=' . $this->host;
		$port = $this->port === null ? '' : 'port=' . $this->port;
		$connectionString = "$host $port dbname={$this->database} user={$this->username} "
				. "password={$this->password}";
		$this->connection = pg_connect($connectionString);
		return true;
	}

	/**
	 * Begins a new transaction. If autocommit is enabled, this turns autocommit off for the
	 * rest of this request.
	 *
	 * @return bool <code>TRUE</code> if a transaction was started, <code>FALSE</code> if not (i.e.
	 *              the connection is in an error state).
	 */
	public function begin () {
		if (!$this->connection) {
			$this->connect();
		}
		if ($this->autocommit) {
			$this->autocommit = false;
		}

		if (pg_transaction_status($this->connection) !== PGSQL_TRANSACTION_INERROR) {
			pg_query($this->connection, 'BEGIN');
			return true;
		}
		return false;
	}

	/**
	 * Commits or rolls back the active transaction, if any. The transaction is rolled back if in an
	 * invalid state, else it is commited. If autocommit mode is enabled, it returns at once.
	 *
	 * @return bool <code>TRUE</code> if transaction was commited, <code>FALSE</code> if rolled back.
	 */
	public function commit () {
		$status = pg_transaction_status($this->connection);

		if (!$status
				|| $status === PGSQL_TRANSACTION_UNKNOWN
				|| $status === PGSQL_TRANSACTION_IDLE
				|| $this->autocommit)
			return;

		if ($status === PGSQL_TRANSACTION_INERROR) {
			pg_query($this->connection, 'ROLLBACK');
			return false;
		}

		pg_query($this->connection, 'COMMIT');
		return true;
	}

	/**
	 * Rolls back the active transaction.
	 */
	public function rollback () {
		$status = pg_transaction_status($this->connection);

		if (!$status
				|| $status === PGSQL_TRANSACTION_UNKNOWN
				|| $status === PGSQL_TRANSACTION_IDLE
				|| $this->autocommit)
			return;

		if (!$this->autocommit) {
			pg_query($this->connection, 'ROLLBACK');
		}
	}

	/**
	 * Sends a query to the database after escaping and interpolating the supplied parameters, and
	 * returns the result set.
	 *
	 * If a connection to the database is not yet established, <code>connect()</code> is called
	 * implicitly. The same is true of transactions; if a transaction has not yet been started on the
	 * connection, <code>begin()</code> is called.
	 *
	 * @param string $query The query to execute.
	 * @param mixed $params Parameters to interpolate into query.
	 * @throws Exception    Upon an error when executing the query.
	 * @return ResultSet    Representing the query results. Implementation-specific.
	 */
	public function query ($query, $params = array()) {
		$this->prepareConnection();

		// Interpolate any parameters into query
		$preparedQuery = $this->prepareQuery($query, $params);

		if (pg_send_query($this->connection, $preparedQuery)) {
			$resultResource = pg_get_result($this->connection);
			$resultStatus = pg_result_status($resultResource);

			if ($resultStatus !== PGSQL_FATAL_ERROR) {
				$this->resultSet = new PostgreSqlResultSet($this, $resultResource);
				return $this->resultSet;
			}

			$this->rollback();
			throw new SqlException(pg_result_error($resultResource), $preparedQuery,
				(int) pg_result_error_field($resultResource, PGSQL_DIAG_SQLSTATE));
		}

		throw new DatabaseException('Query could not be sent to the database. Connection lost?');
	}

	/**
	 * Sends several queries (separated by semicolons) to the database, and returns the number
	 * of rows affected.
	 *
	 * This method doesn't support parameters, and thus will not automatically protect the
	 * queries from SQL injection. For dynamic queries with user-supplied values,
	 * <code>query()</code> should be used.
	 *
	 * @param string $query The query to execute.
	 * @throws Exception    Upon an error when executing the query.
	 * @return int          Number of rows affected by the queries.
	 */
	public function batchQuery ($query) {
		$this->prepareConnection();

		if (!($result = pg_query($this->connection, $query))) {
			$lastError = pg_last_error();
			$this->rollback();
			throw new SqlException($lastError, $query);
		}

		return pg_affected_rows($result);
	}

	/**
	 * Inserts records into $table from $rows, using the PostgreSQL specific COPY FROM feature.
	 *
	 * $rows must be an array, where each element can either be a string or array. With string
	 * elements, each element represents a row where columns are separated by the $delimiter.
	 * No escaping are done on the data in this case, and thus this functions as a wrapper
	 * around pg_copy_from.
	 *
	 * When each value in $rows is an array, each elements represents a column value. Escaping
	 * and translation of nulls is here done.
	 *
	 * @param string $table     Name of table to insert rows into.
	 * @param array $rows       Array with data.
	 * @param string $delimiter Token for delimiting columns. Defaults to \t (TAB).
	 * @param string $nulls     How SQL NULL values are represented. Defaults to
	 *                          \\N (for correct escaping, equals \N).
	 */
	public function copyFrom ($table, $rows, $delimiter = "\t", $null = "\\N") {
		$this->prepareConnection();

		if (is_array($rows) && ($first = current($rows)) !== false && is_array($first)) {
			$delims = array_fill(0, count($first), $delimiter);
			$nulls = array_fill(0, count($first), $null);
			$escapedRows = array();

			foreach ($rows as $row) {
				$r = array_map(array($this, 'escapeCopyValue'), $row, $delims, $nulls);
				$escapedRows[] = implode($delimiter, $r);
			}

			$rows = $escapedRows;
		}

		$result = pg_copy_from($this->connection, $table, $rows, $delimiter, $null);

		if (!$result) {
			$lastError = pg_last_error();
			$this->rollback();

			$query = "COPY $table FROM \n" . implode("\n", $rows);
			throw new SqlException($lastError, $query);
		}

		return $result;
	}

	/**
	 * Escapes a value to make it safe for use in SQL queries.
	 *
	 * Converts null to SQL NULL string, boolean values to accepted string representations, and
	 * escapes necessary characters in strings. Pure numeric values are simply returned as is.
	 *
	 * @param mixed $value Data value to escape and/or convert.
	 * @return string      The value prepared for insertion into a SQL query.
	 */
	protected function escapeValue ($value) {
		if ($value === null) {
			return 'NULL';
		}
		if (is_bool($value)) {
			return ($value ? 'true' : 'false');
		}
		if ($this->isPureNumber($value)) {
			return $value;
		}
		if (is_array($value)) {
			// Check if regular sequential array
			if (array_keys($value) === range(0, count($value) - 1)) {
				return 'ARRAY['
					. implode(', ', array_map(array($this, 'escapeValue'), $value))
					. ']';
			}
			// Else it's an associative array we should rather JSON encode to PostgreSQL
			else {
				return "'" . json_encode($value) . "'";
			}
		}

		return "'" . pg_escape_string($value) . "'";
	}

	/**
	 * Escapes a value for insertion into an SQL COPY string.
	 *
	 * This is special compared to escapeValue(), as SQL COPY has its own escaping rules.
	 */
	private function escapeCopyValue ($value, $delimiterChar, $nullValue) {
		if ($value === null) {
			return $nullValue;
		}

		if (is_bool($value)) {
			return ($value ? 'true' : 'false');
		}

		if (is_array($value)) {
			$innerDelims = array_fill(0, count($value), $delimiterChar);
			$innerNulls = array_fill(0, count($value), $nullValue);

			return '{' . implode(', ',
				array_map(
					array($this, 'escapeCopyValue'), $value, $innerDelims, $innerNulls)
			) . '}';
		}

		return strtr($value, array(
			"\n" => '\\\n',
			"\r" => '\\\r',
			'}' => '\\}',
			'{' => '\\{',
			'"' => '\\"',
			'\\' => '\\\\',
			$delimiterChar => '\\'.$delimiterChar));
	}

	/**
	 * Prepares the connection before a query is sent.
	 */
	private function prepareConnection () {
		if (!$this->connection || pg_ping($this->connection) === false) {
			$this->connect();
		}

		if (!$this->autocommit) {
			$status = pg_transaction_status($this->connection);
			if ($status === PGSQL_TRANSACTION_UNKNOWN || $status === PGSQL_TRANSACTION_INERROR) {
				// We can't query when the connection status is bad
				return false;
			}
			else if ($status === PGSQL_TRANSACTION_IDLE) {
				// No transaction yet started, let's begin one
				$this->begin();
			}
		}
	}
}
?>
