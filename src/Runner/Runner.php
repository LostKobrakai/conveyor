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


use ProcessWireTestEnvs\Helpers\Cmd;
use ProcessWireTestEnvs\Installer\Installer;
use Webmozart\PathUtil\Path;

class Runner
{
	/**
	 * Actions to be taken between test runs (-p to choose)
	 */
	const ACTION_CONTINUE = "Continue";
	const ACTION_STOP = "Stop";
	const ACTION_REPEAT = "Repeat";

	/**
	 * Test result handling
	 */
	const TEST_RESULTS_SUCCESS = true;
	const TEST_RESULTS_ERROR = false;

	/**
	 * @var Installer Responsible for installing the processwire instances
	 */
	protected $installer;

	/**
	 * Runner constructor.
	 *
	 * @param $configFile
	 */
	public function __construct($configFile)
	{
		Log::info("Loading Config file");
		$this->config = Config::fromFile($configFile);
		Log::info("Loading Installer file");
		$this->installer = new Installer($this->config);
	}

	/**
	 * Trigger the test conveyor
	 *
	 * @param callable|null $betweenAction
	 * @throws \Exception
	 */
	public function run(callable $betweenAction = null) {
		$failedTestNum = 0;
		$testNum = 0;

		foreach($this->config->testTags as $tagName) {
			do {
				$testNum++;
				list($success, $nextAction) = $this->doTestRun($tagName, $betweenAction);
				if(!$success) $failedTestNum++;
			}while ($nextAction === self::ACTION_REPEAT);
			if ($nextAction === self::ACTION_STOP) {
				break;
			}
		}

		if($failedTestNum){
			Log::always(sprintf('%d failed / %d test run(s) completed', $failedTestNum, $testNum));
			exit(1);
		} else {
			Log::always(
				sprintf(ngettext(
					'Everything went fine. %d test run completed.',
					'Everything went fine. %d test runs completed.',
					$testNum
				), $testNum)
			);
			exit(0);
		}
	}

	/**
	 * Do a single test run of installation / pre-tasks / test suite run / clean-up
	 *
	 * @param          $tagName
	 * @param callable $betweenAction
	 * @return array
	 * @throws \Exception
	 */
	protected function doTestRun ($tagName, callable $betweenAction = null)
	{
		$testResult = self::TEST_RESULTS_ERROR;
		Log::info("Installing ProcessWire $tagName");
		$processWirePath = $this->installer->installProcessWire($tagName);

		try {
			$this->copySourceFiles($processWirePath);
			$this->runBeforeTestCommands($processWirePath);

			$rel = Path::makeRelative($processWirePath, Path::getDirectory($this->config->_file));
			Log::always("Running tests for ProcessWire $tagName in $rel");

			try {
				if (!$this->runTests($processWirePath))
					throw new \Exception('Test returned non-zero response.');
				$testResult = self::TEST_RESULTS_SUCCESS;
			} catch (\Exception $e) {
				Log::error(sprintf('[%s] %s', get_class($e), $e->getMessage()));
			}

			Log::always("Finished tests for ProcessWire $tagName");
		} catch (\Exception $e) {
			// Cleanup and after that bubble further
			$this->installer->uninstallProcessWire($processWirePath);
			throw $e;
		}

		if (!$betweenAction) $betweenAction = [$this, 'determineNextAction'];
		$nextAction = call_user_func($betweenAction, $testResult, $tagName);

		Log::info(sprintf("Clean up & %s", $nextAction));

		$this->installer->uninstallProcessWire($processWirePath);

		return [$testResult !== self::TEST_RESULTS_ERROR, $nextAction];
	}

	/**
	 * Copy files to the installation
	 *
	 * @param $path
	 */
	protected function copySourceFiles($path) {
		foreach ($this->config->copySources as $pair) {
			$trailingSlash = substr(Path::normalize($pair['destination']), -1) === '/';
			$destination = Path::join($path, trim($pair['destination']));
			$destination .= $trailingSlash ? '/' : '';
			$directory = $trailingSlash ? $destination : Path::getDirectory($destination);
			$cwd = Path::getDirectory($this->config->_file);

			Cmd::run('mkdir -p', [$directory], ['cwd' => $cwd]);
			Cmd::run('cp -Rf', [trim($pair['source']), $destination], ['cwd' => $cwd]);
		}
	}

	/**
	 * Run arbitrary commands before running the tests
	 *
	 * @param $path
	 */
	protected function runBeforeTestCommands ($path)
	{
		foreach ($this->config->beforeCmds as $cmd){
			list($cmdExecutable, $args) = preg_split("/\s+/", trim($cmd) . " ", 2);
			Cmd::run($cmdExecutable, preg_split("/\s+/", $args), [
				'env' => ["PW_PATH" => $path], 'cwd' => $path
			]);
		}
	}

	/**
	 * Run the test command
	 *
	 * @param $processWirePath
	 * @return bool
	 */
	protected function runTests($processWirePath) {
		list($cmdExecutable, $args) = preg_split("/\s+/", trim($this->config->testCmd) . " ", 2);
		if (strpbrk($cmdExecutable, "/\\") !== false) {
			// cmd executable is a path, so make it absolute
			$cmdExecutable = Path::join(dirname($this->config->_file), $cmdExecutable);
		}
		$env = [
			"PW_PATH" => $processWirePath
		];
		$result = Cmd::run($cmdExecutable, preg_split("/\s+/", $args), [
			'env' => $env,
			'throw_on_error' => false,
			'print_output' => true
		]);
		return $result->exitCode === 0;
	}

	/**
	 * Default next action
	 *
	 * @return string
	 */
	protected function determineNextAction ()
	{
		return self::ACTION_CONTINUE;
	}
}