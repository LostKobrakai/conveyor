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
namespace ProcessWireTestEnvs\Runner;


use ProcessWireTestEnvs\Helpers\Arr;
use stdClass;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

class Config implements ConfigurationInterface
{

	/**
	 * Generates the configuration tree builder.
	 *
	 * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
	 */
	public function getConfigTreeBuilder ()
	{
		$treeBuilder = new TreeBuilder();
		$rootNode = $treeBuilder->root('runner');

		$rootNode
			->children()
				->scalarNode('tmpDir')
					->info('The folder to place downloads and the test installations in.')
					->defaultValue('.conveyor')
					->cannotBeEmpty()
				->end()
				->arrayNode('db')
					->children()
						->scalarNode('host')
							->info('The folder to place downloads and the test installations in.')
							->defaultValue('localhost')
							->cannotBeEmpty()
						->end()
						->integerNode('port')
							->min(0)->max(65535)
							->defaultValue(3306)
							->isRequired()
						->end()
						->scalarNode('user')
							->defaultValue('root')
							->cannotBeEmpty()
						->end()
						->scalarNode('pass')
							->defaultValue('')
						->end()
						->scalarNode('name')
							->defaultValue('pw_conveyor')
						->end()
					->end()
					->addDefaultsIfNotSet()
				->end()
				->arrayNode('testTags')
					->prototype('scalar')
					->end()
					->requiresAtLeastOneElement()
				->end()
				->arrayNode('copySources')
					->prototype('array')
						->children()
							->scalarNode('source')
								->cannotBeEmpty()
							->end()
							->scalarNode('destination')
								->cannotBeEmpty()
							->end()
						->end()
					->end()
				->end()
				->arrayNode('beforeCmds')
					->prototype('scalar')
					->end()
				->end()
				->scalarNode('testCmd')
					->cannotBeEmpty()
					->isRequired()
				->end()
			->end();

		return $treeBuilder;
	}

	/**
	 * Read the configuration file and return it's data
	 *
	 * @param $configFile
	 * @return stdClass
	 */
	public static function fromFile ($configFile)
	{
		$parser = [
			'yml' => [Yaml::class, 'parse'],
			'yaml' => [Yaml::class, 'parse'],
			'json' => function($content){ return json_decode($content, true); }
		][pathinfo($configFile, PATHINFO_EXTENSION)];

		$config = call_user_func($parser, file_get_contents($configFile));

		$processor = new Processor();
		$processedConfiguration = $processor->processConfiguration(new static, [$config]);
		$processedConfiguration['_file'] = $configFile;

		return Arr::toStdClass($processedConfiguration);
	}
}