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
use pocketmine\scheduler\AsyncPool;
use pocketmine\Server;
use pocketmine\world\format\io\WorldProviderManager;

final class GenerationFairnessTest extends TestCase{

	public function testNextAdmissionStartsWithAnotherWorldAfterCapacityReturns() : void{
		$pool = $this->createMock(AsyncPool::class);
		$pool->method('getSize')->willReturn(2);
		$server = $this->createMock(Server::class);
		$server->method('getAsyncPool')->willReturn($pool);
		$manager = new WorldManager($server, '/unused', new WorldProviderManager());
		$capacity = 1;
		/** @var \ArrayObject<int, string> $started */
		$started = new \ArrayObject();
		$worlds = [];
		foreach(['a', 'b'] as $name){
			$world = $this->createMock(World::class);
			$world->method('processQueuedChunkGeneration')->willReturnCallback(static function() use($name, &$capacity, $started) : int{
				if($capacity < 1){ return 0; }
				--$capacity;
				$started[] = $name;
				return 1;
			});
			$worlds[] = $world;
		}
		(new \ReflectionProperty(WorldManager::class, 'worlds'))->setValue($manager, $worlds);
		$manager->processChunkGenerationQueues();
		$capacity = 1;
		$manager->processChunkGenerationQueues();
		self::assertSame(['a', 'b'], $started->getArrayCopy());
	}
}
