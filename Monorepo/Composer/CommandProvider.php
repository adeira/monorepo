<?php declare(strict_types = 1);

namespace Adeira\Monorepo\Composer;

class CommandProvider implements \Composer\Plugin\Capability\CommandProvider
{

	public function getCommands()
	{
		return [
			new CollectCommand,
			new CreateCommand,
			new EjectCommand,
		];
	}

}
