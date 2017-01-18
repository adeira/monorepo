<?php declare(strict_types = 1);

namespace Adeira\Monorepo\Composer;

class Plugin implements \Composer\Plugin\PluginInterface, \Composer\Plugin\Capable
{

	public function activate(\Composer\Composer $composer, \Composer\IO\IOInterface $io)
	{
		//PluginInterface
	}

	public function getCapabilities()
	{
		return [
			\Composer\Plugin\Capability\CommandProvider::class => \Adeira\Monorepo\Composer\CommandProvider::class,
		];
	}

}
