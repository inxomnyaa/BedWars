<?php

namespace xenialdan\BedWars\commands\subcommand;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use xenialdan\BedWars\Loader;
use xenialdan\gameapi\Game;

class InfoSubCommand extends BaseSubCommand
{

    /**
     * This is where all the arguments, permissions, sub-commands, etc would be registered
     */
    protected function prepare(): void
    {
        $this->setPermission("bedwars.command");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @var Game|Loader $p */
        $p = Loader::getInstance();
        $sender->sendMessage(TextFormat::DARK_PURPLE . "XBedWars " . TextFormat::LIGHT_PURPLE . "v." . $p->getDescription()->getVersion());
        $sender->sendMessage(TextFormat::DARK_PURPLE . "Coded by " . TextFormat::LIGHT_PURPLE . $p->getAuthors());
        $sender->sendMessage(TextFormat::DARK_PURPLE . "Website: " . TextFormat::LIGHT_PURPLE . $p->getDescription()->getWebsite());
        $sender->sendMessage(TextFormat::DARK_PURPLE . "Licensed under " . TextFormat::LIGHT_PURPLE . "GNU LESSER GENERAL PUBLIC LICENSE Version 2.1");
    }
}
