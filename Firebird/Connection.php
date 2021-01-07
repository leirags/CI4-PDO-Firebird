<?php

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014-2019 British Columbia Institute of Technology
 * Copyright (c) 2019-2020 CodeIgniter Foundation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package    CodeIgniter
 * @author     CodeIgniter Dev Team
 * @copyright  2019-2020 CodeIgniter Foundation
 * @license    https://opensource.org/licenses/MIT	MIT License
 * @link       https://codeigniter.com
 * @since      Version 4.0.0
 * @filesource
 */

namespace CodeIgniter\Database\Firebird;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Database\Exceptions\DatabaseException;

/**
 * Connection for Firebird
 */
class Connection extends BaseConnection implements ConnectionInterface
{
	/**
	 * Database driver
	 *
	 * @var string
	 */
	public $DBDriver = 'Firebird';
	/**
	 * DELETE hack flag
	 *
	 * Whether to use the FIREBIRD "delete hack" which allows the number
	 * of affected rows to be shown. Uses a preg_replace when enabled,
	 * adding a bit more processing to all queries.
	 *
	 * @var boolean
	 */
	public $deleteHack = true;
	// --------------------------------------------------------------------
	/**
	 * Identifier escape character
	 *
	 * @var string
	 */
	public $escapeChar = '';
	// --------------------------------------------------------------------
	/**
	 * Connect to the database.
	 *
	 * @param boolean $persistent
	 *
	 * @return mixed
	 * @throws \CodeIgniter\Database\Exceptions\DatabaseException
	 */
	public function connect(bool $persistent = false)
	{
		if (empty($this->DSN)) { $this->buildDSN(); }

		// verify firebird if exists
		if (mb_strpos($this->DSN, 'firebird:') === 0)
		{
			// $this->DSN = mb_substr($this->DSN, 9);
			// throw error must be firebird.
		}

		$options = array(
			// \PDO::ATTR_AUTOCOMMIT => FALSE, Not Work... throw an error
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			// \PDO::ATTR_ERRMODE => \PDO::ERRMODE_SILENT, // Ignore all errors and continue...the execution
			// \PDO::ATTR_ERRMODE => \PDO::ERRMODE_WARNING,
		);

		try
		{
			$this->connID = new \PDO($this->DSN, $this->username, $this->password, $options);

			return $this->connID;
		}
		catch (\CodeIgniter\Database\Exceptions\DatabaseException $e)
		{
			// Clean sensitive information from errors.
			$msg = $e->getMessage();

			$msg = str_replace($this->username, '****', $msg);
			$msg = str_replace($this->password, '****', $msg);

			$msg .= $this->DSN;

			throw new \CodeIgniter\Database\Exceptions\DatabaseException($msg, (int)$e->getCode(), $e);
		}

		return false;
	}

	//--------------------------------------------------------------------

	/**
	 * Keep or establish the connection if no queries have been sent for
	 * a length of time exceeding the server's idle timeout.
	 *
	 * @return void
	 */
	public function reconnect()
	{
		$this->close();
		$this->initialize();
	}

	//--------------------------------------------------------------------

	/**
	 * Close the database connection.
	 *
	 * @return void
	 */
	protected function _close()
	{
		$this->connID->close();
	}

	//--------------------------------------------------------------------

	/**
	 * Select a specific database table to use.
	 *
	 * @param string $databaseName
	 *
	 * @return boolean
	 */
	public function setDatabase(string $databaseName): bool
	{
		return false;
	}

	//--------------------------------------------------------------------

	/**
	 * Returns a string containing the version of the database being used.
	 *
	 * @return string
	 */
	public function getVersion(): string
	{
		if (isset($this->dataCache['version']))
		{
			return $this->dataCache['version'];
		}

		if (empty($this->firebird))
		{
			$this->initialize();
		}

		return $this->dataCache['version'] = $this->firebird->server_info;
	}

	//--------------------------------------------------------------------

