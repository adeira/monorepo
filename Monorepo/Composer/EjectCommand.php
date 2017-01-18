<?php declare(strict_types = 1);

namespace Adeira\Monorepo\Composer;

use Composer\DependencyResolver\Pool;
use Composer\Json\{
	JsonFile, JsonManipulator
};
use Composer\Package\Version\VersionSelector;
use Composer\Repository\{
	CompositeRepository, PlatformRepository, RepositoryFactory
};
use Symfony\Component\Console\{
	Input\InputInterface, Output\OutputInterface
};

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

			if (isset($packageComposerData['require'])) {
				$manipulator->addMainKey('require', []);
				foreach ($packageComposerData['require'] as $packageName => $packageConstraint) {
					if (!isset($composerData['require'][$packageName])) {
						$constraint = $this->findRecommendedRequireVersion($packageName);
					} else {
						$constraint = $composerData['require'][$packageName];
					}
					$manipulator->addLink('require', $packageName, $constraint);
				}
			}

			if (isset($packageComposerData['require-dev'])) {
				$manipulator->addMainKey('require-dev', []);
				foreach ($packageComposerData['require-dev'] as $packageName => $packageConstraint) {
					if (!isset($composerData['require-dev'][$packageName])) {
						$constraint = $this->findRecommendedRequireVersion($packageName);
					} else {
						$constraint = $composerData['require-dev'][$packageName];
					}
					$manipulator->addLink('require-dev', $packageName, $constraint);
				}
			}

			file_put_contents($packageComposerFile, $manipulator->getContents());
		}
	}

	/**
	 * FIXME: dry!
	 */
	private function findRecommendedRequireVersion(string $package): string
	{
		$io = $this->getIO();
		$pool = new Pool;
		$pool->addRepository(new CompositeRepository(array_merge(
			[new PlatformRepository],
			RepositoryFactory::defaultRepos($io)
		)));
		$versionSelector = new VersionSelector($pool);
		$io->write(sprintf(' > <info>Find best Composer version constraint for %s</info>', $package));
		$packageCandidate = $versionSelector->findBestCandidate($package);
		if (!$packageCandidate) {
			throw new \Exception('Cannot find package ' . $package); //TODO: info o jaký bundle se jedná
		}
		return $versionSelector->findRecommendedRequireVersion($packageCandidate);
	}

}
