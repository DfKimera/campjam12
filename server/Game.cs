using System;
using System.Collections.Generic;
using System.Text;
using System.Collections;
using PlayerIO.GameLibrary;
using System.Drawing;

namespace Campjam12 {

	[RoomType("Match")]
	public class GameCode : Game<Player> {

		public int[] gameMap = { 3, 3, 3, 1, 1, 1, 1, 1, 4, 2, 1, 1, 3, 3, 3, 3, 3, 3, 2, 1, 4, 1, 1, 4, 1, 1, 1, 3, 3, 3, 3, 3, 3, 1, 4, 4, 2, 1, 4, 1, 1, 1, 3, 3, 3, 3, 3, 3, 1, 4, 4, 1, 1, 1, 1, 2, 1, 3, 3, 3, 3, 3, 3, 1, 2, 1, 1, 1, 1, 4, 4, 1, 3, 3, 3, 3, 3, 3, 1, 1, 1, 4, 1, 1, 4, 4, 1, 3, 3, 3, 3, 3, 3, 1, 1, 1, 4, 1, 2, 4, 1, 2, 3, 3, 3, 3, 3, 3, 1, 1, 2, 4, 1, 1, 1, 1, 1, 3, 3, 3 };

		public const int MAP_WIDTH = 15;
		public const int MAP_HEIGHT = 8;

		public bool hasHostJoined = false;
        public bool hasGuestJoined = false;

		public Player hostPlayer;
		public Player guestPlayer;

		public Player currentTurnPlayer = null;
		public Player idlePlayer = null;

		public int turnNumber = 0;

        public bool hasHostPlacedUnits = false;
        public bool hasGuestPlacedUnits = false;

		public Dictionary<string, Player> players = new Dictionary<string, Player>();
		public Dictionary<int, Unit> units = new Dictionary<int, Unit>();
		public TileGrid tileGrid = new TileGrid();
		public int unitIndex = 0;

		// -------------------------------------------
		// Server Events
		// -------------------------------------------

		public override void GameStarted() {
			Console.WriteLine("Game is started: " + RoomId);			
		}

		public override void GameClosed() {
			Console.WriteLine("RoomId: " + RoomId);
		}

		public override void UserJoined(Player player) {

			player.uid = player.ConnectUserId;
			player.accessToken = player.JoinData["accessToken"];
			player.name = player.JoinData["playerName"];

			if(!hasHostJoined) {
				this.onHostJoin(player);
			} else {
				this.onGuestJoin(player);
			}
		}

		public override bool AllowUserJoin(Player player) {
			if(hasHostJoined && hasGuestJoined) {
				return false;
			} else {
				return true;
			}
		}

		public override void UserLeft(Player player) {
			Broadcast("UserLeft", player.Id);
		}

		public override void GotMessage(Player player, Message message) {
			//Console.WriteLine("Received message T=" + message.Type + " from player " + player.Id);

			switch(message.Type) {
				
				case MessageType.CL_PLACE_UNIT:
					onUnitPlace(player, units[message.GetInt(0)], message.GetInt(1), message.GetInt(2));
					break;

				case MessageType.CL_MOVE_UNIT:
					onUnitMove(player, units[message.GetInt(0)], message.GetInt(1), message.GetInt(2));
					break;

				case MessageType.CL_PLAYER_READY:
					onPlayerPlacementReady(player);
					break;

				default:
					Console.WriteLine("Unknown message type: " + message.Type);
					break;


			}
		}

		// -------------------------------------------
		// Game Events & Stages
		// -------------------------------------------

		public void onHostJoin(Player player) {

			hostPlayer = player;
			player.isHost = true;
			this.giveUnitsToPlayer(player);
			hasHostJoined = true;
			
		}

		public void onGuestJoin(Player player) {

			guestPlayer = player;
			this.giveUnitsToPlayer(player);
			hasGuestJoined = true;

			guestPlayer.opponent = hostPlayer;
			hostPlayer.opponent = guestPlayer;

			this.startUnitPlacement();

		}

		public void startUnitPlacement() {

			Console.WriteLine("Preparing game... players shall now place their units");

			this.generateGridFromMap(gameMap);

			hostPlayer.sendMatchInfo(units, gameMap, hostPlayer, guestPlayer);
			guestPlayer.sendMatchInfo(units, gameMap, hostPlayer, guestPlayer);

			RefreshDebugView();

		}

