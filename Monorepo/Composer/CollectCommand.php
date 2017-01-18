<?php declare(strict_types = 1);

namespace Adeira\Monorepo\Composer;

use Composer\DependencyResolver\Pool;
use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Collect all informations about subpackages and add them to the root composer.json file.
 */
class CollectCommand extends \Composer\Command\BaseCommand
{

	private $require = [
		'php' => '^7.0',
		'adeira/monorepo-composer-plugin' => '*',
		'roave/security-advisories' => 'dev-master',
	];

	private $requireDev = [
		'composer/composer' => '^1.3',
		'nette/tester' => 'dev-master',
	];

	protected function configure()
	{
		$this->setName('adeira:collect');
		$this->setDescription('Collect Composer dependencies from subpackages.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$io = $this->getIO();
		$config = $this->getComposer()->getConfig();
		$vendorDir = $config->get('vendor-dir');
		$componentsDirectory = $vendorDir . '/../Component';

		$composerFile = $vendorDir . '/../composer.json';
		$manipulator = new JsonManipulator(file_get_contents($composerFile));
		$manipulator->addMainKey('replace', []); // reset replace section

		$componentsData = $this->fetchComponentsDataAndReplaceConstraints($componentsDirectory);
		$autoload = [];
		$require = $this->require;
		$requireDev = $this->requireDev;
		foreach ($componentsData as $componentName => $componentData) {
			$require += $componentData['require']; //TODO: biggest constraint
			$requireDev += $componentData['require-dev'] ?? [];

			// update 'replace' section
			$io->write(sprintf(' > <comment>Generate replace definition for %s</comment>', $componentName));
			$manipulator->addLink('replace', $componentName, 'self.version');

			if (isset($componentData['autoload'])) {
				foreach ($componentData['autoload']['psr-4'] as $namespace => $destination) {
					$name = ltrim($componentName, 'adeira/');
					$autoload[preg_replace('~\\\~', '\\\\', $namespace)][] = "Component/$name/$destination";
				}
			}
			if (isset($componentData['autoload-dev'])) {
				foreach ($componentData['autoload-dev']['psr-4'] as $namespace => $destination) { //FIXME: dry
					$name = ltrim($componentName, 'adeira/');
					$autoload[preg_replace('~\\\~', '\\\\', $namespace)][] = "Component/$name/$destination";
				}
			}
		}

		// update 'require' and 'require-dev'
		$io->write(' > <comment>Generate require definition</comment>');
		$manipulator = $this->updateRequire($manipulator, $composerFile, $require);
		$io->write(' > <comment>Generate require-dev definition</comment>');
		$manipulator = $this->updateRequireDev($manipulator, $composerFile, $requireDev);

		// update 'autoload'
		$io->write(sprintf(' > <comment>Generate autoloaders</comment>'));
		$manipulator->addMainKey('autoload', ['psr-4' => $autoload]);

		// persist
		file_put_contents($composerFile, $manipulator->getContents());
	}

	private function updateRequire(JsonManipulator $manipulator, string $composerFile, array $require)
	{
		$manipulator->addMainKey('require', []); //reset require key

		$componentJson = new JsonFile($composerFile);
		$data = $componentJson->read();

		foreach ($require as $package => $constraint) {
			if (!$constraint) { //NULL in subpackage
				$constraint = $data['require'][$package] ?? NULL;
			}
			if (!$constraint) { //NULL even in main package
				$constraint = $this->findRecommendedRequireVersion($package);
			}
			$manipulator->addLink('require', $package, $constraint, TRUE);
		}

		return $manipulator;
	}

	private function updateRequireDev(JsonManipulator $manipulator, string $composerFile, array $requireDev)
	{
		$manipulator->addMainKey('require-dev', []); //reset require-dev key

		$componentJson = new JsonFile($composerFile);
		$data = $componentJson->read();

		foreach ($requireDev as $package => $constraint) {
			if (!$constraint) { //NULL in subpackage
				$constraint = $data['require-dev'][$package] ?? NULL;
			}
			if (!$constraint) { //NULL even in main package
				$constraint = $this->findRecommendedRequireVersion($package);
			}
			$manipulator->addLink('require-dev', $package, $constraint, TRUE);
		}

		return $manipulator;
	}

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

	private function fetchComponentsDataAndReplaceConstraints($componentsDirectory): array
	{
		$componentsData = [];
		foreach (array_diff(scandir($componentsDirectory), ['..', '.']) as $componentDirectory) {
			$this->getIO()->write(sprintf(' > <comment>Collect data from %s</comment>', $componentDirectory));
			$componentDirectory = $componentsDirectory . '/' . $componentDirectory;

			$this->createComponentDefaultVendor($componentDirectory);

			$componentJson = new JsonFile($composerFile = $componentDirectory . '/composer.json');
			$data = $componentJson->read();
			$componentsData[$data['name']] = $data;
			$manipulator = new JsonManipulator(file_get_contents($composerFile));

			// require
			$key = 'require';
/*			if (isset($componentsData[$data['name']][$key])) {
				$manipulator->addMainKey($key, []); //reset require key
				foreach ($componentsData[$data['name']][$key] as $package => $constraint) {
					$manipulator->addLink($key, $package, NULL, TRUE); //add requires with NULL constraint
				}
			}

			// require-dev
			$key = 'require-dev';
			if (isset($componentsData[$data['name']][$key])) {
				$manipulator->addMainKey($key, []); //reset require-dev key
				foreach ($componentsData[$data['name']][$key] as $package => $constraint) {
					//$manipulator->addLink($key, $package, NULL, TRUE); //add requires with NULL constraint
				}
			}
*/
			file_put_contents($composerFile, $manipulator->getContents());
		}
		return $componentsData;
	}

	private function createComponentDefaultVendor($componentDirectory)
	{
		$componentVendor = $componentDirectory . '/vendor';
		if (!is_dir($componentVendor)) {
			mkdir($componentVendor);
			file_put_contents(
				$componentVendor . '/autoload.php',
				"<?php require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';\n"
			);
		}
	}

}
