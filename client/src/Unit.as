package {
	import flash.display.MovieClip;
	import flash.events.Event;
	import flash.events.MouseEvent;

	import units.FlagType;
	import units.PupaType;
	import units.UnitType1;
	
	import com.greensock.TweenLite;

	public class Unit extends MovieClip {

		public var id:int = 0;

		public var isOwnedByHost:Boolean = false;
		public var isMine:Boolean = false;
		public var isAlive:Boolean = true;

		public var canMove:Boolean = false;
		public var isSelected:Boolean = false;

		public var type:int = 0;

		public var column:int = 0;
		public var row:int = 0;
		public var currentTile:Tile = null;

		public var portrait:UnitPortrait = null;

		public var direction:int = 1; // 1 = ->, -1 = <-
		public var range:int = 1;
		public var canWalkOnWater:Boolean = false;
		public var canSwapWithUnits:Boolean = false;
		public var canHideOnTallGrass:Boolean = false;
		public var canPlaceWeb:Boolean = false;
		public var hasFreeMovement:Boolean = false;

		public var isFlag:Boolean = false;

		public var skin:MovieClip = null;
		public var selectionAura:SelectionAura = new SelectionAura();

		public function Unit(unitID:int, isHost:Boolean, unitType:int) {

			this.id = unitID;
			this.isOwnedByHost = isHost;
			this.isMine = (Game.amIHosting) ? this.isOwnedByHost : !this.isOwnedByHost;
			this.type = unitType;

			selectionAura.visible = false;
			addChild(selectionAura);

			this.reloadSkin();
			this.reloadSkills();

			this.addEventListener("click", onClick);
			this.addEventListener(MouseEvent.MOUSE_OVER, onMouseOver);

		}

		public function reloadSkills():void {

			canHideOnTallGrass = (type == 1);
			range = (type == 2) ? 2 : 1;
			hasFreeMovement = (type == 3);
			canPlaceWeb = (type == 4);
			canSwapWithUnits = (type == 5);
			canWalkOnWater = (type == 6);

			isFlag = (type == 7);

		}

		public function reloadSkin():void {
			if(skin) {
				this.removeChild(skin);
			}

			switch(type) {

				case 1: skin = new units.UnitType1(); break;
				case 2: skin = new units.UnitType2(); break;
				case 3: skin = new units.UnitType3(); break;
				case 4: skin = new units.UnitType4(); break;
				case 5: skin = new units.UnitType5(); break;
				case 6: skin = new units.UnitType6(); break;
				case 7: skin = new units.FlagType(); break; // flag
				case 0: default: skin = new units.PupaType(); break; // pupa

			}

			if(this.isOwnedByHost) {
				this.scaleX = -1;
				this.direction = 1;
			} else {
				this.direction = -1;
			}

			this.addChild(skin);
		}

		public function move(col:int, row:int):void {
			this.row = row;
			this.column = col;
			this.x = (this.column * Tile.SIZE) + (Tile.SIZE / 2);
			this.y = (this.row * Tile.SIZE)  + (Tile.SIZE / 2) - 8;
		}

		public function getName():String {

			switch(type) {
				case 1: return "Termite"; break;
				case 2: return "Drozo"; break;
				case 3: return "Lumewasp"; break;
				case 4: return "Spibster"; break;
				case 5: return "Buttermoth"; break;
				case 6: return "Warbeetle"; break;
				case 7: return "Flag"; break;
				case 0: default: return "???"; break;
			}

		}

		public function getProfileFrame(victory:Boolean):int {
			switch(type) {
				case 1: default: return ((victory) ? 1 : 2); break;
				case 2: return ((victory) ? 3 : 4); break;
				case 3: return ((victory) ? 5 : 6); break;
				case 4: return ((victory) ? 7 : 8); break;
				case 5: return ((victory) ? 9 : 10); break;
				case 6: return ((victory) ? 11 : 12); break;
			}
		}

		public function getInfo():String {

			switch(type) {
				case 1: return "Can hide and become invisible in bushes"; break;
				case 2: return "Moves twice the speed of other units"; break;
				case 3: return "Can move backwards"; break;
				case 4: return "Unit has no special moves"; break;
				//case 4: return "Casts a web in a tile, slowing other units"; break;
				case 5: return "Unit has no special moves"; break;
				//case 5: return "Can swap among other units"; break;
				case 6: return "Can walk on water tiles"; break;
				case 7: return "Capture the flag to win the game!"; break;
				case 0: default: return "You haven't revealed this unit yet"; break;
			}

		}

		public function placeOnTile(tile:Tile):void {
			if(!tile) {
				Debugger.trace("Cannot place unit: invalid tile!");
				return;
			}

			if(this.currentTile) {
				this.removeFromTile();
			}

			tile.placeUnit(this);
			this.move(tile.column, tile.row);
		}

		public function removeFromTile():void {
			if(this.currentTile) {
				this.currentTile.removeUnit();
			}
		}

		public function allowMovement():void {
			if(isFlag) {
				restrictMovement();
			}

			canMove = true;
			buttonMode = true;
		}

		public function restrictMovement():void {
			canMove = false;
			buttonMode = false;
		}

		private function onClick(ev:Event):void {
			if(!canMove) {
				return;
			}

			UI.gameScreen.selectUnit(this);
		}

		public function walkTo(targetCol:int, targetRow:int) {

			column = targetCol;
			row = targetRow;
			
			var targetX:Number = (this.column * Tile.SIZE) + (Tile.SIZE / 2);
			var targetY:Number = (this.row * Tile.SIZE) + (Tile.SIZE / 2) - 8;

			this.skin.gotoAndPlay("move");

			var self:Unit = this;

			TweenLite.to(this, 1.5, {x: targetX, y: targetY, onComplete: function ():void {
				self.skin.gotoAndPlay("idle");
			}});
			
		}

		public function isInRangeOf(tile:Tile):Boolean {

			var dist:Number = Math.abs(tile.column - this.column) + Math.abs(tile.row - this.row);

			if(dist == 0) {
				return false;
			}

			if(dist > range) {
				return false;
			}

			if(tile.type == Tile.TYPE_WATER && !canWalkOnWater) {
				return false;
			}

			if(tile.hasUnit()) {
				if(!tile.currentUnit.isMine) {
					return false;
				}

				if(!canSwapWithUnits) {
					return false;
				}
			}

			var deltaX:int = tile.column - this.column;

			if(deltaX > 0 && direction < 0) {
				return false;
			} else if(deltaX < 0 && direction > 0) {
				return false;
			}

			return true;

		}

		public function kill():void {

			this.isAlive = false;
			if(this.portrait) {
				this.portrait.onUnitKilled();
			}

			if(isSelected) {
				UI.gameScreen.deselectUnit();
			}

			this.visible = false;
			this.removeFromTile();

			this.removeEventListener("click", onClick);
		}

		private function onMouseOver(ev:MouseEvent):void {

			if(!isAlive) {
				return;
			}

			if(Game.amIHosting) {
				if(isMine) {
					UI.gameScreen.showHostUnitAvatar(this);
				} else {
					UI.gameScreen.showGuestUnitAvatar(this);
				}
			} else {
				if(isMine) {
					UI.gameScreen.showGuestUnitAvatar(this);
				} else {
					UI.gameScreen.showHostUnitAvatar(this);
				}
			}

		}

	}
}
