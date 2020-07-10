<?php

declare(strict_types=1);

namespace xenialdan\BedWars\entity;

use pocketmine\entity\Villager;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\Player;

class ShopVillager extends Villager
{

    public function getName(): string
    {
        return "Shop Villager";
    }

    protected function doHitAnimation(): void
    {
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($source instanceof EntityDamageByEntityEvent && !$source->getDamager() instanceof Player) {
            $source->setCancelled();
        } else {
            parent::attack($source);
        }
    }
}