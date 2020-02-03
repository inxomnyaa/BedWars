<?php

namespace xenialdan\BedWars;

use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use xenialdan\gameapi\API;

/**
 * Class LeaveGameListener
 * @package xenialdan\XBedWars
 * Listens for interacts for leaving games or teams
 */
class LeaveGameListener implements Listener
{

    public function onDeath(PlayerDeathEvent $ev): void
    {
        if (API::isArenaOf(Loader::getInstance(), ($player = $ev->getPlayer())->getLevel()) && API::isPlaying($player, Loader::getInstance())) {
            $team = API::getTeamOfPlayer($player);
            /** @var BedwarsTeam $team */
            if ($team->isBedDestroyed()) {
                /** @noinspection PhpUnhandledExceptionInspection */
                API::getArenaByLevel(Loader::getInstance(), $player->getLevel())->removePlayer($player);
            }
        }
    }

    public function onDisconnectOrKick(PlayerQuitEvent $ev): void
    {
        if (API::isArenaOf(Loader::getInstance(), $ev->getPlayer()->getLevel()))
            /** @noinspection PhpUnhandledExceptionInspection */
            API::getArenaByLevel(Loader::getInstance(), $ev->getPlayer()->getLevel())->removePlayer($ev->getPlayer());
    }

    public function onLevelChange(EntityLevelChangeEvent $ev): void
    {
        if ($ev->getEntity() instanceof Player) {
            if (API::isArenaOf(Loader::getInstance(), $ev->getOrigin()) && API::isPlaying($ev->getEntity(), Loader::getInstance()))//TODO test if still calls it twice
                /** @noinspection PhpUnhandledExceptionInspection */
                API::getArenaByLevel(Loader::getInstance(), $ev->getOrigin())->removePlayer($ev->getEntity());
        }
    }
}