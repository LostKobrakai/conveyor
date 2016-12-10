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

use ProcessWireTestEnvs\Console\Styles\Debug;
use ProcessWireTestEnvs\Console\Styles\Highlight;
use ProcessWireTestEnvs\Runner\Log;
use ProcessWireTestEnvs\Runner\Runner;
use ProcessWireTestEnvs\Runner\RunnerConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Terminal;
use Webmozart\PathUtil\Path;

class RunCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('run')
			->setDescription('Run tests in all environments')
			->setHelp("Run the configured test suite against all environments")
			->addOption(
				'pause', 'p', InputOption::VALUE_OPTIONAL,
				'Ask for user interaction between test runs'
			)
			->addOption(
				'config', null, InputOption::VALUE_REQUIRED,
				'Relative path to config file', 'conveyor.yml'
			);
	}

	/**
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute (InputInterface $input, OutputInterface $output)
	{
		Debug::apply($output);
		Highlight::apply($output);
		$width = (new Terminal())->getWidth();

		$output->writeln(sprintf(
			'<highlight>%s</highlight>',
			str_pad(' ====== ProcessWire Enviroment Tests ====== ', $width, ' ', STR_PAD_BOTH)
		), OutputInterface::VERBOSITY_NORMAL);

		$betweenAction = !$input->hasParameterOption(['-p', '--pause'])
			? null
			: function($success, $tag) use($input, $output) {
				$helper = $this->getHelper('question');
				if($input->getOption('pause') == 'failure' && $success == Runner::TEST_RESULTS_SUCCESS) return;

				$message = [
					Runner::TEST_RESULTS_SUCCESS => 'The last test run was successful.',
					Runner::TEST_RESULTS_ERROR => 'The last test run did fail.'
				][$success];
				$output->writeln($message);
				$question = new ChoiceQuestion(
					'How would you like to continue?',
					array(1 => Runner::ACTION_REPEAT, 2 => Runner::ACTION_STOP, 3 => Runner::ACTION_CONTINUE),
					Runner::ACTION_REPEAT
				);

				if (!$answer = $helper->ask($input, $output, $question)) {
					return Runner::ACTION_STOP;
				}

				return $answer;
			};

		$configFile = Path::join(getcwd(), $input->getOption('config'));

		if(!is_file($configFile))
			throw new InvalidOptionException('Config path is not a file');

		$logWriter = new ConsoleLogWriter($output, $width);
		Log::addWriter($logWriter);
		$testRunner = new Runner($configFile);
		$testRunner->run($betweenAction);

		return 0;
	}

	/**
	 * @param OutputInterface $output
	 * @param string $message
	 */
	public function info ($output, $message)
	{
		$output->writeln("<info>$message</info>", self::LOGLEVEL_INFO);
	}
}