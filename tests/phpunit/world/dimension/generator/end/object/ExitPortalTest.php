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

namespace pocketmine\world\dimension\generator\end\object;

use LogicException;
use PHPUnit\Framework\TestCase;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\world\dimension\generator\object\BlockManager;
use pocketmine\world\dimension\portal\block\EndPortal;
use pocketmine\world\dimension\portal\PortalContent;
use pocketmine\world\SimpleChunkManager;

final class ExitPortalTest extends TestCase{

	public function testActiveExitPortalContainsTwentyPortalBlocks() : void{
		try{
			PortalContent::endPortal();
		}catch(LogicException){
			PortalContent::registerBlock(BlockTypeIds::newId());
		}

		$manager = new BlockManager(new SimpleChunkManager(0, 256));
		(new ExitPortal())->generate($manager, 0, 64, 0);

		$portalBlocks = 0;
		$centerBedrock = false;
		foreach($manager->getBlocks() as $placed){
			if($placed->y === 64 && $placed->block instanceof EndPortal){
				++$portalBlocks;
			}
			if($placed->x === 0 && $placed->y === 64 && $placed->z === 0 && $placed->block->getTypeId() === VanillaBlocks::BEDROCK()->getTypeId()){
				$centerBedrock = true;
			}
		}

		self::assertSame(20, $portalBlocks);
		self::assertTrue($centerBedrock);
	}
}
