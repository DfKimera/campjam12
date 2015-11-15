package {

	import flash.display.MovieClip;

	public class TileGrid extends MovieClip {

		public static const WIDTH:int = 15;
		public static const HEIGHT:int = 8;

		public var tiles:Array = [];

		public function getTileAt(col:int, row:int):Tile {
			return tiles[col][row];
		}

		public function generateFlatGrass():void {

			for(var col:int = 0; col < WIDTH; col++) {

				for(var row:int = 0; row < HEIGHT; row++) {

					var tile:Tile = new Tile(col, row, Tile.TYPE_GRASS, this);

					if(!tiles[col]) {
						tiles[col] = [];
					}

					tiles[col][row] = tile;
					addChild(tile);

				}

			}

		}

		public function importTileList(tileList:Array):void {

			var offset:int = 0;

			for(var row:int = 0; row < HEIGHT; row++) {

				for(var col:int = 0; col< WIDTH; col++) {

					if(!tiles[col]) {
						tiles[col] = [];
					}

					tiles[col][row] = new Tile(col, row, int(tileList[offset]), this);

					addChild(tiles[col][row]);

					offset++;

				}

			}


		}

		public function renderDebugMode():void {

			for(var i:int = 0; i < TileGrid.WIDTH; i++ ) {
				for(var j:int = 0; j < TileGrid.HEIGHT; j++ ) {

					var tile:Tile = tiles[i][j];

					switch(tile.type) {
						case Tile.TYPE_GRASS:
							tile.graphics.beginFill(0x00FF00, 0.5);
							break;
						case Tile.TYPE_DIRT:
							tile.graphics.beginFill(0xFF3399, 0.2);
							break;
						case Tile.TYPE_WATER:
							tile.graphics.beginFill(0x0000FF, 0.5);
							break;
						case Tile.TYPE_TALL_GRASS:
							tile.graphics.beginFill(0x88FF88, 0.3);
							break;
						default:
							tile.graphics.beginFill(0x000000, 0.1);
							break;
					}

					tile.graphics.drawRect(1,1,Tile.SIZE-1, Tile.SIZE-1);
					tile.graphics.endFill();

				}
			}

		}

		public function centralize():void {
			this.x = (Game.stage.stageWidth / 2) - (this.width / 2);
			this.y = (Game.stage.stageHeight/ 2) - (this.height/ 2) + 1;

			Game.unitsContainer.x = this.x;
			Game.unitsContainer.y = this.y;
		}
	}
}
