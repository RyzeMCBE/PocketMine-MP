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

namespace pocketmine\network\mcpe;

use PHPUnit\Framework\TestCase;
use pocketmine\entity\Entity;
use pocketmine\player\Player;
use pocketmine\promise\PromiseResolver;
use pocketmine\world\ChunkLoader;
use pocketmine\world\World;
use pocketmine\world\WorldTimings;

final class DimensionChunkPriorityTest extends TestCase{

	public function testPrioritizesOnlyQueuedArrivalChunksAndPreservesInflightBudget() : void{
		$near = World::chunkHash(0, 0);
		$near2 = World::chunkHash(1, 0);
		$far = World::chunkHash(10, 10);
		$notRequested = World::chunkHash(-1, 0);
		$session = $this->createMock(NetworkSession::class);
		$session->method("getDimensionArrivalChunks")->willReturn([$notRequested => true, $near => true, $near2 => true]);
		$world = $this->createMock(World::class);
		$world->timings = new WorldTimings($world);
		/** @var \ArrayObject<int, array{int, int}> $requests */
		$requests = new \ArrayObject();
		$resolvers = [];
		$world->method("requestChunkPopulation")->willReturnCallback(function(int $x, int $z) use(&$requests, &$resolvers){
			$requests[] = [$x, $z];
			$resolvers[] = $resolver = new PromiseResolver();
			return $resolver->getPromise();
		});
		$player = $this->createPartialMock(Player::class, ["getWorld", "getNetworkSession", "isConnected"]);
		(new \ReflectionProperty(Entity::class, "closed"))->setValue($player, true);
		(new \ReflectionProperty(Player::class, "logger"))->setValue($player, new \PrefixedLogger($this->createMock(\Logger::class), "test"));
		$player->method("getWorld")->willReturn($world);
		$player->method("getNetworkSession")->willReturn($session);
		$player->method("isConnected")->willReturn(true);
		foreach([
			"chunksPerTick" => 2,
			"chunkLoader" => new class implements ChunkLoader{},
			"loadQueue" => [$far => 200, $near2 => 1, $near => 0],
			"activeChunkGenerationRequests" => [World::chunkHash(20, 20) => true]
		] as $name => $value){
			(new \ReflectionProperty(Player::class, $name))->setValue($player, $value);
		}
		$method = new \ReflectionMethod(Player::class, "requestChunks");
		$method->invoke($player);
		self::assertSame([[0, 0]], $requests->getArrayCopy());
		$method->invoke($player);
		self::assertSame([[0, 0]], $requests->getArrayCopy()); // one old + one new request exhausts the configured limit
		self::assertSame([$far => 200, $near2 => 1], (new \ReflectionProperty(Player::class, "loadQueue"))->getValue($player));
	}
}
