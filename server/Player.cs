using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

using PlayerIO.GameLibrary;

namespace Campjam12 {
	
	public class Player : BasePlayer {

		public string uid;
		public string accessToken;
		public string name;

		public bool isHost = false;

		public Player opponent = null;

		public Dictionary<int, Unit> units = new Dictionary<int,Unit>();

		public void send(Message m) {
			this.Connection.Send(m);
		}

		public void sendMatchInfo(Dictionary<int, Unit> gameUnits, int[] gameMap, Player hostPlayer, Player guestPlayer) {

			string unitList = "";
			foreach(KeyValuePair<int, Unit> kv in gameUnits) {
				unitList += kv.Value.id + ":" + Convert.ToInt32(kv.Value.owner.isHost) + ":" + kv.Value.type + ","; // unitID:isHost:type
			}

			string tileList = String.Join(",", gameMap);

			send(Message.Create(MessageType.SV_GAME_PREPARE, unitList, tileList, hostPlayer.uid, hostPlayer.name, guestPlayer.uid, guestPlayer.name));
		}

		public void notifyOpponentReady() {
			send(Message.Create(MessageType.SV_OPPONENT_READY));
		}

		public void startTurn(int turnNumber) {
			send(Message.Create(MessageType.SV_YOUR_TURN, turnNumber));
		}

		public void startOpponentTurn(int turnNumber) {
			send(Message.Create(MessageType.SV_OPPONENT_TURN, turnNumber));
		}

		public void notifyUnitMovement(Unit unit, int newCol, int newRow) {
			send(Message.Create(MessageType.SV_UNIT_MOVED, unit.id, newCol, newRow));
		}

		public void notifyUnitPlacement(Unit unit, int apparentType, int col, int row) {
			send(Message.Create(MessageType.SV_UNIT_PLACED, unit.id, apparentType, col, row));
		}

		public void showCombatScreen(Unit playerUnit, Unit opponentUnit, bool isWinner, bool isTie) {
			send(Message.Create(MessageType.SV_COMBAT_SCREEN, playerUnit.id, opponentUnit.id, opponentUnit.type, isWinner, isTie));
		}

		public void revealUnit(Unit unit) {
			send(Message.Create(MessageType.SV_UNIT_REVEALED, unit.id, unit.type));
		}

		public void notifyTurnTimeout(int turnNumber, bool isMyTurn) {
			send(Message.Create(MessageType.SV_TURN_TIMEOUT, turnNumber, isMyTurn));
		}

		public void notifyGameOver(bool isWinner) {
			send(Message.Create(MessageType.SV_GAME_OVER, isWinner));
		}

		public void chatMessage(Player sender, String message) {
			send(Message.Create(MessageType.SV_CHAT_MESSAGE, sender.uid, sender.name, message));
		}

	}

}
