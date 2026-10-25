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

use pocketmine\world\dimension\generator\noise\OctaveNoise;
use pocketmine\world\dimension\generator\noise\SimplexNoise;
use pocketmine\world\dimension\generator\random\RandomSource;

/**
 * Noises of the end terrain, drawn one after the other from a single copy
 * of the world seeded source: two 16 octave roughness noises, the 8 octave
 * detail noise that blends them, then the island placement noise.
 */
final class EndTerrainHolder extends RandomizedObjectHolder{

	private OctaveNoise $roughnessNoiseOctaves;
	private OctaveNoise $roughnessNoiseOctaves2;
	private OctaveNoise $detailNoiseOctaves;
	private SimplexNoise $islandNoise;

	public function __construct(RandomSource $randomSourceProvider){
		parent::__construct($randomSourceProvider);
		$random = $randomSourceProvider->identical();
		$this->roughnessNoiseOctaves = new OctaveNoise($random, 16);
		$this->roughnessNoiseOctaves2 = new OctaveNoise($random, 16);
		$this->detailNoiseOctaves = new OctaveNoise($random, 8);
		$this->islandNoise = new SimplexNoise($random);
	}

	public function getRoughnessNoiseOctaves() : OctaveNoise{
		return $this->roughnessNoiseOctaves;
	}

	public function getRoughnessNoiseOctaves2() : OctaveNoise{
		return $this->roughnessNoiseOctaves2;
	}

	public function getDetailNoiseOctaves() : OctaveNoise{
		return $this->detailNoiseOctaves;
	}

	public function getIslandNoise() : SimplexNoise{
		return $this->islandNoise;
	}
}
