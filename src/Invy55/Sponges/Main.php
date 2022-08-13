<?php

declare(strict_types=1);

namespace Invy55\Sponges;

use Invy55\Sponges\Tasks\SetBlockTask;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Water;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\Position;
use pocketmine\world\World;

class Main extends PluginBase implements Listener {

    protected function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onBlockPlaceEvent(BlockPlaceEvent $event): void {
        $block = $event->getBlock();
        if (($block->isSameState(VanillaBlocks::SPONGE()->setWet(false))) && $this->absorbWater($block->getPosition())) {
            $this->getScheduler()->scheduleDelayedTask(new SetBlockTask($this, $block->getPosition()->getWorld(), $block->getPosition(), VanillaBlocks::SPONGE()->setWet(true), true), 1);
        }
    }

    public function onWaterFlow(BlockSpreadEvent $event): void {
        $source = $event->getSource();
        $block = $event->getBlock();

        if ($source instanceof Water) {
            $sponge = $this->hasSpongeNear($block->getPosition()->getWorld(), $block->getPosition()->getX(), $block->getPosition()->getY(), $block->getPosition()->getZ());
            if ($sponge instanceof Block) {
                $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($sponge): void {
                    $this->absorbWater($sponge->getPosition());
                }), 1);
                $this->getScheduler()->scheduleDelayedTask(new SetBlockTask($this, $sponge->getPosition()->getWorld(), $sponge->getPosition(), VanillaBlocks::SPONGE()->setWet(true), true), 1);
            }
        }
    }

    public function onBucketUse(PlayerBucketEmptyEvent $event): void {
        $block = $event->getBlockClicked();
        $sponge = $this->hasSpongeNear($block->getPosition()->getWorld(), $block->getPosition()->getX(), $block->getPosition()->getY(), $block->getPosition()->getZ());
        if ($sponge instanceof Block) {
            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($sponge): void {
                $this->absorbWater($sponge->getPosition());
            }), 1);
            $this->getScheduler()->scheduleDelayedTask(new SetBlockTask($this, $sponge->getPosition()->getWorld(), $sponge->getPosition()->asVector3(), VanillaBlocks::SPONGE()->setWet(true), true), 1);
        }
    }

    public function hasSpongeNear(World $world, int $xBlock, int $yBlock, int $zBlock): Block|bool {
        for ($x = -1; $x <= 1; ++$x) {
            for ($y = -1; $y <= 1; ++$y) {
                for ($z = -1; $z <= 1; ++$z) {
                    return $world->getBlockAt($xBlock + $x, $yBlock + $y, $zBlock + $z)->isSameState(VanillaBlocks::SPONGE()->setWet(false));
                }
            }
        }

        return false;
    }

    public function absorbWater(Position $center): bool {
        $world = $center->getWorld();
        $yBlock = $center->getY();
        $zBlock = $center->getZ();
        $xBlock = $center->getX();
        $radius = 5;
        $l = false;
        $touchingWater = false;
        for ($x = -1; $x <= 1; ++$x) {
            for ($y = -1; $y <= 1; ++$y) {
                for ($z = -1; $z <= 1; ++$z) {
                    $block = $world->getBlockAt($xBlock + $x, $yBlock + $y, $zBlock + $z);
                    if ($block instanceof Water) {
                        $touchingWater = true;
                    }
                }
            }
        }

        if ($touchingWater) {
            for ($x = $center->getX() - $radius; $x <= $center->getX() + $radius; $x++) {
                $xsqr = ($center->getX() - $x) * ($center->getX() - $x);
                for ($y = $center->getY() - $radius; $y <= $center->getY() + $radius; $y++) {
                    $ysqr = ($center->getY() - $y) * ($center->getY() - $y);
                    for ($z = $center->getZ() - $radius; $z <= $center->getZ() + $radius; $z++) {
                        $zsqr = ($center->getZ() - $z) * ($center->getZ() - $z);
                        if ((($xsqr + $ysqr + $zsqr) <= ($radius * $radius)) && $y > 0 && $world->getBlockAt($x, $y, $z) instanceof Water) {
                            $l = true;
                            $world->setBlock(new Vector3($x, $y, $z), VanillaBlocks::AIR());
                        }
                    }
                }
            }
        }

        return $l;
    }
}
