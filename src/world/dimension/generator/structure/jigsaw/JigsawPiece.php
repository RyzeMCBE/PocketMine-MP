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

namespace pocketmine\world\dimension\generator\structure\jigsaw;

use pocketmine\world\dimension\generator\structure\BoundingBox;
use pocketmine\world\dimension\generator\structure\template\JigsawConnector;
use pocketmine\world\dimension\generator\structure\template\StructureTemplate;

/**
 * A template placed during jigsaw assembly, at a position relative to the
 * structure origin.
 */
final class JigsawPiece{

	/**
	 * @param list<JigsawConnector> $sourceJigsaws jigsaws of the unrotated template
	 * @param list<JigsawConnector> $placedJigsaws the same jigsaws after rotation, relative to the piece
	 */
	public function __construct(
		public readonly string $structureName,
		public readonly StructureTemplate $source,
		public readonly int $x,
		public readonly int $y,
		public readonly int $z,
		public readonly int $rotation,
		public readonly BoundingBox $boundingBox,
		public readonly array $sourceJigsaws,
		public readonly array $placedJigsaws
	){}
}
