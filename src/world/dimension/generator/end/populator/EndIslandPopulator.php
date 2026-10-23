<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\world\dimension\generator\end\populator;

use pocketmine\world\ChunkManager;
use pocketmine\world\dimension\generator\ChunkHash;
use pocketmine\world\dimension\generator\end\EndIslands;
use pocketmine\world\dimension\generator\end\object\EndIsland;
use pocketmine\world\dimension\generator\object\BlockManager;
use pocketmine\world\dimension\generator\Populator;
use pocketmine\world\dimension\generator\random\LegacyRandom;

final class EndIslandPopulator implements Populator{

	private LegacyRandom $random;
	private EndIsland $endIsland;

	public function __construct(){
		$this->random = new LegacyRandom(0);
		$this->endIsland = new EndIsland();
	}

	public function populate(BlockManager $root, ChunkManager $world, int $chunkX, int $chunkZ, int $seed) : void{
		if($chunkX * $chunkX + $chunkZ * $chunkZ <= 4096){
			return;
		}
		$random = $this->random;
		$random->setSeed($seed ^ ChunkHash::hash($chunkX, $chunkZ));
		if($random->nextBoundedInt(14) === 0){
			$height = EndIslands::height($seed, $chunkX, $chunkZ, 1, 1);
			if($height < -20.0){
				$baseX = $chunkX << 4;
				$baseZ = $chunkZ << 4;
				$object = new BlockManager($world);
				$x = $baseX + 8 + $random->nextBoundedInt(16);
				$y = 55 + $random->nextBoundedInt(16);
				$z = $baseZ + 8 + $random->nextBoundedInt(16);
				$this->endIsland->generate($object, $random, $x, $y, $z);
				if($random->nextBoundedInt(4) === 0){
					$x = $baseX + 8 + $random->nextBoundedInt(16);
					$y = 55 + $random->nextBoundedInt(16);
					$z = $baseZ + 8 + $random->nextBoundedInt(16);
					$this->endIsland->generate($object, $random, $x, $y, $z);
				}
				$root->merge($object);
			}
		}
	}
}
