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

use InvalidArgumentException;

/**
 * Plugin-managed portal links. Register on the server thread, restore from the
 * owning plugin's configuration on enable, and unregister on disable.
 */
final class PortalLinkManager{

	/** @var array<string, PortalLink> */
	private array $links = [];
	/** @var array<string, array<string, PortalLink>> */
	private array $byWorld = [];

	/** Rejects duplicate IDs and overlapping source areas instead of choosing an arbitrary destination. */
	public function register(PortalLink $link) : void{
		$this->validateRegistration($link);
		$this->links[$link->id] = $link;
		$this->byWorld[$link->source->worldName][$link->id] = $link;
	}

	/**
	 * Atomically registers first -> arrivalAtSecond and second -> arrivalAtFirst.
	 * Returns IDs suffixed with :forward and :reverse for later cleanup.
	 *
	 * @return array{PortalLink, PortalLink}
	 */
	public function registerBidirectional(string $id, PortalArea $first, PortalDestination $arrivalAtFirst, PortalArea $second, PortalDestination $arrivalAtSecond) : array{
		if($first->worldName !== $arrivalAtFirst->worldName || $second->worldName !== $arrivalAtSecond->worldName){
			throw new InvalidArgumentException("Each arrival must belong to its corresponding portal's world");
		}
		$forward = new PortalLink($id . ":forward", $first, $arrivalAtSecond);
		$reverse = new PortalLink($id . ":reverse", $second, $arrivalAtFirst);
		$this->validateRegistration($forward);
		$this->validateRegistration($reverse);
		if($first->overlaps($second)){
			throw new InvalidArgumentException("Bidirectional portal areas cannot overlap");
		}
		$this->register($forward);
		$this->register($reverse);
		return [$forward, $reverse];
	}

	public function get(string $id) : ?PortalLink{
		return $this->links[$id] ?? null;
	}

	/** @return array<string, PortalLink> */
	public function getLinks() : array{
		return $this->links;
	}

	/** Removing a link also invalidates its pending trips before they can teleport. */
	public function unregister(string $id) : ?PortalLink{
		$link = $this->links[$id] ?? null;
		if($link !== null){
			unset($this->links[$id], $this->byWorld[$link->source->worldName][$id]);
			if($this->byWorld[$link->source->worldName] === []){
				unset($this->byWorld[$link->source->worldName]);
			}
		}
		return $link;
	}

	/** Resolves an actual touched portal block; an absent link permits normal portal travel. */
	public function find(string $worldName, PortalType $type, int $x, int $y, int $z) : ?PortalLink{
		foreach($this->byWorld[$worldName] ?? [] as $link){
			if($link->source->contains($worldName, $type, $x, $y, $z)){
				return $link;
			}
		}
		return null;
	}

	private function validateRegistration(PortalLink $link) : void{
		if(isset($this->links[$link->id])){
			throw new InvalidArgumentException("Portal link ID already registered: " . $link->id);
		}
		foreach($this->byWorld[$link->source->worldName] ?? [] as $existing){
			if($existing->source->overlaps($link->source)){
				throw new InvalidArgumentException("Portal source area overlaps link " . $existing->id);
			}
		}
	}
}
