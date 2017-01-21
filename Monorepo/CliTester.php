<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Adeira\Monorepo;

use Tester\Environment;
use Tester\Dumper;
use Tester\Runner\PhpInterpreter;
use Tester\Runner\Runner;

/**
 * CLI Tester.
 */
class CliTester
{

	/** @return int|NULL */
	public function run()
	{
		Environment::setupColors();
		Environment::setupErrors();
		Environment::$debugMode = FALSE;
		Environment::$useColors = TRUE;

		ob_start();

		$interpreter = new PhpInterpreter('php', []);
		if ($error = $interpreter->getStartupError()) {
			echo Dumper::color('red', "PHP startup error: $error") . "\n";
		}

		$runner = $this->createRunner($interpreter);
		$runner->setEnvironmentVariable(Environment::RUNNER, 1);
		$runner->setEnvironmentVariable(Environment::COLORS, (int) Environment::$useColors);

		ob_clean();
		ob_end_flush();

		$success = TRUE;
		$cumulativeResult = [1 => 0, 0, 0];
		foreach(glob(__DIR__ . '/../Component/*/tests') as $path) {
			$runner->paths = [$path];
			$name = str_replace([__DIR__ . '/../', '/tests'], '', $path);
			echo $name . PHP_EOL . str_repeat('=', 10) . PHP_EOL;
			$result = $runner->run();

			$results = $runner->getResults();
			$cumulativeResult[Runner::PASSED] += $results[Runner::PASSED];
			$cumulativeResult[Runner::SKIPPED] += $results[Runner::SKIPPED];
			$cumulativeResult[Runner::FAILED] += $results[Runner::FAILED];

			$success ^= $result;
		}

		$results = $cumulativeResult;
		echo ($results[Runner::FAILED]
			? Dumper::color('white/red') . ($results[Runner::FAILED] ? $results[Runner::FAILED] . ' failure' : '')
			: '') . Dumper::color() . PHP_EOL;

		return $success ? 0 : 1;
	}

	/** @return Runner */
	private function createRunner($interpreter)
	{
		$runner = new Runner($interpreter);
		$runner->threadCount = 8;
		$runner->stopOnFail = FALSE;
		$runner->outputHandlers[] = new class ($runner) extends \Tester\Runner\Output\ConsolePrinter
		{

			public function begin()
			{
				ob_start();
				parent::begin();
				echo rtrim(ob_get_clean()) . PHP_EOL;
			}

			public function result($testName, $result, $message)
			{
				$outputs = [
					Runner::PASSED => Dumper::color('green', '✔ ' . $testName),
					Runner::SKIPPED => Dumper::color('olive', 's ' . $testName) . "($message)",
					Runner::FAILED => Dumper::color('red', '✖ ' . $testName) . "\n" . $this->indent($message, 1) . "\n",
				];
				echo $outputs[$result] . PHP_EOL;
			}

			public function end()
			{
				ob_start();
				parent::end();
				echo trim(ob_get_clean()) . PHP_EOL . PHP_EOL;
			}

			private function indent($message, $spaces)
			{
				if ($message) {
					$result = '';
					foreach (explode(PHP_EOL, $message) as $line) {
						$result .= str_repeat(' ', $spaces) . $line . PHP_EOL;
					}
					return rtrim($result, PHP_EOL);
				}
				return $message;
			}

		};
		return $runner;
	}

}
