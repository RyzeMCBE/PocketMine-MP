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

namespace pocketmine\world\dimension\generator\end;

use pocketmine\world\dimension\generator\end\populator\ChorusFlowerPopulator;
use pocketmine\world\dimension\generator\end\populator\EndIslandPopulator;
use pocketmine\world\dimension\generator\end\populator\ExitPortalPopulator;
use pocketmine\world\dimension\generator\end\populator\ObsidianPillarPopulator;
use pocketmine\world\dimension\generator\Populator;
use pocketmine\world\dimension\generator\structure\EndCityPopulator;

/**
 * The end population pass, in execution order.
 *
 * End gateways are intentionally omitted until the core has a real gateway
 * block/tile and teleport behaviour. Generating the bedrock shell with an
 * air centre would create misleading, non-functional structures.
 */
final class EndPopulators{

	private function __construct(){
	}

	/**
	 * @return list<Populator>
	 */
	public static function create(int $seed, string $resourceFolder) : array{
		return [
			new ObsidianPillarPopulator(),
			new ExitPortalPopulator(),
			new ChorusFlowerPopulator(),
			new EndCityPopulator($resourceFolder),
			new EndIslandPopulator(),
		];
	}
}
