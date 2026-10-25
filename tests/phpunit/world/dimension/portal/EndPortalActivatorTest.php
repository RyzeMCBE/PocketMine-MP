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

use LogicException;
use PHPUnit\Framework\TestCase;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Facing;
use pocketmine\world\dimension\portal\block\EndPortal;
use pocketmine\world\World;

final class EndPortalActivatorTest extends TestCase{

	public function testCompleteRingActivatesThreeByThreePortal() : void{
		try{
			PortalContent::endPortal();
		}catch(LogicException){
			PortalContent::registerBlock(BlockTypeIds::newId());
		}

		$y = 64;
		$blocks = [];
		for($i = -1; $i <= 1; ++$i){
			$blocks["-2:$y:$i"] = VanillaBlocks::END_PORTAL_FRAME()->setFacing(Facing::EAST)->setEye(true);
			$blocks["2:$y:$i"] = VanillaBlocks::END_PORTAL_FRAME()->setFacing(Facing::WEST)->setEye(true);
			$blocks["$i:$y:-2"] = VanillaBlocks::END_PORTAL_FRAME()->setFacing(Facing::SOUTH)->setEye(true);
			$blocks["$i:$y:2"] = VanillaBlocks::END_PORTAL_FRAME()->setFacing(Facing::NORTH)->setEye(true);
		}

		$world = $this->createMock(World::class);
		$world->method("getBlockAt")->willReturnCallback(
			static function(int $x, int $yy, int $z) use (&$blocks) : Block{
				return clone ($blocks["$x:$yy:$z"] ?? VanillaBlocks::AIR());
			}
		);

		$placed = [];
		$world->expects(self::exactly(9))
			->method("setBlockAt")
			->willReturnCallback(
				static function(int $x, int $yy, int $z, Block $block) use (&$placed) : void{
					$placed["$x:$yy:$z"] = clone $block;
				}
			);
		$world->expects(self::once())->method("addSound");

		self::assertTrue(EndPortalActivator::tryActivate($world, -2, $y, 0));
		self::assertCount(9, $placed);
		foreach($placed as $block){
			self::assertInstanceOf(EndPortal::class, $block);
		}
		for($x = -1; $x <= 1; ++$x){
			for($z = -1; $z <= 1; ++$z){
				self::assertArrayHasKey("$x:$y:$z", $placed);
			}
		}
	}
}
