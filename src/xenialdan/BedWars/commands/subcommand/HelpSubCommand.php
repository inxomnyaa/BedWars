<?php

namespace xenialdan\BedWars\commands\subcommand;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;

class HelpSubCommand extends BaseSubCommand
{

    /**
     * This is where all the arguments, permissions, sub-commands, etc would be registered
     */
    protected function prepare(): void
    {
        $this->setPermission("bedwars.command.help");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $this->sendUsage();
    }
}
