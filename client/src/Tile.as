package {
	
	import flash.display.MovieClip;
	import flash.events.MouseEvent;

	public class Tile extends MovieClip {

		public static const TYPE_GRASS:int = 1;
		public static const GRASS_SKINS:Array = [1,2,3];

		public static const TYPE_TALL_GRASS:int = 2;
		public static const TALL_GRASS_SKINS:Array = [8];

		public static const TYPE_DIRT:int = 3;
		public static const DIRT_SKINS:Array = [4,5,6];

		public static const TYPE_WATER:int = 4;
		public static const WATER_SKINS:Array = [7];

		public static const SIZE:int = 64;

		public var column:int = 0;
		public var row:int = 0;
		public var type:int = Tile.TYPE_GRASS;
		public var skin:int = 0;

		public var skinContainer:TileSkin = new TileSkin();

		public var currentUnit:Unit = null;
		public var grid:TileGrid = null;

		public function Tile(column:int, row:int, type:int,  grid:TileGrid) {
			this.column = column;
			this.row = row;

			this.x = this.column * Tile.SIZE;
			this.y = this.row * Tile.SIZE;

			this.type = type;
			this.grid = grid;

			this.addEventListener(MouseEvent.CLICK, onClick);
			this.addEventListener(MouseEvent.MOUSE_OVER, onMouseOver);
			this.addEventListener(MouseEvent.MOUSE_OUT,  onMouseOut);

			skinContainer.placementOverlay.visible = false;
			skinContainer.movementOverlay.visible = false;

			addChild(skinContainer);
			this.resetSkin();

		}

		public function resetSkin():void {

			var skinList:Array = GRASS_SKINS;

			switch(type) {

				case TYPE_TALL_GRASS: skinList = TALL_GRASS_SKINS; break;
				case TYPE_DIRT: skinList = DIRT_SKINS; break;
				case TYPE_WATER: skinList = WATER_SKINS; break;
				case TYPE_GRASS: default: skinList = GRASS_SKINS; break;

			}

			var randomSkin:int = Math.floor(Math.random() * skinList.length);
			skinContainer.gotoAndStop(skinList[randomSkin]);

		}

		public function hasUnit():Boolean {
			return (this.currentUnit != null);
		}

		public function placeUnit(unit:Unit):void {
			if(this.currentUnit) {
				this.removeUnit();
			}

			this.currentUnit = unit;
			unit.currentTile = this;

		}

		public function removeUnit():void {
			if(this.currentUnit) {
				this.currentUnit.currentTile = null;
				this.currentUnit = null;
			}
		}

		private function onClick(ev:MouseEvent):void {
			if(UI.gameScreen.selectedPortrait) {

				if(!this.isPlayerTerritory()) {
					return;
				}

				UI.gameScreen.dropPortrait(this);
			} else if(UI.gameScreen.selectedUnit && UI.gameScreen.canMoveUnits && UI.gameScreen.selectedUnit.isInRangeOf(this)) {
				UI.gameScreen.moveSelectedToTile(this);
			}
		}

		private function onMouseOver(ev:MouseEvent):void {
			if(UI.gameScreen.selectedPortrait && this.isPlayerTerritory()) {
				skinContainer.placementOverlay.visible = true;
			} else if(UI.gameScreen.selectedUnit && UI.gameScreen.canMoveUnits && UI.gameScreen.selectedUnit.isInRangeOf(this)) {
				var unit:Unit = UI.gameScreen.selectedUnit;
				var dx = this.column - unit.column;
				var dy = this.row - unit.row;

				if(dx == 1 && dy == 1) {
					skinContainer.movementOverlay.rotation = 45;
				} else if(dx == 1 && dy == -1) {
					skinContainer.movementOverlay.rotation = -45;
				} else if(dx == -1 && dy == 1) {
					skinContainer.movementOverlay.rotation = 125;
				} else if(dx == -1 && dy == -1) {
					skinContainer.movementOverlay.rotation = -125;
				} else if(dx > 0) {
					skinContainer.movementOverlay.rotation = 0;
				} else if(dx < 0) {
					skinContainer.movementOverlay.rotation = 180;
				} else if(dy > 0) {
					skinContainer.movementOverlay.rotation = 90;
				} else if(dy < 0) {
					skinContainer.movementOverlay.rotation = -90;
				}

				skinContainer.movementOverlay.visible = true;
			}
		}

		public function isPlayerTerritory():Boolean {
			if(Game.amIHosting) {
				return this.isHostPlayerTerritory();
			} else {
				return this.isGuestPlayerTerritory();
			}
		}

		public function isHostPlayerTerritory():Boolean {
			return (column <= 2);
		}

		public function isGuestPlayerTerritory():Boolean {
			return (column >= 12);
		}

		private function onMouseOut(ev:MouseEvent):void {
			skinContainer.placementOverlay.visible = false;
			skinContainer.movementOverlay.visible = false;
		}
	}
}
