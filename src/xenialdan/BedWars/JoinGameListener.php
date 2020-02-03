<?php

namespace xenialdan\BedWars;

use pocketmine\block\SignPost;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;

/**
 * Class JoinListener
 * @package xenialdan\XBedWars
 * Listens for interacts for joining games or teams
 */
class JoinGameListener implements Listener
{

    public function onInteract(PlayerInteractEvent $event): void
    {
        $action = $event->getAction();
        $block = $event->getBlock();
        if ($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK && $block instanceof SignPost) {
            /** @var $tile Sign */
            if (($tile = $block->getLevel()->getTile($block)) instanceof Sign) {
                $this->onClickSign($event, $tile->getText());
            }
        }
    }

    private function onClickSign(PlayerInteractEvent $event, array $text): void
    {
        if (strpos(strtolower(TextFormat::clean($text[0])), strtolower(TextFormat::clean(Loader::getInstance()->getPrefix()))) !== false) {
            $player = $event->getPlayer();
            if (is_null($arena = Loader::getInstance()->getArenas()[TextFormat::clean($text[1])] ?? null)) {
                $player->sendMessage(TextFormat::RED . 'Arena not found');
                return;
            }
            if (!$arena->joinTeam($player)) {
                $player->sendMessage(TextFormat::RED . 'Error joining');
            }
        }
    }

}