		public void onUnitPlace(Player player, Unit unit, int targetCol, int targetRow) {

			Console.WriteLine("Player " + player.Id + " placed unit " + unit.id + " @ tile " + targetCol + "," + targetRow);
			
			if(player.Id != unit.owner.Id) {
				Console.WriteLine("ERROR! Player attempted to place a unit that is not his!");
				return;
			}

			Tile tile = tileGrid[targetCol][targetRow];

			unit.placeOnTile(tile);

			int apparentType = 0;
			if(unit.type == 7) { // flag
				apparentType = 7;
			}

			player.notifyUnitPlacement(unit, unit.type, targetCol, targetRow);
			player.opponent.notifyUnitPlacement(unit, apparentType, targetCol, targetRow);

			RefreshDebugView();
		}

		public void onPlayerPlacementReady(Player player) {
			
			if(player.isHost) {
				hasHostPlacedUnits = true;
			} else {
				hasGuestPlacedUnits = true;
			}

			player.opponent.notifyOpponentReady();

			if(hasHostPlacedUnits && hasGuestPlacedUnits) {
				Console.WriteLine("All players placed units, starting first turn");
				this.startNewTurn();
			}

			RefreshDebugView();

		}

		public void startNewTurn() {

			RefreshDebugView();

			turnNumber++;
			Console.WriteLine("NEW TURN: #" + turnNumber);

			if(currentTurnPlayer == null || currentTurnPlayer == guestPlayer) {
				
				currentTurnPlayer = hostPlayer;
				idlePlayer = guestPlayer;
				

			} else if(currentTurnPlayer == hostPlayer) {
				
				currentTurnPlayer = guestPlayer;
				idlePlayer = hostPlayer;

			}

			currentTurnPlayer.startTurn(turnNumber);
			idlePlayer.startOpponentTurn(turnNumber);

			this.startTurnTimer(turnNumber);

		}

		public void startTurnTimer(int turnNumber) {

			ScheduleCallback(delegate() {
				this.onTurnTimeout(turnNumber);
			}, 31500); // 1.5sec tolerance for latency/desync

		}

		public void onTurnTimeout(int turnNumber) {
			if(this.turnNumber != turnNumber) {
				//Console.WriteLine("Turn #"+turnNumber+" timed out, but turn had already finished");
				return;
			} else {
				Console.WriteLine("TURN #" + turnNumber + " TIME OUT!");

				currentTurnPlayer.notifyTurnTimeout(turnNumber, true);
				idlePlayer.notifyTurnTimeout(turnNumber, false);

				ScheduleCallback(delegate() {
					this.startNewTurn();
				}, 3000);
			}
		}

		public void onUnitMove(Player player, Unit unit, int targetCol, int targetRow) {

			if(player.Id != currentTurnPlayer.Id) {
				Console.WriteLine("ERROR! Player tried to move not-owned unit");
				this.startNewTurn();
				return;
			}

			Console.WriteLine("Played is moving unit "+unit.id+" to "+targetCol+","+targetRow);

			Tile tile = tileGrid[targetCol][targetRow];

			int dx = Math.Abs(unit.column - targetCol);
			int dy = Math.Abs(unit.row - targetRow);

			if(dx + dy > unit.range) {
				Console.WriteLine("CHEATING! Unit " + unit.id + " from player " + player + " attempted to move more then it's range!");
				this.startNewTurn();
				return;
			}

			if(tile.type == Tile.TYPE_WATER && !unit.canWalkOnWater) {
				Console.WriteLine("CHEATING! Earth-bound unit " + unit.id + " from player " + player + " is trying to move into water tile!");
				this.startNewTurn();
				return;
			}

			if(tile.hasUnit()) {
				if(tile.currentUnit.isFlag) {
					triggerVictory(player);
				} else {
					//Console.WriteLine("CHEATING! Unit " + unit.id + " from player " + player + " is trying to walk onto another unit!");
					//this.startNewTurn();
					//return;
				}
			}

			unit.placeOnTile(tile);

			currentTurnPlayer.notifyUnitMovement(unit, unit.column, unit.row);
			idlePlayer.notifyUnitMovement(unit, unit.column, unit.row);

			List<Tile> neighbors = tile.getAttackingNeighbors();

			foreach(Tile neighbor in neighbors) {
				if(neighbor.hasUnit() && neighbor.currentUnit.isAlive && neighbor.currentUnit.owner.Id != unit.owner.Id) {
					Console.WriteLine("Battle! Found enemy unit in adjascent tile @" + tile.column + "," + tile.row + ", triggering battle in 3sec");

					if(neighbor.currentUnit.isFlag) {
						Console.WriteLine("Unit is the flag! Victory!");
						triggerVictory(player);
						return;
					}

					ScheduleCallback(delegate() {
						triggerBattle(unit, neighbor.currentUnit);
					}, 2000);

					return;
				}
			}

			Console.WriteLine("Turn completed, starting new turn in 3 seconds...");
			ScheduleCallback(delegate() {
				this.startNewTurn();
			}, 2000);

			RefreshDebugView();

		}

