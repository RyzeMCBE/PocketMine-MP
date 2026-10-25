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
use function strlen;
use function trim;

/** An immutable one-way link. Replacing it requires unregistering its stable ID first. */
final class PortalLink{

	public function __construct(
		public readonly string $id,
		public readonly PortalArea $source,
		public readonly PortalDestination $destination
	){
		if(trim($id) === "" || strlen($id) > 128){
			throw new InvalidArgumentException("Portal link ID must contain between 1 and 128 bytes");
		}
	}
}
