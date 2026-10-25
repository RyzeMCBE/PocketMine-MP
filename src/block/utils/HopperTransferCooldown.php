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

namespace pocketmine\block\utils;

use function max;
use function min;

/**
 * Keeps hopper transfer intervals in server ticks, not in invocation counts.
 *
 * An early neighbour update may call the hopper many times during one tick.
 * None of those calls should shorten the transfer cooldown. A saved cooldown
 * is stored as remaining ticks, so it can be restored across a server restart
 * without persisting an absolute server tick number.
 */
final class HopperTransferCooldown{
	private ?int $nextAllowedTick = null;
	private int $pendingDelay = 0;

	public function __construct(
		private int $intervalTicks
	){
		if($intervalTicks < 1){
			throw new \InvalidArgumentException("Hopper transfer interval must be positive");
		}
	}

	public function canTransfer(int $currentTick) : bool{
		if($this->nextAllowedTick === null){
			$this->nextAllowedTick = $currentTick + $this->pendingDelay;
			$this->pendingDelay = 0;
		}

		return $currentTick >= $this->nextAllowedTick;
	}

	public function markTransferred(int $currentTick) : void{
		$this->nextAllowedTick = $currentTick + $this->intervalTicks;
		$this->pendingDelay = 0;
	}

	public function getRemainingTicks(int $currentTick) : int{
		return $this->nextAllowedTick === null ?
			$this->pendingDelay :
			max(0, $this->nextAllowedTick - $currentTick);
	}

	public function loadRemainingTicks(int $remainingTicks) : void{
		$this->nextAllowedTick = null;
		$this->pendingDelay = max(0, min($this->intervalTicks, $remainingTicks));
	}
}
