<?php declare(strict_types = 1);

namespace Adeira\Monorepo\Composer;

use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EjectCommand extends \Composer\Command\BaseCommand
{

	protected function configure()
	{
		$this->setName('adeira:eject');
		$this->setDescription('Eject Composer dependencies into subpackages.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$io = $this->getIO();
		$config = $this->getComposer()->getConfig();
		$vendorDir = $config->get('vendor-dir');
		$componentsDirectory = $vendorDir . '/../Component';
		$composerFile = $vendorDir . '/../composer.json';

		$composerData = (new JsonFile($composerFile))->read();
		foreach (array_diff(scandir($componentsDirectory), ['..', '.']) as $componentDirectory) {
			$io->write(sprintf(' > <comment>Eject %s</comment>', $componentDirectory));
			$packageComposerData = (new JsonFile($packageComposerFile = $componentsDirectory . '/' . $componentDirectory . '/composer.json'))->read();

			$manipulator = new JsonManipulator(file_get_contents($packageComposerFile));

			$manipulator->addMainKey('require', []);
			foreach ($packageComposerData['require'] as $packageName => $packageConstraint) {
				$manipulator->addLink('require', $packageName, $composerData['require'][$packageName]);
			}
			$manipulator->addMainKey('require-dev', []);
			foreach ($packageComposerData['require-dev'] as $packageName => $packageConstraint) {
				$manipulator->addLink('require-dev', $packageName, $composerData['require-dev'][$packageName]);
			}

			file_put_contents($packageComposerFile, $manipulator->getContents());
		}
	}

}
