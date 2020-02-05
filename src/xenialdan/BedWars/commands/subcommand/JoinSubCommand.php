<?php

namespace xenialdan\BedWars\commands\subcommand;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use xenialdan\BedWars\commands\arguments\JoinableArenaEnumArgument;
use xenialdan\BedWars\Loader;
use xenialdan\gameapi\API;
use xenialdan\gameapi\Arena;
use xenialdan\gameapi\Game;

class JoinSubCommand extends BaseSubCommand
{

    /**
     * This is where all the arguments, permissions, sub-commands, etc would be registered
     */
    protected function prepare(): void
    {
        $this->setPermission("backpack.command.join");
        $this->registerArgument(0, new JoinableArenaEnumArgument("Arena", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "This command can only be used as a player");
            return;
        }
        /** @var Game $p */
        $p = Loader::getInstance();
        /** @var Player $sender */
        $arena = API::getArenaOfPlayer($sender);
        if ($arena !== null) {
            $sender->sendMessage(TextFormat::RED . "You can't join another arena while already in a game");
            return;
        }
        $arena = null;
        if (empty(trim(($targetArenaName = $args["Arena"] ?? "")))) {
            $sender->sendMessage("No arena selected, trying to find a random arena");
            $available = array_values(array_map(function (Arena $arena): string {
                return $arena->getLevelName();
            }, array_filter($p->getArenas(), function (Arena $arena): bool {
                return ($arena->getState() === Arena::IDLE || $arena->getState() === Arena::WAITING);
            })));
            if (empty($available)) {
                $sender->sendMessage(TextFormat::RED . "No free arena found. Try again later");
                return;
            }
            $arena = $available[array_rand($available)];
        } else {
            $arena = $p->getArenaByLevelName($targetArenaName);
            if (is_null($arena)) {
                $sender->sendMessage(TextFormat::RED . "Arena " . $targetArenaName . " not found");
                return;
            }
        }
        if (is_null($arena)) {
            $sender->sendMessage(TextFormat::RED . "No arena found");
            return;
        }
        if (!$arena->joinTeam($sender)) {
            $sender->sendMessage(TextFormat::RED . "Error joining arena");
            return;
        }
    }
}
