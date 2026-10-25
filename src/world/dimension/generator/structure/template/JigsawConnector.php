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

namespace pocketmine\world\dimension\generator\structure\template;

use pocketmine\data\bedrock\block\BlockStateData;

/**
 * A jigsaw block of a structure template: where it sits, what it turns into
 * once the structure is assembled, and which pieces it may connect to.
 */
final class JigsawConnector{

	public function __construct(
		public readonly int $x,
		public readonly int $y,
		public readonly int $z,
		public readonly ?BlockStateData $finalState,
		public readonly string $name,
		public readonly string $joint,
		public readonly string $pool,
		public readonly string $target,
		public readonly int $placementPriority,
		public readonly int $selectionPriority
	){}

	public function withPosition(int $x, int $y, int $z) : JigsawConnector{
		return new JigsawConnector($x, $y, $z, $this->finalState, $this->name, $this->joint, $this->pool, $this->target, $this->placementPriority, $this->selectionPriority);
	}

	public function withFinalState(?BlockStateData $finalState) : JigsawConnector{
		return new JigsawConnector($this->x, $this->y, $this->z, $finalState, $this->name, $this->joint, $this->pool, $this->target, $this->placementPriority, $this->selectionPriority);
	}
}
