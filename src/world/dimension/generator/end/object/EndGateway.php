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

use pocketmine\block\Block;
use pocketmine\world\dimension\generator\Blocks;
use pocketmine\world\dimension\generator\object\BlockManager;

/**
 * End gateway block wrapped in bedrock above and below. When the server has
 * no end gateway block the centre is left as air.
 */
final class EndGateway{

	private const FACES = [[0, -1, 0], [0, 1, 0], [0, 0, -1], [0, 0, 1], [-1, 0, 0], [1, 0, 0]];

	private ?Block $gateway = null;
	private bool $resolved = false;

	private function gatewayBlock() : ?Block{
		if(!$this->resolved){
			$this->resolved = true;
			try{
				$this->gateway = Blocks::get("minecraft:end_gateway");
			}catch(\InvalidArgumentException){
				$this->gateway = null;
			}
		}
		return $this->gateway;
	}

	public function generate(BlockManager $level, int $x, int $y, int $z) : void{
		foreach(self::FACES as [$dx, $dy, $dz]){
			$level->setBlockStateAt($x + $dx, $y + 1 + $dy, $z + $dz, "minecraft:bedrock");
		}
		foreach(self::FACES as [$dx, $dy, $dz]){
			$level->setBlockStateAt($x + $dx, $y - 1 + $dy, $z + $dz, "minecraft:bedrock");
		}
		$level->setBlockStateAt($x, $y, $z, $this->gatewayBlock() ?? "minecraft:air");
	}
}
