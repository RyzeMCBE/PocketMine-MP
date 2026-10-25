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

namespace pocketmine\world;

use PHPUnit\Framework\TestCase;
use pocketmine\math\Vector3;

final class ScheduledBlockUpdateQueueTest extends TestCase{

	public function testEarlierUpdateDoesNotCreateAnotherRecurringChain() : void{
		$queue = new ScheduledBlockUpdateQueue();
		$position = new Vector3(1, 64, -2);
		$queue->schedule($position, 8);
		$queue->schedule($position, 1);
		$executed = [];
		for($tick = 0; $tick <= 40; ++$tick){
			while(($ready = $queue->fetchReady($tick)) !== null){
				$executed[] = $tick;
				$queue->schedule($ready, $tick + 8);
			}
		}
		self::assertSame([1, 9, 17, 25, 33], $executed);
	}

	public function testComparesAbsoluteDeadlinesAndRetainsEarliest() : void{
		$queue = new ScheduledBlockUpdateQueue();
		$position = new Vector3(0, 64, 0);
		$queue->schedule($position, 108);
		$queue->schedule($position, 110); // shorter relative delay requested later must not postpone 108
		self::assertNull($queue->fetchReady(107));
		self::assertEquals($position, $queue->fetchReady(108));
		self::assertNull($queue->fetchReady(110));
	}

	public function testPositionsAndReplacementIdentitiesAreIndependent() : void{
		$queue = new ScheduledBlockUpdateQueue();
		$a = new Vector3(-1, -64, 0);
		$b = new Vector3(0, 319, 1);
		$queue->schedule($a, 10);
		$queue->schedule($b, 8);
		$queue->schedule($a, 2);
		self::assertEquals($a, $queue->fetchReady(2));
		$queue->schedule($a, 10); // same deadline as stale entry, different identity
		self::assertEquals($b, $queue->fetchReady(8));
		self::assertEquals($a, $queue->fetchReady(10));
		self::assertNull($queue->fetchReady(10));
	}
}
