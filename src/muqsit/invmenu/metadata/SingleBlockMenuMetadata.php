<?php

/*
 *  ___            __  __
 * |_ _|_ ____   _|  \/  | ___ _ __  _   _
 *  | || '_ \ \ / / |\/| |/ _ \ '_ \| | | |
 *  | || | | \ V /| |  | |  __/ | | | |_| |
 * |___|_| |_|\_/ |_|  |_|\___|_| |_|\__,_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Muqsit
 * @link http://github.com/Muqsit
 *
*/

declare(strict_types=1);

namespace muqsit\invmenu\metadata;

use muqsit\invmenu\session\MenuExtradata;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\tile\Nameable;
use pocketmine\block\tile\Tile;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\BlockEntityDataPacket;
use pocketmine\network\mcpe\serializer\NetworkNbtSerializer;
use pocketmine\player\Player;
use pocketmine\world\Position;

class SingleBlockMenuMetadata extends MenuMetadata{

	/** @var NetworkNbtSerializer */
	protected static $serializer;

	/** @var Block */
	protected $block;

	/** @var string */
	protected $identifier;

	/** @var int */
	protected $size;

	/** @var string */
	protected $tile_id;

	public function __construct(string $identifier, int $size, Block $block, string $tile_id){
		parent::__construct($identifier, $size);

		if(self::$serializer === null){
			self::$serializer = new NetworkNbtSerializer();
		}

		$this->block = $block;
		$this->tile_id = $tile_id;
	}

	public function sendGraphic(Player $player, MenuExtradata $metadata) : void{
		$positions = $this->getBlockPositions($metadata);

		$blocks = [];
		foreach($positions as $pos){
			$block = BlockFactory::get($this->block->getId(), $this->block->getMeta());
			$block->x = $pos->x;
			$block->y = $pos->y;
			$block->z = $pos->z;
			$blocks[] = $block;
		}
		$player->getWorld()->sendBlocks([$player], $blocks);

		$name = $metadata->getName();
		foreach($positions as $pos){
			$this->sendBlockEntityGraphicAt($player, $pos, $name);
		}
	}

	protected function sendBlockEntityGraphicAt(Player $player, Vector3 $pos, ?string $name) : void{
		$player->sendDataPacket(BlockEntityDataPacket::create(
			$pos->x,
			$pos->y,
			$pos->z,
			self::$serializer->write(
				new TreeRoot($this->getBlockEntityDataAt($pos, $name))
			)
		));
	}

	protected function getBlockEntityDataAt(Vector3 $pos, ?string $name) : CompoundTag{
		$tag = CompoundTag::create()->setString(Tile::TAG_ID, $this->tile_id);
		if($name !== null) {
			$tag->setString(Nameable::TAG_CUSTOM_NAME, $name);
		}
		return $tag;
	}

	public function removeGraphic(Player $player, MenuExtradata $extradata) : void{
		$player->getWorld()->sendBlocks([$player], $this->getBlockPositions($extradata));
	}

	protected function getBlockPositions(MenuExtradata $metadata) : array{
		return [$metadata->getPosition()];
	}
}