		public void triggerBattle(Unit attacker, Unit defender) {
			attacker.owner.revealUnit(defender);
			defender.owner.revealUnit(attacker);

			bool victory = attacker.attack(defender);

			attacker.owner.showCombatScreen(attacker, defender, victory, false);
			defender.owner.showCombatScreen(attacker, defender, victory, false);

			if(victory) {
				defender.isAlive = false;
				defender.currentTile = null;
				defender.kill();
			} else {
				attacker.isAlive = false;
				attacker.currentTile = null;
				attacker.kill();
			}

			ScheduleCallback(delegate() {
				this.startNewTurn();
			}, 5000);

			RefreshDebugView();
		}

		public void triggerVictory(Player winner) {
			Console.WriteLine("GAME OVER! Winner: " + winner);

			winner.notifyGameOver(true);
			winner.opponent.notifyGameOver(false);
		}

		// -------------------------------------------
		// Common tasks
		// -------------------------------------------

		public void generateGridFromMap(int[] map) {

			Console.WriteLine("Generating grid from linear array map. Length = " + map.Length);

			int offset = 0;

			for(int row = 0; row < MAP_HEIGHT; row++) {

				for(int col = 0; col < MAP_WIDTH; col++) {

					if(!tileGrid.ContainsKey(col)) {
						tileGrid[col] = new Dictionary<int, Tile>();
					}

					tileGrid[col][row] = new Tile(map[offset], col, row, tileGrid);
					offset++;
				}

			}

			Console.WriteLine("Map generated!");

		}

		public void giveUnitsToPlayer(Player player) {
			this.giveUnitToPlayer(1, player);
			this.giveUnitToPlayer(2, player);
			this.giveUnitToPlayer(2, player);
			this.giveUnitToPlayer(2, player);
			this.giveUnitToPlayer(3, player);
			this.giveUnitToPlayer(3, player);
			this.giveUnitToPlayer(3, player);
			this.giveUnitToPlayer(4, player);
			this.giveUnitToPlayer(4, player);
			this.giveUnitToPlayer(5, player);
			this.giveUnitToPlayer(5, player);
			this.giveUnitToPlayer(6, player);
			this.giveUnitToPlayer(7, player); // flag
		}

		public void giveUnitToPlayer(int type, Player player) {

			int unitID = ++unitIndex;

			Unit unit = new Unit();
			unit.id = unitID;
			unit.type = type;
			unit.owner = player;
			unit.reloadSkills();

			player.units[unitID] = unit;
			units[unitID] = unit;

		}


		public override System.Drawing.Image GenerateDebugImage() {

			var image = new Bitmap(960, 512);
			using(var g = Graphics.FromImage(image)) {

				foreach(KeyValuePair<int,Dictionary<int,Tile>> kv in tileGrid) {
					foreach(KeyValuePair<int, Tile> kv2 in kv.Value) {
						Tile tile = kv2.Value;

						int x = tile.column * 64;
						int y = tile.row * 64;
						Brush color;
						switch(tile.type) {
							case Tile.TYPE_GRASS:
							default: color = Brushes.LawnGreen; break;
							case Tile.TYPE_TALL_GRASS: color = Brushes.ForestGreen; break;
							case Tile.TYPE_DIRT: color = Brushes.SandyBrown; break;
							case Tile.TYPE_WATER: color = Brushes.LightBlue; break;
						}


						g.FillRectangle(color, x, y, 64, 64);
					}

				}

				foreach(KeyValuePair<int, Unit> kv in units) {

					Unit unit = kv.Value;

					if(!unit.isAlive) {
						continue;
					}

					Brush color = Brushes.Blue;
					if(unit.owner.isHost) {
						color = Brushes.Red;
					}

					int x = unit.column * 64;
					int y = unit.row * 64;
					g.FillEllipse(color, x, y, 64, 64);

				}
			}
			return image;
		}
		

	}
}
