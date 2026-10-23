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

namespace pocketmine\world\dimension\generator\structure\jigsaw;

use pocketmine\world\dimension\generator\random\RandomSource;

/**
 * Weighted list of templates a jigsaw block can pull a piece from.
 */
final class StructurePool{

	/**
	 * @param list<PoolEntry> $entries
	 */
	public function __construct(
		public readonly string $name,
		public readonly array $entries,
		public readonly ?string $fallback = null
	){}

	public function getRandomEntry(RandomSource $random) : PoolEntry{
		$totalWeight = 0;
		foreach($this->entries as $entry){
			$totalWeight += $entry->weight;
		}
		$target = $random->nextBoundedInt($totalWeight - 1);
		foreach($this->entries as $entry){
			if($target < $entry->weight){
				return $entry;
			}
			$target -= $entry->weight;
		}
		throw new \RuntimeException("Failed to select structure from pool '$this->name'");
	}
}
