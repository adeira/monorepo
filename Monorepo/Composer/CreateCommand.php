<?php declare(strict_types = 1);

namespace Adeira\Monorepo\Composer;

use Symfony\Component\Console\{
	Input\InputArgument, Input\InputInterface, Output\OutputInterface, Style\SymfonyStyle
};

class CreateCommand extends \Composer\Command\BaseCommand
{

	protected function configure()
	{
		$this->setName('adeira:create');
		$this->setDescription('Create new empty submodule from templates.');
		$this->addArgument('submoduleName', InputArgument::REQUIRED);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$templateDir = __DIR__ . '/../../Template';
		$directory = new \RecursiveDirectoryIterator($templateDir, \RecursiveDirectoryIterator::SKIP_DOTS);
		$iterator = new \RecursiveIteratorIterator($directory);
		$submoduleName = $input->getArgument('submoduleName');
		$destinationDirectory = __DIR__ . '/../../Component/' . $submoduleName;

		if (is_dir($destinationDirectory)) {
			throw new \Exception("Cannot create submodule '$submoduleName' because destination folder already exists.");
		}

		$progress = (new SymfonyStyle($input, $output))->createProgressBar(count(iterator_to_array($iterator)));
		$progress->start();

		/** @var \SplFileInfo $item */
		foreach ($iterator as $item) {
			$newFile = $destinationDirectory . '/' . str_replace("$templateDir/", '', $item->getPathname());

			if (!file_exists(dirname($newFile))) {
				mkdir(dirname($newFile), 0755, TRUE);
			}

			$newFileContent = file_get_contents($item->getPathname());
			$newFileContent = str_replace([
				'<<packageName>>',
				'<<PackageName>>',
			], [
				$submoduleName,
				ucfirst($submoduleName),
			], $newFileContent);

			file_put_contents($newFile, $newFileContent);
			$progress->advance();
		}

		$progress->finish();
	}

}
