package {

	import playerio.Message;

	import ui.GameScreen;

	public class WorldController {

		public var gui:GameScreen;
		public var net:Networking;
		public var grid:TileGrid;

		public var units:Array = [];

		public var unitsPlacedByPlayer:int = 0;

		public var hostPlayer:Player;
		public var guestPlayer:Player;

		public function WorldController() {

			this.net = Game.net;
			this.grid = Game.grid;

			net.setupListeners(this);

		}

		public function onGamePrepare(m:Message):void {
			UI.open(UI.gameScreen);

			gui = UI.gameScreen;
			gui.activate(this);

			var unitList:Array = m.getString(0).split(",");
			var tileList:Array = m.getString(1).split(",");

			grid.importTileList(tileList);
			//grid.renderDebugMode();
			grid.centralize();


			for(var i in unitList) {

				if(unitList[i].length <= 0) {
					continue;
				}

				var unitInfo:Array = unitList[i].split(":");

				var unitID:int = int(unitInfo[0]);
				var isHost:Boolean = Boolean(int(unitInfo[1]));
				var unitType:int = int(unitInfo[2]);

				var unit:Unit = new Unit(unitID, isHost, unitType);
				unit.visible = false;

				Game.unitsContainer.addChild(unit);

				units[unitID] = unit;

				gui.addUnitToPortraits(unit);
			}

			hostPlayer = new Player(m.getString(2), m.getString(3), true);
			guestPlayer = new Player(m.getString(4), m.getString(5), false);

			gui.reloadPlayers();
			gui.allowTilePlacement();
			gui.canPlaceUnits = true;

		}

		public function placeUnitOnTile(unit:Unit, tile:Tile):void {
			net.sendUnitPlacement(unit, tile.column, tile.row);
		}

		public function onUnitPlaced(m:Message):void {
			var unitID:int = m.getInt(0);
			var apparentType:int = m.getInt(1);
			var targetCol:int = m.getInt(2);
			var targetRow:int = m.getInt(3);

			var tile:Tile = grid.getTileAt(targetCol, targetRow);
			var unit:Unit = units[unitID];
			unit.type = apparentType;

			if(unit.isMine && unit.currentTile == null && !tile.hasUnit()) {
				unitsPlacedByPlayer++;
			}

			unit.placeOnTile(tile);
			unit.reloadSkin();
			unit.visible = true;

			if(gui.canPlaceUnits && unitsPlacedByPlayer == 3) {
				gui.showReadyButton();
			}

		}

		public function onOpponentReady(m:Message):void {
			if(Game.amIHosting) {
				gui.guestReadyDisplay.visible = true;
			} else {
				gui.hostReadyDisplay.visible = true;
			}
		}

		public function playerIsReady():void {
			net.sendPlayerReady();
		}

		public function onYourTurn(m:Message):void {
			gui.resetUnitPortraits();
			gui.deselectUnit();
			gui.startTimer(30);
			gui.allowUnitMovement();
			gui.waitForOpponentDisplay.visible = false;
			gui.yourTurnDisplay.visible = true;
			gui.opponentTurnDisplay.visible = false;
		}

		public function onOpponentTurn(m:Message):void {
			gui.resetUnitPortraits();
			gui.startTimer(30);
			gui.restrictUnitMovement();
			gui.waitForOpponentDisplay.visible = false;
			gui.yourTurnDisplay.visible = false;
			gui.opponentTurnDisplay.visible = true;
		}

		public function moveUnitToTile(unit:Unit, tile:Tile):void {
			gui.restrictUnitMovement(); // prevent moving before we get a response from the server
			net.sendUnitMovement(unit, tile.column, tile.row);
		}

		public function onUnitMoved(m:Message):void {
			var unitID:int = m.getInt(0);
			var targetCol:int = m.getInt(1);
			var targetRow:int = m.getInt(2);

			var unit:Unit = Unit(units[unitID]);
			unit.walkTo(targetCol, targetRow);
		}

		public function onUnitReveal(m:Message):void {
			var unitID:int = m.getInt(0);
			var unitType:int = m.getInt(1);

			var unit:Unit = Unit(units[unitID]);
			unit.type = unitType;
			unit.reloadSkin();
		}

		public function onBattleScreen(m:Message):void {

			var attackerUnit:Unit = units[m.getInt(0)];
			var defenderUnit:Unit = units[m.getInt(1)];

			var attackerVictory:Boolean = m.getBoolean(2);
			var isTie:Boolean = m.getBoolean(3);

			gui.showBattleScreen(attackerUnit, attackerUnit.currentTile, attackerVictory, defenderUnit, defenderUnit.currentTile, !attackerVictory);

			// CHUUUUPA!

			if(attackerVictory) {
				defenderUnit.kill();
			} else {
				attackerUnit.kill();
			}

		}

		public function onTurnTimeout(m:Message):void {
			gui.stopTimer();
			gui.restrictUnitMovement();
			// @TODO: show a blinking notice warning of the timeout
		}

		public function onGameOver(m:Message):void {
			var isWinner:Boolean = m.getBoolean(0);

			gui.stopTimer();
			gui.restrictUnitMovement();

			gui.gameOverScreen.gotoAndStop( (isWinner) ? 1 : 2 );
			gui.gameOverScreen.visible = true;

		}

		public function onChatMessage(m:Message):void {
			// unused
		}

	}

}
