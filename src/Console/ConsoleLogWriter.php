<?php
/*
 * The MIT License
 *
 * Copyright 2016 Benjamin Milde <benni@kobrakai.de> (https://kobrakai.de)
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
namespace ProcessWireTestEnvs\Console;


use Symfony\Component\Console\Output\OutputInterface;

class ConsoleLogWriter
{
	/**
	 * @var OutputInterface
	 */
	private $output;

	/**
	 * @var int Terminalwidth
	 */
	private $width;

	public function __construct (OutputInterface $output, $width = 80)
	{
		$this->output = $output;
		$this->width = $width;
	}

	public function write ($message, $level)
	{
		$verbosity = [
			0 => OutputInterface::VERBOSITY_NORMAL,
			1 => OutputInterface::VERBOSITY_NORMAL,
			2 => OutputInterface::VERBOSITY_VERBOSE,
			3 => OutputInterface::VERBOSITY_VERY_VERBOSE,
			4 => OutputInterface::VERBOSITY_DEBUG,
		][$level];

		$style = [
			0 => '<highlight>%s%s</highlight>',
			1 => '<error>%s%s</error>',
			2 => '<error>%s%s</error>',
			3 => '<info>%s%s</info>',
			4 => '<debug>%s%s</debug>',
		][$level];

		$prefix = '📦 >';

		$message = $level == 0 ? ' ' . str_pad($message, $this->width - strlen($prefix) + 2) : $message;

		$this->output->writeln(sprintf($style, $prefix, $message), $verbosity);
	}
}