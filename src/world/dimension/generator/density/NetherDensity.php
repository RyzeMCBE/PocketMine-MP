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

namespace pocketmine\world\dimension\generator\density;

/**
 * Final terrain density of the nether: the base noise faded toward solid
 * near the floor (Y -8 to 24) and the roof (Y 104 to 128), interpolated per
 * cell, scaled and squeezed.
 */
final class NetherDensity{

	private function __construct(){
	}

	public static function finalDensity(DensityFunction $base3dNoise) : DensityFunction{
		$density = Densities::add(Densities::constant(-0.9375), $base3dNoise);
		$density = Densities::mul(Densities::yClampedGradient(104, 128, 1.0, 0.0), $density);
		$density = Densities::add(Densities::constant(0.9375), $density);
		$density = Densities::add(Densities::constant(-2.5), $density);
		$density = Densities::mul(Densities::yClampedGradient(-8, 24, 0.0, 1.0), $density);
		$density = Densities::add(Densities::constant(2.5), $density);
		$density = Densities::blendDensity($density);
		$density = Densities::interpolated($density);
		$density = Densities::mul(Densities::constant(0.64), $density);
		return Densities::map($density, MappedDensity::SQUEEZE);
	}
}
