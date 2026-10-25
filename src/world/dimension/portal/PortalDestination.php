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

namespace pocketmine\world\dimension\portal;

use InvalidArgumentException;
use pocketmine\entity\Location;
use function abs;
use function is_finite;

/** Exact entity feet coordinates and orientation; stores no reference to a loaded World. */
final class PortalDestination{

	/** Coordinates are not scaled or centred, and must already describe a safe arrival. */
	public function __construct(
		public readonly string $worldName,
		public readonly float $x,
		public readonly float $y,
		public readonly float $z,
		public readonly float $yaw = 0.0,
		public readonly float $pitch = 0.0
	){
		PortalArea::validateWorldName($worldName);
		foreach([$x, $y, $z, $yaw, $pitch] as $value){
			if(!is_finite($value)){
				throw new InvalidArgumentException("Portal destination coordinates and orientation must be finite");
			}
		}
		if(abs($x) > 29999872 || abs($z) > 29999872 || $y < -63 || $y > 318 || $pitch < -90 || $pitch > 90){
			throw new InvalidArgumentException("Portal destination is outside the supported coordinate or pitch bounds");
		}
	}

	public static function fromLocation(Location $location) : self{
		return new self($location->getWorld()->getFolderName(), $location->x, $location->y, $location->z, $location->yaw, $location->pitch);
	}
}
