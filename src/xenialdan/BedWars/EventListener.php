<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace xenialdan\BedWars;

use pocketmine\block\Bed;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use xenialdan\BedWars\entity\ShopVillager;
use xenialdan\customui\elements\Button;
use xenialdan\customui\windows\SimpleForm;
use xenialdan\gameapi\API;
use xenialdan\gameapi\Arena;
use xenialdan\gameapi\Team;

/**
 * Class EventListener
 * @package xenialdan\XBedWars
 * Listens for all normal events
 */
class EventListener implements Listener
{

    public function onDamage(EntityDamageEvent $event)
    {
        if (API::isArenaOf(Loader::getInstance(), $event->getEntity()->getLevel())) {
            if (!$event->getEntity() instanceof Player) {
                $event->setCancelled();
                if ($event instanceof EntityDamageByEntityEvent) {
                    if (($damager = $event->getDamager()) instanceof Player) {
                        if ($event->getEntity() instanceof ShopVillager) {
                            if (($arena = API::getArenaByLevel(Loader::getInstance(), $event->getEntity()->getLevel())) instanceof Arena) {
                                if ($arena->getState() !== Arena::INGAME /*&& $arena->getState() !== Arena::SETUP*/) {
                                    $event->setCancelled();
                                    return;
                                }
                                Loader::getInstance()->openShop($damager);
                            }
                        }
                    }
                }
            }
            return;
        }/*
        if (API::isArena(Loader::getInstance(), ($entity = $event->getEntity())->getLevel()) && API::isPlaying($entity, Loader::getInstance())) {
        if (!($arena = API::getArenaByLevel(Loader::getInstance(), $entity->getLevel())) instanceof Arena) return;
            if ($arena->getState() !== Arena::INGAME && $arena->getState() !== Arena::SETUP) {
                $event->setCancelled();
                return;
            }
            if ($event instanceof EntityDamageByEntityEvent) {
                if (($damager = $event->getDamager()) instanceof Player) {
                    if (API::getTeamOfPlayer($entity)->inTeam($damager))
                        $event->setCancelled();
                    return;
                }
            }
        }*/
    }

    public function onBlockBreakEvent(BlockBreakEvent $event)
    {
        $level = ($entity = $event->getPlayer())->getLevel();
        if (API::isPlaying($entity, Loader::getInstance())) {
            $block = $event->getBlock();
            if ($block instanceof Bed) {
                $bedTile = $level->getTile($block);
                if ($bedTile instanceof \pocketmine\tile\Bed) {
                    $event->setDrops([]);
                    $c = $bedTile->getColor();
                    /** @var BedwarsTeam $attackedTeam */
                    $attackedTeam = API::getTeamByColor(Loader::getInstance(), $event->getBlock()->getLevel(), API::getColorByMeta($c));
                    if (is_null($attackedTeam)) {//no team but bed for color
                        Loader::getInstance()->getLogger()->notice("Tried to break a bed for a non existing team. You might want to fix your map. Bed: Color: " . API::getColorByMeta($c) . "" . $event->getBlock() . " " . $event->getBlock()->asVector3() . " " . $event->getBlock()->getLevel()->getName());
                        return;
                    }
                    $event->setCancelled();
                    if ($attackedTeam->inTeam($entity)) {
                        $entity->sendTip(TextFormat::RED . "You can not break your own teams bed!");//TODO add a warning to the player?
                        return;
                    } else {
                        if ($attackedTeam->isBedDestroyed()) return;
                        $event->setCancelled(false);
                        $attackedTeam->setBedDestroyed();
                        $teamOfPlayer = API::getTeamOfPlayer($entity);//TODO test if still happens in setup
                        if (is_null($teamOfPlayer)) {
                            var_dump("Still happens");
                            $event->setCancelled(false);
                            return;
                        }
                        Loader::getInstance()->getServer()->broadcastTitle(TextFormat::RED . "Your Teams bed was destroyed", TextFormat::RED . "by team " . $teamOfPlayer->getColor() . $teamOfPlayer->getName(), -1, -1, -1, $attackedTeam->getPlayers());
                        foreach ($attackedTeam->getPlayers() as $attackedTeamPlayer) {
                            $attackedTeamPlayer->setSpawn($attackedTeamPlayer->getServer()->getDefaultLevel()->getSafeSpawn());
                        }
                        Loader::getInstance()->getServer()->broadcastTitle($attackedTeam->getColor() . "The bed of team " . $attackedTeam->getName(), $attackedTeam->getColor() . "was destroyed by team " . $teamOfPlayer->getColor() . $teamOfPlayer->getName(), -1, -1, -1, $attackedTeam->getPlayers());
                        $spk = new PlaySoundPacket();
                        [$spk->x, $spk->y, $spk->z] = [$entity->x, $entity->y, $entity->z];
                        $spk->volume = 1;
                        $spk->pitch = 0.0;
                        $spk->soundName = "mob.enderdragon.end";
                        $entity->getLevel()->broadcastGlobalPacket($spk);
                        #if (count($arena->getPlayers()) <= 1) $arena->stopArena();
                    }
                }
            }
        }
    }

    public function onBlockPlaceEvent(BlockPlaceEvent $event)
    {
        if (!API::isArenaOf(Loader::getInstance(), $event->getBlock()->getLevel())) return;
        if (($arena = API::getArenaByLevel(Loader::getInstance(), $event->getBlock()->getLevel())) instanceof Arena) {
            if (($arena->getState() === Arena::STARTING || $arena->getState() === Arena::WAITING) && $event->getItem()->getId() === ItemIds::BED) {
                $event->setCancelled();
                $player = $event->getPlayer();
                /** @var Team $team */
                if(count(($team = $arena->getTeamByPlayer($player))->getPlayers()) <= $team->getMinPlayers()){
                    $player->sendMessage(TextFormat::RED.TextFormat::BOLD."Can not leave the team because a minimum of ".$team->getMinPlayers(). " players is required for this team");
                    return;
                }
                $form = new SimpleForm("Switch Team");
                foreach ($arena->getTeams() as $team) {
                    $button = new Button($team->getColor() . $team->getName() . TextFormat::GOLD . " [" . count($team->getPlayers()) . "/" . $team->getMaxPlayers() . "]");
                    $button->addImage(Button::IMAGE_TYPE_PATH, "textures/items/bed_" . strtolower($team->getName()));
                    $form->addButton($button);
                }
                $form->setCallable(function (Player $player, $data) use ($arena, $form) {
                    $player->getInventory()->clearAll();
                    $data = TextFormat::clean(substr($data, 0, strpos($data, " ")));
                    $arena->joinTeam($player, $data);
                });
                $player->sendForm($form);
                return;
            }
            /*if ($arena->getState() !== Arena::INGAME && $arena->getState() !== Arena::SETUP) {
                $event->setCancelled();
            }*/
        }

    }
}