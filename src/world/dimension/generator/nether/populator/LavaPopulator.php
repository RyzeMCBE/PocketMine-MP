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

namespace pocketmine\world\dimension\generator\nether\populator;

use pocketmine\world\ChunkManager;
use pocketmine\world\dimension\generator\ChunkHash;
use pocketmine\world\dimension\generator\math\GenerationMath;
use pocketmine\world\dimension\generator\nether\object\SeededPopulator;
use pocketmine\world\dimension\generator\nether\object\WorldQuery;
use pocketmine\world\dimension\generator\object\BlockManager;

final class LavaPopulator extends SeededPopulator{

	public function populate(BlockManager $root, ChunkManager $world, int $chunkX, int $chunkZ, int $seed) : void{
		$random = $this->random;
		$random->setSeed($seed ^ ChunkHash::hash($chunkX, $chunkZ));
		$object = new BlockManager($world);
		$amount = $random->nextBoundedInt(30) - 29;

		for($i = 0; $i < $amount; ++$i){
			$x = GenerationMath::randomRange($random, $chunkX << 4, ($chunkX << 4) + 15);
			$z = GenerationMath::randomRange($random, $chunkZ << 4, ($chunkZ << 4) + 15);
			$y = $this->getHighestWorkableBlock($object, $x, $z);
			if($y <= 1){
				continue;
			}
			if($random->nextInt(4) === 1){
				continue;
			}
			$object->setBlockStateAt($x, $y + 1, $z, "minecraft:lava");
		}
		$root->merge($object);
	}

	private function getHighestWorkableBlock(BlockManager $level, int $x, int $z) : int{
		$y = 127;
		for(; $y >= 0; $y--){
			if($level->getBlockIdAt($x, $y, $z) === WorldQuery::AIR){
				break;
			}
		}
		return $y === 0 ? -1 : $y;
	}
}
