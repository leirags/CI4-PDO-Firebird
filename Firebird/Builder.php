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

use CodeIgniter\Database\BaseBuilder;

/**
 * Builder for Firebird
 */
class Builder extends BaseBuilder
{

	/**
	 * Identifier escape character
	 *
	 * @var string
	 */
	protected $escapeChar = '';

	/**
	 * Specifies which sql statements
	 * support the ignore option.
	 *
	 * @var array
	 */
	protected $supportedIgnoreStatements = [

	];

	/**
	 * Local implementation of limit
	 *
	 * @param string  $sql
	 * @param boolean $offsetIgnore
	 *
	 * @return string
	 */
	protected function _limit(string $sql, bool $offsetIgnore = false): string
	{
		$sql_standard = false;
		/*
		log_message('error', '_limit[IN] '.$sql.
					"\n offsetIgnore:{$offsetIgnore}\n QBOffset:{$this->QBOffset}\n QBLimit:{$this->QBLimit}\n");
		*/

		if ($sql_standard === true) {

			if (false === $offsetIgnore && $this->QBOffset) {
				$sql .= (is_int($this->QBOffset) ? ' OFFSET ' . $this->QBOffset : ' OFFSET 0 ').' ROWS ';
			}
			$sql .= ' FETCH NEXT ' . $this->QBLimit . ' ROWS ONLY ';

		} else {

			// verify select if exists
			if (mb_stripos($sql, 'select') === 0)
			{
				if ($this->QBLimit) {
					$sql = mb_substr($sql, 6);
					$sql = 'SELECT FIRST '.$this->QBLimit.''.(false === $offsetIgnore && $this->QBOffset ? ' SKIP '.$this->QBOffset : '').$sql;
				}
			}
		}

		//log_message('error', '_limit[OUT] '.$sql);

		return $sql;
	}

}
