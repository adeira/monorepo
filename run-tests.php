<?php

require __DIR__ . '/Monorepo/CliTester.php';

$baseDir = __DIR__ . '/vendor/nette/tester/src';

require $baseDir . '/Runner/PhpInterpreter.php';
require $baseDir . '/Runner/Runner.php';
require $baseDir . '/Runner/CliTester.php';
require $baseDir . '/Runner/Job.php';
require $baseDir . '/Runner/CommandLine.php';
require $baseDir . '/Runner/TestHandler.php';
require $baseDir . '/Runner/OutputHandler.php';
require $baseDir . '/Runner/Output/ConsolePrinter.php';
require $baseDir . '/Framework/Helpers.php';
require $baseDir . '/Framework/Environment.php';
require $baseDir . '/Framework/Assert.php';
require $baseDir . '/Framework/AssertException.php';
require $baseDir . '/Framework/Dumper.php';
require $baseDir . '/Framework/DataProvider.php';
require $baseDir . '/Framework/TestCase.php';

die((new Adeira\Monorepo\CliTester)->run());
