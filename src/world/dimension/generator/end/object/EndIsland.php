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

namespace pocketmine\world\dimension\generator\end\object;

use pocketmine\world\dimension\generator\math\Float32;
use pocketmine\world\dimension\generator\math\GenerationMath;
use pocketmine\world\dimension\generator\object\BlockManager;
use pocketmine\world\dimension\generator\random\LegacyRandom;

/**
 * Small floating end stone island: stacked discs shrinking downward.
 */
final class EndIsland{

	public function generate(BlockManager $level, LegacyRandom $rand, int $posX, int $posY, int $posZ) : void{
		$n = (float) ($rand->nextInt(2) + 4);
		for($y = 0; $n > 0.5; $y--){
			$limit = Float32::of(($n + 1.0) * ($n + 1.0));
			for($x = GenerationMath::floorFloat(-$n); $x <= GenerationMath::ceilFloat($n); $x++){
				for($z = GenerationMath::floorFloat(-$n); $z <= GenerationMath::ceilFloat($n); $z++){
					if((float) ($x * $x + $z * $z) <= $limit){
						$level->setBlockStateAt($posX + $x, $posY + $y, $posZ + $z, "minecraft:end_stone");
					}
				}
			}
			$n = Float32::of($n - ($rand->nextInt(1) + 0.5));
		}
	}
}
