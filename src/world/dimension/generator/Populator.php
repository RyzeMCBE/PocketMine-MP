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

namespace pocketmine\world\dimension\generator;

use pocketmine\world\ChunkManager;
use pocketmine\world\dimension\generator\object\BlockManager;

/**
 * A decoration pass run on a chunk once its neighbours exist. A populator
 * seeds its own random from the world seed and the chunk, reads the world as
 * it was before this population pass, and queues its changes into the root
 * BlockManager, which the generator applies once every populator of the chunk
 * has run.
 *
 * Populators run on the generation worker threads: they must not touch the
 * server, a World, or any shared mutable state.
 */
interface Populator{

	public function populate(BlockManager $root, ChunkManager $world, int $chunkX, int $chunkZ, int $seed) : void;
}
