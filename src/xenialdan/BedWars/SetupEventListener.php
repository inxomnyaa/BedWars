<?php


namespace xenialdan\BedWars;


use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use xenialdan\BedWars\entity\ShopVillager;
use xenialdan\gameapi\API;
use xenialdan\gameapi\Arena;

class SetupEventListener implements Listener
{

    /**
     * @priority HIGHEST
     * @param EntitySpawnEvent $e
     */
    public function spawnShop(EntitySpawnEvent $e){
        if (!$e->getEntity() instanceof ShopVillager) return;
        if (!API::isArenaOf(Loader::getInstance(), ($level = ($entity = $e->getEntity())->getLevel()))) return;
        if (!($arena = API::getArenaByLevel(Loader::getInstance(), $level)) instanceof Arena) return;
        if ($arena->getState() !== Arena::SETUP) {
            return;
        }
        $entity->setRotation(0,0);
        $entity->setNameTagVisible(true);
        $entity->setNameTagAlwaysVisible(false);
        $entity->setImmobile();
        $entity->setMotion(new Vector3());
        $entity->respawnToAll();
    }

    /**
     * @priority HIGHEST
     * @param EntityDamageEvent $e
     */
    public function removeOrRotateShop(EntityDamageEvent $e){
        if (!$e->getEntity() instanceof ShopVillager || !$e instanceof EntityDamageByEntityEvent) return;
        if (!$e->getDamager() instanceof Player) return;
        if (!API::isArenaOf(Loader::getInstance(), ($level = ($entity = $e->getEntity())->getLevel()))) return;
        if (!($arena = API::getArenaByLevel(Loader::getInstance(), $level)) instanceof Arena) return;
        if ($arena->getState() !== Arena::SETUP) {
            return;
        }
        $e->setCancelled();
        if($e->getDamager()->isSneaking()){
            $entity->close();
            return;
        }
        $newYaw = ($entity->getYaw() + 45) % 360;
        $entity->setRotation($newYaw, 0);
        $entity->respawnToAll();
    }

    /**
     * @priority HIGHEST
     * @param BlockPlaceEvent $e
     */
    public function setSpawns(BlockPlaceEvent $e)
    {
        if (!API::isArenaOf(Loader::getInstance(), $e->getBlock()->getLevel())) return;
        if (!($arena = API::getArenaByLevel(Loader::getInstance(), $e->getBlock()->getLevel())) instanceof Arena) return;
        if ($arena->getState() !== Arena::SETUP) {
            return;
        }
        if ($e->getBlock()->getId() !== Block::CONCRETE) return;
        $e->setCancelled();
        $color = API::getColorByMeta($e->getBlock()->getDamage());
        $team = API::getTeamByColor(Loader::getInstance(), $e->getPlayer()->getLevel(), $color);
        if (is_null($team)) return;
        $team->setSpawn($e->getBlock()->asVector3());
        /** @var BedwarsSettings $settings */
        $settings = $arena->getSettings();
        $settings->teams[$team->getName()]["spawn"] = (array)$e->getBlock()->asVector3();
        $arena->getSettings()->save();
        $e->getPlayer()->sendMessage("Successfully changed spawn of team " . $team->getColor() . $team->getName() . TextFormat::RESET . " to [" . (join(", ", (array)$e->getBlock()->asVector3())) . "]");
    }

