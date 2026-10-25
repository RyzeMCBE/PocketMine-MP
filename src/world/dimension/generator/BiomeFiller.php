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

use pocketmine\world\format\Chunk;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\format\SubChunk;

/**
 * Writes column biomes into every subchunk of a chunk, over the whole world
 * height.
 */
final class BiomeFiller{

	private function __construct(){
	}

	/**
	 * @param int[] $biomes BiomeIds id per column, indexed x * 16 + z
	 */
	public static function fillColumns(Chunk $chunk, array $biomes) : void{
		$template = new PalettedBlockArray($biomes[0]);
		for($x = 0; $x < 16; $x++){
			for($z = 0; $z < 16; $z++){
				$id = $biomes[$x * 16 + $z];
				for($y = 0; $y < 16; $y++){
					$template->set($x, $y, $z, $id);
				}
			}
		}
		self::apply($chunk, $template);
	}

	public static function fill(Chunk $chunk, int $biomeId) : void{
		self::apply($chunk, new PalettedBlockArray($biomeId));
	}

	private static function apply(Chunk $chunk, PalettedBlockArray $template) : void{
		for($index = Chunk::MIN_SUBCHUNK_INDEX; $index <= Chunk::MAX_SUBCHUNK_INDEX; $index++){
			$old = $chunk->getSubChunk($index);
			$chunk->setSubChunk($index, new SubChunk($old->getEmptyBlockId(), $old->getBlockLayers(), clone $template));
		}
	}
}
