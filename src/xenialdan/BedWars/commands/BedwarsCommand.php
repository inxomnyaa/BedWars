<?php

declare(strict_types=1);

namespace xenialdan\BedWars\commands;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use xenialdan\BedWars\Loader;
use xenialdan\gameapi\API;
use xenialdan\gameapi\Game;

class BedwarsCommand extends PluginCommand
{
    public function __construct(Plugin $plugin)
    {
        parent::__construct("bw", $plugin);
        $this->setAliases(["bedwars"]);
        $this->setPermission("bedwars.command");
        $this->setDescription("Bedwars commands for setup or leaving a game");
        $this->setUsage("/bw | /bw setup | /bw endsetup | /bw leave | /bw forcestart | /bw stop | /bw status | /bw info | /bw help");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        /** @var Player $sender */
        $return = $sender->hasPermission($this->getPermission());
        if (!$return) {
            $sender->sendMessage(TextFormat::RED . "You do not have permissions to run this command");
            return true;
        }
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "This command is for players only");
            return false;
        }
        try {
            $return = true;
            switch ($args[0] ?? "help") {
                case "setup":
                    {
                        if (!$sender->hasPermission("bedwars.command.setup")) {
                            $sender->sendMessage(TextFormat::RED . "You do not have permissions to run this command");
                            return true;
                        }
                        /** @var Game $p */
                        $p = $this->getPlugin();
                        $p->setupArena($sender);
                        break;
                    }
                case "join":
                    {
                        if (!$sender->hasPermission("bedwars.command.join")) {
                            $sender->sendMessage(TextFormat::RED . "You do not have permission to run this command");
                            return true;
                        }
                        if (API::getArenaOfPlayer($sender) !== null) {
                            $sender->sendMessage(TextFormat::RED . "You can't join another arena while already in a game");
                            return true;
                        }
                        if (is_null($arena = Loader::getInstance()->getArenas()[$args[1]]??null)) {
                            $sender->sendMessage(TextFormat::RED . "Arena " . $args[1] . " not found");
                            return true;
                        }
                        if (!$arena->joinTeam($sender)) {
                            $sender->sendMessage(TextFormat::RED . "Error joining arena");
                            return true;
                        }
                        break;
                    }
                case "leave":
                    {
                        if (!$sender->hasPermission("bedwars.command.leave")) {
                            $sender->sendMessage(TextFormat::RED . "You do not have permissions to run this command");
                            return true;
                        }
                        $arena = API::getArenaOfPlayer($sender);
                        if(is_null($arena) || !API::isArenaOf($this->getPlugin(), $arena->getLevel())){
                            /** @var Game $plugin */
                            $plugin = $this->getPlugin();
                            $sender->sendMessage(TextFormat::RED."It appears that you are not playing ". $plugin->getPrefix());
                            return true;
                        }
                        if (API::isPlaying($sender, $this->getPlugin())) $arena->removePlayer($sender);
                        break;
                    }
                case "endsetup":
                    {
                        if (!$sender->hasPermission("bedwars.command.endsetup")) {//TODO only when setup
                            $sender->sendMessage(TextFormat::RED . "You do not have permissions to run this command");
                            return true;
                        }
                        /** @var Game $p */
                        $p = $this->getPlugin();
                        $p->endSetupArena($sender);
                        break;
                    }
                case "stop":
                    {
                        if (!$sender->hasPermission("bedwars.command.stop")) {
                            $sender->sendMessage(TextFormat::RED . "You do not have permissions to run this command");
                            return true;
                        }
                        API::getArenaByLevel(Loader::getInstance(), $sender->getLevel())->stopArena();
                        break;
                    }
                case "forcestart":
                    {
                        if (!$sender->hasPermission("bedwars.command.forcestart")) {
                            $sender->sendMessage(TextFormat::RED . "You do not have permissions to run this command");
                            return true;
                        }
                        $arena = API::getArenaOfPlayer($sender);
                        if(is_null($arena) || !API::isArenaOf($this->getPlugin(), $arena->getLevel())){
                            /** @var Game $plugin */
                            $plugin = $this->getPlugin();
                            $sender->sendMessage(TextFormat::RED."It appears that you are not playing ". $plugin->getPrefix());
                            return true;
                        }
                        $arena->startTimer($arena->getOwningGame());
                        $arena->forcedStart = true;
                        $arena->setTimer(5);
                        $sender->getServer()->broadcastMessage("Arena will start immediately due to a forced start by " . $sender->getDisplayName(), $arena->getPlayers());
                        break;
                    }
                case "help":
                    {
                        if (!$sender->hasPermission("bedwars.command.help")) {
                            $sender->sendMessage(TextFormat::RED . "You do not have permissions to run this command");
                            return true;
                        }
                        $sender->sendMessage($this->getUsage());
                        $return = true;
                        break;
                    }
                default:
                    {
                        $return = false;
                        throw new \InvalidArgumentException("Unknown argument supplied: " . $args[0]);
                    }
            }
        } catch (\Throwable $error) {
            $this->getPlugin()->getLogger()->logException($error);
            $return = false;
        } finally {
            return $return;
        }
    }
}
