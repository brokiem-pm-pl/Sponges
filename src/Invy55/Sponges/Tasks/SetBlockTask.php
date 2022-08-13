<?php

declare(strict_types=1);

namespace Invy55\Sponges\Tasks;

use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;
use pocketmine\world\World;

class SetBlockTask extends Task {

    public function __construct(private $plugin, private World $level, private Vector3 $vector, private Block $block, private $firstP) {
    }

    public function getPlugin() {
        return $this->plugin;
    }

    public function onRun(): void {
        $this->level->setBlock($this->vector, $this->block, $this->firstP);
    }
}
