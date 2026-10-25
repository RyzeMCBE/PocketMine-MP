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

namespace pocketmine\event\player;

use pocketmine\network\mcpe\DimensionTransition;
use pocketmine\player\Player;

/** A transition observation, not a guarantee that the destination has rendered. Not cancellable. */
final class PlayerDimensionChangeEvent extends PlayerEvent{

	public const STARTED = "started";
	public const SCREEN_STOPPED = "screen_stopped";
	public const CLIENT_ACKNOWLEDGED = "client_acknowledged";
	public const REPLACED = "replaced";
	public const DISCONNECTED = "disconnected";
	public const TIMED_OUT = "timed_out";
	public const SEND_CANCELLED = "send_cancelled";

	public function __construct(Player $player, private DimensionTransition $transition, private string $state){
		$this->player = $player;
		$this->transition = clone $transition;
	}

	public function getState() : string{ return $this->state; }
	public function getTransition() : DimensionTransition{ return clone $this->transition; }
}
