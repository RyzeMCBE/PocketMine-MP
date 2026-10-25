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

namespace pocketmine\world\dimension\generator\structure\fortress;

use pocketmine\world\dimension\generator\object\BlockManager;
use pocketmine\world\dimension\generator\random\RandomSource;
use pocketmine\world\dimension\generator\random\Xoroshiro128;
use pocketmine\world\dimension\generator\structure\BoundingBox;
use pocketmine\world\dimension\generator\structure\StructurePiece;

/**
 * Crumbling end of a bridge. Its jagged outline comes from a seed drawn
 * when the piece is laid out, so the piece looks the same in every chunk
 * it crosses.
 */
final class BridgeEndFiller extends FortressPiece{

	private int $selfSeed;

	public function __construct(int $genDepth, RandomSource $random, BoundingBox $boundingBox, int $orientation){
		parent::__construct($genDepth);
		$this->setOrientation($orientation);
		$this->boundingBox = $boundingBox;
		$this->selfSeed = $random->nextInt();
	}

	public static function createPiece(array $pieces, RandomSource $random, int $x, int $y, int $z, int $orientation, int $genDepth) : ?FortressPiece{
		$boundingBox = BoundingBox::orient($x, $y, $z, -1, -3, 0, 5, 10, 8, $orientation);
		return self::isOkBox($boundingBox) && StructurePiece::findCollisionPiece($pieces, $boundingBox) === null ? new BridgeEndFiller($genDepth, $random, $boundingBox, $orientation) : null;
	}

	public function postProcess(BlockManager $level, RandomSource $random, BoundingBox $boundingBox, int $chunkX, int $chunkZ) : bool{
		$rand = new Xoroshiro128($this->selfSeed);
		$bricks = FortressBlocks::netherBricks();

		for($x = 0; $x <= 4; ++$x){
			for($y = 3; $y <= 4; ++$y){
				$this->generateBox($level, $boundingBox, $x, $y, 0, $x, $y, $rand->nextBoundedInt(8), $bricks, $bricks, false);
			}
		}

		$this->generateBox($level, $boundingBox, 0, 5, 0, 0, 5, $rand->nextBoundedInt(8), $bricks, $bricks, false);
		$this->generateBox($level, $boundingBox, 4, 5, 0, 4, 5, $rand->nextBoundedInt(8), $bricks, $bricks, false);

		for($x = 0; $x <= 4; ++$x){
			$this->generateBox($level, $boundingBox, $x, 2, 0, $x, 2, $rand->nextBoundedInt(5), $bricks, $bricks, false);
		}

		for($x = 0; $x <= 4; ++$x){
			for($y = 0; $y <= 1; ++$y){
				$this->generateBox($level, $boundingBox, $x, $y, 0, $x, $y, $rand->nextBoundedInt(3), $bricks, $bricks, false);
			}
		}
		return true;
	}
}
