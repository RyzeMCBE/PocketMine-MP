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
use pocketmine\world\dimension\generator\end\object\ExitPortal;
use pocketmine\world\dimension\generator\nether\object\WorldQuery;
use pocketmine\world\dimension\generator\object\BlockManager;
use pocketmine\world\dimension\generator\Populator;

/**
 * Builds the exit portal frame on top of the main island at 0, 0. It runs
 * for chunk 0, 0 so that every block of the frame lies in a chunk the
 * population pass can write.
 */
final class ExitPortalPopulator implements Populator{

	public function populate(BlockManager $root, ChunkManager $world, int $chunkX, int $chunkZ, int $seed) : void{
		if($chunkX === 0 && $chunkZ === 0){
			$object = new BlockManager($world);
			(new ExitPortal())->generate($object, 0, WorldQuery::heightMap($world, 0, 0), 0);
			$root->merge($object);
		}
	}
}
