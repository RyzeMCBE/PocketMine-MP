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

final class CpuCapacityTest extends TestCase{

	/** @param array<string, string> $files */
	private function detect(array $files, int $host = 32) : int{
		return CpuCapacity::detect($host, static fn(string $path) : ?string => $files[$path] ?? null);
	}

	public function testContainerRootQuotaAndAffinity() : void{
		self::assertSame(4, $this->detect(['/sys/fs/cgroup/cpu.max' => '400000 100000', '/proc/self/status' => "Cpus_allowed_list:\t0-15\n"]));
		self::assertSame(2, $this->detect(['/sys/fs/cgroup/cpu.max' => '400000 100000', '/proc/self/status' => "Cpus_allowed_list:\t1,3\n"]));
	}

	public function testNestedCgroupAndParentQuotaAreBothObserved() : void{
		self::assertSame(2, $this->detect([
			'/proc/self/cgroup' => '0::/tenant/server',
			'/sys/fs/cgroup/tenant/server/cpu.max' => '250000 100000',
			'/sys/fs/cgroup/tenant/cpu.max' => '200000 100000',
			'/sys/fs/cgroup/cpu.max' => 'max 100000'
		]));
	}

	public function testCgroupV1AndInheritedCpuset() : void{
		self::assertSame(3, $this->detect([
			'/proc/self/cgroup' => "2:cpu,cpuacct:/tenant\n3:cpuset:/tenant",
			'/sys/fs/cgroup/cpu,cpuacct/tenant/cpu.cfs_quota_us' => '800000',
			'/sys/fs/cgroup/cpu,cpuacct/tenant/cpu.cfs_period_us' => '100000',
			'/sys/fs/cgroup/cpuset/tenant/cpuset.cpus' => '',
			'/sys/fs/cgroup/cpuset/cpuset.cpus' => '0,2-3'
		]));
	}

	public function testFractionalQuotaStillAllowsOneWorkerAndMalformedFilesFallBack() : void{
		self::assertSame(1, $this->detect(['/sys/fs/cgroup/cpu.max' => '50000 100000']));
		self::assertSame(6, $this->detect(['/sys/fs/cgroup/cpu.max' => 'broken', '/proc/self/status' => "Cpus_allowed_list: 5-2\n"], 6));
		self::assertSame(1, $this->detect([], 0));
	}

	public function testCpuListsAreCountedWithoutMaterializingEveryCpu() : void{
		self::assertSame(7, CpuCapacity::countCpuList('0-3,6,8-9'));
		self::assertNull(CpuCapacity::countCpuList('0-3,2-4'));
		self::assertNull(CpuCapacity::countCpuList('0-999999999999999999999'));
		self::assertNull(CpuCapacity::countCpuList(''));
	}
}
