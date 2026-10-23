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

use pocketmine\world\dimension\generator\density\BlendedNoise;
use pocketmine\world\dimension\generator\density\DensityFunction;
use pocketmine\world\dimension\generator\density\NetherDensity;
use pocketmine\world\dimension\generator\noise\NormalNoise;
use pocketmine\world\dimension\generator\random\RandomSource;

/**
 * Noises of the nether terrain and surface. Each noise is drawn from a
 * fresh source seeded with the world seed.
 */
final class NetherTerrainHolder extends RandomizedObjectHolder{

	private NormalNoise $surfaceNoise;
	private NormalNoise $patchNoise;
	private NormalNoise $soulsandNoise;
	private NormalNoise $netherStateNoise;
	private NormalNoise $netherwartNoise;
	private DensityFunction $base3dNoise;
	private DensityFunction $densityFunction;

	public function __construct(RandomSource $randomSourceProvider){
		parent::__construct($randomSourceProvider);
		$this->surfaceNoise = new NormalNoise($randomSourceProvider->identical(), -6, [1.0, 1.0, 1.0]);
		$this->patchNoise = new NormalNoise($randomSourceProvider->identical(), -5, [1.0, 0.0, 0.0, 0.0, 0.0, 0.013333333333333334]);
		$this->soulsandNoise = new NormalNoise($randomSourceProvider->identical(), -8, [1.0, 1.0, 1.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.013333333333333334]);
		$this->netherStateNoise = new NormalNoise($randomSourceProvider->identical(), -4, [1.0]);
		$this->netherwartNoise = new NormalNoise($randomSourceProvider->identical(), -3, [1.0, 0.0, 0.0, 0.9]);
		$this->base3dNoise = BlendedNoise::nether($randomSourceProvider->identical());
		$this->densityFunction = NetherDensity::finalDensity($this->base3dNoise);
	}

	public function getSurfaceNoise() : NormalNoise{
		return $this->surfaceNoise;
	}

	public function getPatchNoise() : NormalNoise{
		return $this->patchNoise;
	}

	public function getSoulsandNoise() : NormalNoise{
		return $this->soulsandNoise;
	}

	public function getNetherStateNoise() : NormalNoise{
		return $this->netherStateNoise;
	}

	public function getNetherwartNoise() : NormalNoise{
		return $this->netherwartNoise;
	}

	public function getBase3dNoise() : DensityFunction{
		return $this->base3dNoise;
	}

	public function getDensityFunction() : DensityFunction{
		return $this->densityFunction;
	}
}
