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

namespace pocketmine\block\inventory;

use pocketmine\inventory\SimpleInventory;
use pocketmine\world\Position;
use function min;

class HopperInventory extends SimpleInventory implements BlockInventory{
	use BlockInventoryTrait;

	public function __construct(Position $holder, int $size = 5){
		$this->holder = $holder;
		parent::__construct($size);
	}

	/** Avoid cloning Item/NBT objects for empty/full checks in the transfer loop. */
	public function isEmpty() : bool{
		foreach($this->slots as $item){
			if($item !== null && !$item->isNull()){ return false; }
		}
		return true;
	}

	public function isFull() : bool{
		foreach($this->slots as $item){
			if($item === null || $item->isNull() || $item->getCount() < min($this->getMaxStackSize(), $item->getMaxStackSize())){ return false; }
		}
		return true;
	}
}
