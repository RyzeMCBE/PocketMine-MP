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

namespace pocketmine\event\entity;

use pocketmine\entity\Entity;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\utils\Utils;
use pocketmine\world\dimension\portal\PortalType;
use pocketmine\world\Position;
use function is_finite;

/** Fired before a portal teleport. A changed destination is populated and safety-checked again. */
/** @extends EntityEvent<Entity> */
final class EntityPortalTeleportEvent extends EntityEvent implements Cancellable{
	use CancellableTrait;

	public function __construct(Entity $entity, private PortalType $portalType, private Position $to, private ?float $yaw = null, private ?float $pitch = null){
		$this->entity = $entity;
		$this->to = clone $to;
	}

	public function getPortalType() : PortalType{ return $this->portalType; }
	public function getTo() : Position{ return clone $this->to; }
	public function getYaw() : ?float{ return $this->yaw; }
	public function getPitch() : ?float{ return $this->pitch; }
	public function setTo(Position $to, ?float $yaw = null, ?float $pitch = null) : void{
		Utils::checkVector3NotInfOrNaN($to);
		if(($yaw !== null && !is_finite($yaw)) || ($pitch !== null && (!is_finite($pitch) || $pitch < -90 || $pitch > 90))){
			throw new \InvalidArgumentException("Portal rotation must be finite and pitch between -90 and 90");
		}
		$this->to = clone $to;
		$this->yaw = $yaw;
		$this->pitch = $pitch;
	}
}
