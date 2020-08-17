<?php

declare(strict_types=1);

namespace xenialdan\BedWars\commands;

use CortexPE\Commando\args\BaseArgument;
use CortexPE\Commando\BaseCommand;
use pocketmine\command\CommandSender;
use xenialdan\BedWars\commands\subcommand\EndSetupSubCommand;
use xenialdan\BedWars\commands\subcommand\ForcestartSubCommand;
use xenialdan\BedWars\commands\subcommand\HelpSubCommand;
use xenialdan\BedWars\commands\subcommand\InfoSubCommand;
use xenialdan\BedWars\commands\subcommand\JoinSubCommand;
use xenialdan\BedWars\commands\subcommand\LeaveSubCommand;
use xenialdan\BedWars\commands\subcommand\SetupSubCommand;
use xenialdan\BedWars\commands\subcommand\StopSubCommand;

class BedwarsCommand extends BaseCommand
{

    /**
     * This is where all the arguments, permissions, sub-commands, etc would be registered
     * @throws \CortexPE\Commando\exception\SubCommandCollision
     */
    protected function prepare(): void
    {
        $this->setPermission("bedwars.command");
        $this->registerSubCommand(new JoinSubCommand("join", "Finds an available game to join. Specify an arena name to join it."));
        $this->registerSubCommand(new LeaveSubCommand("leave", "Leave the current game"));
        $this->registerSubCommand(new ForcestartSubCommand("forcestart", "Immediately start the game"));
        $this->registerSubCommand(new StopSubCommand("stop", "Stops the running game"));//TODO add optional runningarena arg to remotely stop
        $this->registerSubCommand(new SetupSubCommand("setup", "Opens the arena setup UI. Remember to run /bw endsetup when finished to save changes"));
        $this->registerSubCommand(new EndSetupSubCommand("endsetup", "Stops the setup process of an arena. Must be executed to save changes upon /bw setup"));
        $this->registerSubCommand(new HelpSubCommand("help", "Sends a usage message"));//TODO make more helpful. Book?
        $this->registerSubCommand(new InfoSubCommand("info", "Information about XBedWars by XenialDan"));
    }

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param BaseArgument[] $args
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (empty($args)) {
            $this->sendUsage();
        }
    }
}
