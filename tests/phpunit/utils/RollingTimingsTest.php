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

final class RollingTimingsTest extends TestCase{

	public function testNearestRankPercentilesAndBoundedHistory() : void{
		$timings = new RollingTimings(100);
		self::assertSame(0, $timings->summarize()['count']);
		for($i = 1; $i <= 100; ++$i){ $timings->add($i); }
		self::assertSame(['count' => 100, 'mean' => 50.5, 'p95' => 95.0, 'p99' => 99.0, 'max' => 100.0], $timings->summarize());
		for($i = 0; $i < 100; ++$i){ $timings->add(2.0); }
		self::assertSame(['count' => 100, 'mean' => 2.0, 'p95' => 2.0, 'p99' => 2.0, 'max' => 2.0], $timings->summarize());
	}
}
