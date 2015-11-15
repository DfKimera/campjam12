package {

	import org.osflash.signals.Signal;

	import playerio.*;

	public class Networking {

		public var connection:Connection;
		public var client:Client;
		public var multi:Multiplayer;
		public var conn:Connection;

		public var playerUID:String = "Guest";
		public var playerAuthToken:String = "";

		public var isConnected:Boolean = false;
		public var isInRoom:Boolean = false;
		public var isPlaying:Boolean = false;

		public var hasConnected:Signal = new Signal();
		public var hasHostedRoom:Signal = new Signal();
		public var hasJoinedRoom:Signal = new Signal();
		public var hasDisconnected:Signal = new Signal();
		public var hasStartedGame:Signal = new Signal();

		public var onReadyCallback:Function;

		public static const GAME_ID:String = "campjam12-2obvs1g4ceygunjs1k80ww";
		public static const CONN_ID:String = "public";
		public static const DEVELOPER_MODE:Boolean = false;

		public function connectToServer(playerUID:String, playerAuthToken:String):void {

			this.playerUID = playerUID;
			this.playerAuthToken = playerAuthToken;

			PlayerIO.connect(
				Game.stage,
				GAME_ID,
				CONN_ID,
				this.playerUID,
				"",
				"",
				this.onConnectCallback,
				this.onErrorCallback
			);

		}

		private function onConnectCallback(client:Client):void {
			this.client = client;
			this.multi = client.multiplayer;

			if(DEVELOPER_MODE) {
				multi.developmentServer = "127.0.0.1:8184";
			}

			this.isPlaying = true;
			this.hasConnected.dispatch(client);
		}

		private function onErrorCallback(error:PlayerIOError):void {
			Debugger.trace(error);
			UI.error("PlayerIO error: "+error.message);
		}

		public function createRoom(roomID:String, readyCallback:Function):void {
			this.onReadyCallback = readyCallback;
			multi.createJoinRoom(roomID, "Match", true, null, {accessToken: playerAuthToken, playerName: Game.playerName}, onRoomCreated, onErrorCallback);
		}

		private function onRoomCreated(conn:Connection):void {
			this.isInRoom = true;
			this.conn = conn;

			this.hasHostedRoom.dispatch(conn);

			if(this.onReadyCallback is Function) {
				this.onReadyCallback.call(this, conn);
			}
		}

		public function joinRoom(roomID:String, readyCallback:Function):void {
			this.onReadyCallback = readyCallback;
			multi.joinRoom(roomID, {accessToken: playerAuthToken, playerName: Game.playerName}, onRoomJoined, onErrorCallback);

		}

		private function onRoomJoined(conn:Connection):void {
			this.isInRoom = true;
			this.conn = conn;

			this.hasJoinedRoom.dispatch(conn);

			if(this.onReadyCallback is Function) {
				this.onReadyCallback.call(this, conn);
			}
		}

		public function setupListeners(world:WorldController):void {
			conn.addDisconnectHandler(onDisconnect);

			conn.addMessageHandler(MessageType.SV_GAME_PREPARE, world.onGamePrepare);
			conn.addMessageHandler(MessageType.SV_OPPONENT_READY, world.onOpponentReady);
			conn.addMessageHandler(MessageType.SV_YOUR_TURN, world.onYourTurn);
			conn.addMessageHandler(MessageType.SV_OPPONENT_TURN, world.onOpponentTurn);
			conn.addMessageHandler(MessageType.SV_UNIT_MOVED, world.onUnitMoved);
			conn.addMessageHandler(MessageType.SV_UNIT_PLACED, world.onUnitPlaced);
			conn.addMessageHandler(MessageType.SV_UNIT_REVEALED,  world.onUnitReveal);
			conn.addMessageHandler(MessageType.SV_COMBAT_SCREEN, world.onBattleScreen);
			conn.addMessageHandler(MessageType.SV_TURN_TIMEOUT, world.onTurnTimeout);
			conn.addMessageHandler(MessageType.SV_GAME_OVER, world.onGameOver);
			conn.addMessageHandler(MessageType.SV_CHAT_MESSAGE, world.onChatMessage);

		}

		public function sendUnitPlacement(unit:Unit, targetCol:int, targetRow:int):void {
			conn.send(MessageType.CL_PLACE_UNIT, unit.id, targetCol, targetRow);
		}

		public function sendPlayerReady():void {
			conn.send(MessageType.CL_PLAYER_READY);
		}

		public function sendUnitMovement(unit:Unit, targetCol:int, targetRow:int):void {
			conn.send(MessageType.CL_MOVE_UNIT, unit.id, targetCol, targetRow);
		}

		public function sendChatMessage(msg:String):void {
			// unused
		}

		private function onDisconnect():void {
			this.hasDisconnected.dispatch();
		}

	}
}
