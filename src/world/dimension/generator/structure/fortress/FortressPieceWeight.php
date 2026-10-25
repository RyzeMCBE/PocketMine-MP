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

/**
 * Weight and placement limit of one fortress piece type while a fortress
 * is laid out. A limit of 0 means unlimited.
 */
final class FortressPieceWeight{

	public int $placeCount = 0;

	/**
	 * @param class-string<FortressPiece> $pieceType
	 */
	public function __construct(
		public readonly string $pieceType,
		public readonly int $weight,
		public readonly int $maxPlaceCount,
		public readonly bool $allowInRow = false
	){}

	public function doPlace(int $genDepth) : bool{
		return $this->maxPlaceCount === 0 || $this->placeCount < $this->maxPlaceCount;
	}

	public function isValid() : bool{
		return $this->maxPlaceCount === 0 || $this->placeCount < $this->maxPlaceCount;
	}

	/**
	 * @return list<FortressPieceWeight>
	 */
	public static function bridgePieces() : array{
		return [
			new FortressPieceWeight(BridgeStraight::class, 30, 0, true),
			new FortressPieceWeight(BridgeCrossing::class, 10, 4),
			new FortressPieceWeight(RoomCrossing::class, 10, 4),
			new FortressPieceWeight(StairsRoom::class, 10, 3),
			new FortressPieceWeight(MonsterThrone::class, 5, 2),
			new FortressPieceWeight(CastleEntrance::class, 5, 1)
		];
	}

	/**
	 * @return list<FortressPieceWeight>
	 */
	public static function castlePieces() : array{
		return [
			new FortressPieceWeight(CastleSmallCorridor::class, 25, 0, true),
			new FortressPieceWeight(CastleSmallCorridorCrossing::class, 15, 5),
			new FortressPieceWeight(CastleSmallCorridorRightTurn::class, 5, 10),
			new FortressPieceWeight(CastleSmallCorridorLeftTurn::class, 5, 10),
			new FortressPieceWeight(CastleCorridorStairs::class, 10, 3, true),
			new FortressPieceWeight(CastleCorridorTBalcony::class, 7, 2),
			new FortressPieceWeight(CastleStalkRoom::class, 5, 2)
		];
	}
}
