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

namespace pocketmine\utils;

use PHPUnit\Framework\TestCase;

final class IncrementalTaskQueueTest extends TestCase{

	public function testTimeBudgetAndRoundRobinAcrossWorlds() : void{
		$clock = 0;
		$queue = new IncrementalTaskQueue(static function() use(&$clock) : int{ return $clock; });
		/** @var \ArrayObject<int, string> $completed */
		$completed = new \ArrayObject();
		$jobs = static function(string $world) use(&$clock, &$completed) : \Generator{
			for($i = 0; $i < 3; ++$i){
				yield static function() use($world, $i, &$clock, &$completed) : void{ $completed[] = "$world:$i"; $clock += 100; };
			}
		};
		$queue->add($jobs('a'));
		$queue->add($jobs('b'));
		self::assertSame(2, $queue->process(150, 99));
		self::assertSame(['a:0', 'b:0'], $completed->getArrayCopy());
		self::assertSame(1, $queue->process(9999, 1));
		self::assertSame(['a:0', 'b:0', 'a:1'], $completed->getArrayCopy());
		self::assertTrue($queue->hasWork());
		self::assertSame(3, $queue->process(9999, 99));
		self::assertFalse($queue->hasWork());
	}

	public function testWriteFailureCanRetryWithoutSkippingOrReplayingSuccessfulWrites() : void{
		$queue = new IncrementalTaskQueue();
		$attempts = 0;
		$writes = [];
		$queue->add((static function() use(&$attempts, &$writes) : \Generator{
			yield static function() use(&$writes) : void{ $writes[] = 'first'; };
			yield static function() use(&$attempts, &$writes) : void{
				if(++$attempts === 1){ throw new \RuntimeException('disk failure'); }
				$writes[] = 'second';
			};
		})());
		try{
			$queue->process(1000000000, 99);
			self::fail('Expected write failure');
		}catch(\RuntimeException $e){ self::assertSame('disk failure', $e->getMessage()); }
		self::assertTrue($queue->hasWork());
		self::assertSame(1, $queue->process(1000000000, 99));
		self::assertSame(['first', 'second'], $writes);
		self::assertFalse($queue->hasWork());
	}
}
