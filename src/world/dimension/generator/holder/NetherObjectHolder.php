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

namespace pocketmine\world\dimension\generator\holder;

use pocketmine\world\dimension\generator\random\RandomSource;

/**
 * Generation objects of the nether, built from the world seed.
 */
final class NetherObjectHolder extends RandomizedObjectHolder{

	private NetherTerrainHolder $terrainHolder;
	private BasaltDeltaHolder $basaltDeltasHolder;

	public function __construct(RandomSource $randomSourceProvider){
		parent::__construct($randomSourceProvider);
		$this->terrainHolder = new NetherTerrainHolder($randomSourceProvider);
		$this->basaltDeltasHolder = new BasaltDeltaHolder($randomSourceProvider);
	}

	public function getTerrainHolder() : NetherTerrainHolder{
		return $this->terrainHolder;
	}

	public function getBasaltDeltasHolder() : BasaltDeltaHolder{
		return $this->basaltDeltasHolder;
	}
}
