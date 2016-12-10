<?php
/*
 * The MIT License
 *
 * Copyright 2016 Richard Jedlička <jedlicka.r@gmail.com> (http://uiii.cz)
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
 */
namespace ProcessWireTestEnvs\Runner;

/**
 * Logger class, where log writers can hook themselves in
 *
 * @package ProcessWireTestEnvs\Runner
 */
class Log
{
	/**
	 * @var array Array of obj's with a method write($message, $level)
	 */
	protected static $writers = [];

	public static function always($message) {
		self::write(0, $message);
	}

	public static function error($message) {
		self::write(1, $message, "[ERROR]");
	}

	public static function warn($message) {
		self::write(2, $message, "[WARNING]");
	}

	public static function info($message) {
		self::write(3, $message, "[INFO]");
	}

	public static function debug($message, $debugLevel = 0) {
		self::write(4 + $debugLevel, $message, "[DEBUG]");
	}

	/**
	 * Log the messages via all the registered writers
	 *
	 * @param        $level
	 * @param        $message
	 * @param string $prefix
	 */
	protected static function write($level, $message, $prefix = "") {
		foreach (self::$writers as $writer) {
			if ($prefix) {
				$prefix .= " ";
			}
			$writer->write(sprintf("%s%s", $prefix, $message), $level);
		}
	}

	/**
	 * Add a class writer
	 *
	 * @param $writer
	 */
	public static function addWriter ($writer)
	{
		self::$writers[] = $writer;
	}
}