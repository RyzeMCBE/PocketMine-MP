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

namespace pocketmine\block;

use PHPUnit\Framework\TestCase;
use pocketmine\block\inventory\HopperInventory;
use pocketmine\block\tile\Hopper as HopperTile;
use pocketmine\item\VanillaItems;
use pocketmine\world\Position;
use pocketmine\world\World;

final class HopperInventoryPerformanceTest extends TestCase{

	public function testFastStateChecksTrackSlotAndBulkChangesAndStackLimits() : void{
		$inventory = new HopperInventory(new Position(0, 64, 0, null));
		self::assertTrue($inventory->isEmpty());
		self::assertFalse($inventory->isFull());
		for($slot = 0; $slot < 5; ++$slot){ $inventory->setItem($slot, VanillaItems::APPLE()->setCount(64)); }
		self::assertFalse($inventory->isEmpty());
		self::assertTrue($inventory->isFull());
		$inventory->setItem(3, VanillaItems::APPLE()->setCount(63));
		self::assertFalse($inventory->isFull());
		$inventory->setMaxStackSize(16);
		self::assertTrue($inventory->isFull());
		$inventory->clearAll();
		self::assertTrue($inventory->isEmpty());
		self::assertFalse($inventory->isFull());
	}

	public function testPoweredHopperSleepsAndNeighbourChangeReactivatesIt() : void{
		$world = $this->createMock(World::class);
		$tile = $this->createMock(HopperTile::class);
		$world->method('getTile')->willReturn($tile);
		$world->method('isLoaded')->willReturn(true);
		$world->method('isChunkLoaded')->willReturn(true);
		$powered = true;
		// Capture by reference because the direct power source changes between updates.
		$world->method('getBlock')->willReturnCallback(static function() use(&$powered) : Block{ return VanillaBlocks::LEVER()->setActivated($powered); });
		/** @var \ArrayObject<int, int> $delays */
		$delays = new \ArrayObject();
		$world->method('scheduleDelayedBlockUpdate')->willReturnCallback(static function($position, int $delay) use(&$delays) : void{ $delays[] = $delay; });
		$hopper = VanillaBlocks::HOPPER();
		$hopper->position($world, 0, 64, 0);
		$hopper->onScheduledUpdate();
		self::assertSame([], $delays->getArrayCopy());
		$powered = false;
		$hopper->onNearbyBlockChange();
		$hopper->onScheduledUpdate();
		self::assertSame([1, 8], $delays->getArrayCopy());
	}
}