	/**
	 * Executes the query against the database.
	 *
	 * @param string $sql
	 *
	 * @return mixed
	 */
	public function execute(string $sql)
	{
		try
		{
			return $this->connID->query(
				$this->prepQuery($sql)
			);
		}
		catch (\PDOException $e)
		{
			throw new DatabaseException(
				$this->connID->errorCode()." ".
				"Failed to execute query:\n" . $sql . "\nWith Error:\n".$e->getMessage(),
				(int)$e->getMessage(),
				$e
			);
			//log_message('error', 'Firebird Query : '.$e);
			if ($this->DBDebug) {
				throw $e;
			}
		}
		return false;
	}

	//--------------------------------------------------------------------

	/**
	 * Prep the query
	 *
	 * If needed, each database adapter can prep the query string
	 *
	 * @param string $sql an SQL query
	 *
	 * @return string
	 */
	protected function prepQuery(string $sql): string
	{
		// FIREBIRD_affected_rows() returns 0 for "DELETE FROM TABLE" queries. This hack
		// modifies the query so that it a proper number of affected rows is returned.
		if ($this->deleteHack === true && preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $sql))
		{
			return trim($sql) . ' WHERE 1=1';
		}

		//log_message('error', 'Prep: '.$sql);

		return $sql;
	}

	//--------------------------------------------------------------------

	/**
	 * Returns the total number of rows affected by this query.
	 *
	 * @return integer
	 */
	public function affectedRows(): int
	{
		//log_message('error', 'affectedRows');
		return is_object($this->resultID) ? $this->resultID->rowCount() : 0;
	}

	//--------------------------------------------------------------------

	/**
	 * Platform-dependant string escape
	 *
	 * @param  string $str
	 * @return string
	 */
	protected function _escapeString(string $str): string
	{
		return str_replace("'", "''", remove_invisible_characters($str, false));
	}

	//--------------------------------------------------------------------

	/**
	 * Escape Like String Direct
	 * There are a few instances where FIREBIRD queries cannot take the
	 * additional "ESCAPE x" parameter for specifying the escape character
	 * in "LIKE" strings, and this handles those directly with a backslash.
	 *
	 * @param  string|string[] $str Input string
	 * @return string|string[]
	 */
	public function escapeLikeStringDirect($str)
	{
		//log_message('error', 'escapeLikeStringDirect');

		if (is_array($str))
		{
			foreach ($str as $key => $val)
			{
				$str[$key] = $this->escapeLikeStringDirect($val);
			}

			return $str;
		}

		$str = $this->_escapeString($str);

		// Escape LIKE condition wildcards
		return str_replace([
			$this->likeEscapeChar,
			'%',
			'_',
		], [
			'\\' . $this->likeEscapeChar,
			'\\' . '%',
			'\\' . '_',
		], $str
		);
	}

	//--------------------------------------------------------------------

	/**
	 * Generates the SQL for listing tables in a platform-dependent manner.
	 * Uses escapeLikeStringDirect().
	 *
	 * @param boolean $prefixLimit
	 *
	 * @return string
	 */
	protected function _listTables(bool $prefixLimit = false): string
	{
		// log_message('error', '_listTables');
		$sql = 'SELECT "RDB$RELATION_NAME" AS "TABLE" FROM "RDB$RELATIONS"
						WHERE "RDB$RELATION_NAME" NOT LIKE \'RDB$%\'
						AND "RDB$RELATION_NAME" NOT LIKE \'SEC$%\'
						AND "RDB$RELATION_NAME" NOT LIKE \'MON$%\'';

		if ($prefixLimit !== false && $this->DBPrefix !== '')
		{
			return $sql.' AND "RDB$RELATION_NAME" LIKE \''.$this->escape_like_str($this->DBPrefix)."%' "
				.sprintf($this->_like_escape_str, $this->_like_escape_chr);
		}

		return $sql;
	}

	//--------------------------------------------------------------------

	/**
	 * Generates a platform-specific query string so that the column names can be fetched.
	 *
	 * @param string $table
	 *
	 * @return string
	 */
	protected function _listColumns(string $table = ''): string
	{
		//log_message('error', '_listColumns');
		$table = $this->protectIdentifiers($table, true, null, false);

		$table_columns = 'SELECT "rfields"."RDB$FIELD_NAME" AS "name",
				CASE "fields"."RDB$FIELD_TYPE"
					WHEN 7 THEN \'SMALLINT\'
					WHEN 8 THEN \'INTEGER\'
					WHEN 9 THEN \'QUAD\'
					WHEN 10 THEN \'FLOAT\'
					WHEN 11 THEN \'DFLOAT\'
					WHEN 12 THEN \'DATE\'
					WHEN 13 THEN \'TIME\'
					WHEN 14 THEN \'CHAR\'
					WHEN 16 THEN \'INT64\'
					WHEN 27 THEN \'DOUBLE\'
					WHEN 35 THEN \'TIMESTAMP\'
					WHEN 37 THEN \'VARCHAR\'
					WHEN 40 THEN \'CSTRING\'
					WHEN 261 THEN \'BLOB\'
					ELSE NULL
				END AS "type",
				"fields"."RDB$FIELD_LENGTH" AS "max_length",
				"rfields"."RDB$DEFAULT_VALUE" AS "default"
			FROM "RDB$RELATION_FIELDS" "rfields"
				JOIN "RDB$FIELDS" "fields" ON "rfields"."RDB$FIELD_SOURCE" = "fields"."RDB$FIELD_NAME"
			WHERE "rfields"."RDB$RELATION_NAME" = \''.$table.'\'
			ORDER BY "rfields"."RDB$FIELD_POSITION"';

		//log_message('error', $table_columns );

		return $table_columns;

	}

	//--------------------------------------------------------------------

	/**
	 * Returns an array of objects with field data
	 *
	 * @param  string $table
	 * @return \stdClass[]
	 * @throws DatabaseException
	 */
	public function _fieldData(string $table): array
	{
		//log_message('error', '_fieldData');
		if (($query = $this->query( $this->_listColumns($table))) === false)
		{
			throw new DatabaseException(lang('Database.failGetFieldData'));
		}
		$query = $query->getResultObject();

		//log_message('error', print_r($query, true) );

		return $query;
	}

	//--------------------------------------------------------------------

	/**
	 * Returns an array of objects with index data
	 *
	 * @param  string $table
	 * @return \stdClass[]
	 * @throws DatabaseException
	 * @throws \LogicException
	 */
	public function _indexData(string $table): array
	{
		//log_message('error', '_indexData');

		$sql = 'SELECT RDB$INDEX_NAME as INDEX_NAME, RDB$RELATION_NAME as RELATION_NAME,
						RDB$INDEX_ID as INDEX_ID, RDB$UNIQUE_FLAG as UNIQUE_FLAG,
						RDB$DESCRIPTION as DESCRIPTION, RDB$SEGMENT_COUNT as SEGMENT_COUNT,
						RDB$INDEX_INACTIVE as INDEX_INACTIVE, RDB$INDEX_TYPE as INDEX_TYPE,
						RDB$FOREIGN_KEY as FOREIGN_KEY, RDB$SYSTEM_FLAG as SYSTEM_FLAG,
						RDB$EXPRESSION_BLR as EXPRESSION_BLR, RDB$EXPRESSION_SOURCE as EXPRESSION_SOURCE,
						RDB$STATISTICS as STATISTICS
		 				FROM RDB$INDICES
						WHERE (RDB$SYSTEM_FLAG is null or RDB$SYSTEM_FLAG = 0) ';

		if (! empty($table)) $sql .= 'AND RDB$RELATION_NAME = \''.$table.'\' ';

		$sql .='ORDER BY RDB$FOREIGN_KEY NULLS FIRST';

		//log_message('error', '_indexData SQL:'.$sql);

		if (($query = $this->query($sql)) === false)
		{
			throw new DatabaseException(lang('Database.failGetIndexData'));
		}
		$query = $query->getResultObject();

		//log_message('error', print_r($query, true) );

		return $query;

	}

	//--------------------------------------------------------------------

	/**
	 * Returns an array of objects with Foreign key data
	 *
	 * @param  string $table
	 * @return \stdClass[]
	 * @throws DatabaseException
	 */
	public function _foreignKeyData(string $table): array
	{
		//log_message('error', '_foreignKeyData');

		$sql = 'SELECT RDB$INDEX_NAME as INDEX_NAME, RDB$RELATION_NAME as RELATION_NAME,
						RDB$INDEX_ID as INDEX_ID, RDB$UNIQUE_FLAG as UNIQUE_FLAG,
						RDB$DESCRIPTION as DESCRIPTION, RDB$SEGMENT_COUNT as SEGMENT_COUNT,
						RDB$INDEX_INACTIVE as INDEX_INACTIVE, RDB$INDEX_TYPE as INDEX_TYPE,
						RDB$FOREIGN_KEY as FOREIGN_KEY, RDB$SYSTEM_FLAG as SYSTEM_FLAG,
						RDB$EXPRESSION_BLR as EXPRESSION_BLR, RDB$EXPRESSION_SOURCE as EXPRESSION_SOURCE,
						RDB$STATISTICS as STATISTICS
		 				FROM RDB$INDICES
						WHERE (RDB$SYSTEM_FLAG is null or RDB$SYSTEM_FLAG = 0) ';

		if (! empty($table)) $sql .= 'AND RDB$RELATION_NAME = \''.$table.'\' ';

		$sql .='ORDER BY RDB$FOREIGN_KEY NULLS FIRST';

		//log_message('error', '_indexData SQL:'.$sql);

		if (($query = $this->query($sql)) === false)
		{
			throw new DatabaseException(lang('Database.failGetIndexData'));
		}
		$query = $query->getResultObject();

		//log_message('error', print_r($query, true) );

		return $query;
	}

	//--------------------------------------------------------------------

	/**
	 * Returns platform-specific SQL to disable foreign key checks.
	 *
	 * @return string
	 */
	protected function _disableForeignKeyChecks()
	{
		return '';
	}

	//--------------------------------------------------------------------

	/**
	 * Returns platform-specific SQL to enable foreign key checks.
	 *
	 * @return string
	 */
	protected function _enableForeignKeyChecks()
	{
		return '';
	}

	//--------------------------------------------------------------------

	/**
	 * Returns the last error code and message.
	 *
	 * Must return an array with keys 'code' and 'message':
	 *
	 *  return ['code' => null, 'message' => null);
	 *
	 * @return array
	 */
	public function error(): array
	{
		if (! empty($this->connID->connect_errno))
		{
			return [
				'code'    => $this->connID->connect_errno,
				'message' => $this->connID->connect_error,
			];
		}

		return [
			'code'    => $this->connID->errno,
			'message' => $this->connID->error,
		];
	}

	//--------------------------------------------------------------------

	/**
	 * Build a DSN from the provided parameters
	 *
	 * @return void
	 */
	protected function buildDSN()
	{
		$DSN2 = 'firebird:';

		$this->hostname === '' || $DSN2 .= "dbname={$this->hostname}";

		if (! empty($this->port) && ctype_digit($this->port))
		{
			$DSN2 .= ":{$this->port}";
		}

		$this->database === '' || $DSN2 .= ":{$this->database}";

		if (! empty($this->charset))
		{
			$DSN2 .= ";charset:{$this->charset}";
		}

		$this->DSN = rtrim($DSN2);
	}

	//--------------------------------------------------------------------

	/**
	 * Insert ID
	 *
	 * @return integer
	 */
	public function insertID($name = null): int
	{
		return $this->connID->lastInsertId($name);
	}

	//--------------------------------------------------------------------

	/**
	 * Begin Transaction
	 *
	 * @return boolean
	 */
	protected function _transBegin(): bool
	{
		$this->connID->autocommit(false);

		return $this->connID->beginTransaction();
	}

	//--------------------------------------------------------------------

	/**
	 * Commit Transaction
	 *
	 * @return boolean
	 */
	protected function _transCommit(): bool
	{
		if ($this->connID->commit())
		{
			$this->connID->autocommit(true);

			return true;
		}

		return false;
	}

	//--------------------------------------------------------------------

	/**
	 * Rollback Transaction
	 *
	 * @return boolean
	 */
	protected function _transRollback(): bool
	{
		if ($this->connID->rollBack())
		{
			$this->connID->autocommit(true);

			return true;
		}

		return false;
	}
	//--------------------------------------------------------------------
}