    /**
     * @priority HIGHEST
     * @param BlockBreakEvent $e
     */
    public function removeItemSpawns(BlockBreakEvent $e)
    {
        if (!API::isArenaOf(Loader::getInstance(), $e->getBlock()->getLevel())) return;
        if (!($arena = API::getArenaByLevel(Loader::getInstance(), $e->getBlock()->getLevel())) instanceof Arena) return;
        if ($arena->getState() !== Arena::SETUP) {
            return;
        }
        if ($e->getBlock()->getId() !== Block::GOLD_BLOCK && $e->getBlock()->getId() !== Block::IRON_BLOCK && $e->getBlock()->getId() !== Block::HARDENED_CLAY) return;
        $e->setCancelled();
        /** @var BedwarsSettings $settings */
        $settings = $arena->getSettings();
        $vector3 = (array)$e->getBlock()->asVector3();
        $removed = false;
        if ($e->getBlock()->getId() === Block::GOLD_BLOCK) {
            foreach ($settings->gold as $i => $gold) {
                if ($gold["x"] === $vector3["x"] && $gold["y"] === $vector3["y"] && $gold["z"] === $vector3["z"]) {
                    $s = $settings->gold;
                    unset($s[$i]);
                    $settings->gold = $s;
                    $e->getPlayer()->sendMessage("Successfully removed gold item spawner at [" . (join(", ", (array)$e->getBlock()->asVector3())) . "]");
                    $removed = true;
                }
            }
        }
        if ($e->getBlock()->getId() === Block::IRON_BLOCK) {
            foreach ($settings->silver as $i => $silver) {
                if ($silver["x"] === $vector3["x"] && $silver["y"] === $vector3["y"] && $silver["z"] === $vector3["z"]) {
                    $s = $settings->silver;
                    unset($s[$i]);
                    $settings->silver = $s;
                    $e->getPlayer()->sendMessage("Successfully removed silver item spawner at [" . (join(", ", (array)$e->getBlock()->asVector3())) . "]");
                    $removed = true;
                }
            }
        }
        if ($e->getBlock()->getId() === Block::HARDENED_CLAY) {
            foreach ($settings->bronze as $i => $bronze) {
                if ($bronze["x"] === $vector3["x"] && $bronze["y"] === $vector3["y"] && $bronze["z"] === $vector3["z"]) {
                    $s = $settings->bronze;
                    unset($s[$i]);
                    $settings->bronze = $s;
                    $e->getPlayer()->sendMessage("Successfully removed bronze item spawner at [" . (join(", ", (array)$e->getBlock()->asVector3())) . "]");
                    $removed = true;
                }
            }
        }
        if ($removed) $arena->getSettings()->save();
        else $e->setCancelled(false);
    }

    /**
     * @priority HIGHEST
     * @param PlayerInteractEvent $e
     */
    public function setItemSpawns(PlayerInteractEvent $e)
    {
        if (!$e->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) return;
        if (!API::isArenaOf(Loader::getInstance(), $e->getBlock()->getLevel())) return;
        if (!($arena = API::getArenaByLevel(Loader::getInstance(), $e->getBlock()->getLevel())) instanceof Arena) return;
        if ($arena->getState() !== Arena::SETUP) {
            return;
        }
        if ($e->getBlock()->getId() !== Block::GOLD_BLOCK && $e->getBlock()->getId() !== Block::IRON_BLOCK && $e->getBlock()->getId() !== Block::HARDENED_CLAY) return;
        $e->setCancelled();
        /** @var BedwarsSettings $settings */
        $settings = $arena->getSettings();
        $vector3 = (array)$e->getBlock()->asVector3();
        if ($e->getBlock()->getId() === Block::GOLD_BLOCK) {
            foreach ($settings->gold as $i => $v3) {
                if ($v3["x"] === $vector3["x"] && $v3["y"] === $vector3["y"] && $v3["z"] === $vector3["z"]) {
                    $e->getPlayer()->sendMessage(TextFormat::RED . "This block is already an item spawner. Break the block to remove it");
                    return;
                }
            }
            $settings->gold[] = (array)$e->getBlock()->asVector3();
            $e->getPlayer()->sendMessage("Successfully added gold item spawner at [" . (join(", ", (array)$e->getBlock()->asVector3())) . "]");
        }
        if ($e->getBlock()->getId() === Block::IRON_BLOCK) {
            foreach ($settings->silver as $i => $v3) {
                if ($v3["x"] === $vector3["x"] && $v3["y"] === $vector3["y"] && $v3["z"] === $vector3["z"]) {
                    $e->getPlayer()->sendMessage(TextFormat::RED . "This block is already an item spawner. Break the block to remove it");
                    return;
                }
            }
            $settings->silver[] = (array)$e->getBlock()->asVector3();
            $e->getPlayer()->sendMessage("Successfully added silver item spawner at [" . (join(", ", (array)$e->getBlock()->asVector3())) . "]");
        }
        if ($e->getBlock()->getId() === Block::HARDENED_CLAY) {
            foreach ($settings->bronze as $i => $v3) {
                if ($v3["x"] === $vector3["x"] && $v3["y"] === $vector3["y"] && $v3["z"] === $vector3["z"]) {
                    $e->getPlayer()->sendMessage(TextFormat::RED . "This block is already an item spawner. Break the block to remove it");
                    return;
                }
            }
            $settings->bronze[] = (array)$e->getBlock()->asVector3();
            $e->getPlayer()->sendMessage("Successfully added bronze item spawner at [" . (join(", ", (array)$e->getBlock()->asVector3())) . "]");
        }
        $arena->getSettings()->save();
    }

}