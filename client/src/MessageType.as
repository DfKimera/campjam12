package {
	public class MessageType {

		public static const SV_GAME_PREPARE:String = "sgp"; // received when it's time to place units
		public static const SV_OPPONENT_READY:String = "sor"; // received when the opponent has already finished placing units
		public static const SV_YOUR_TURN:String = "syt"; // received when it's your turn to play
		public static const SV_OPPONENT_TURN:String = "sot"; // received when it's your opponent's turn to play
		public static const SV_UNIT_MOVED:String = "sum"; // received when a unit is moved on the grid
		public static const SV_UNIT_PLACED:String = "sup"; // received when a unit is placed on the grid
		public static const SV_UNIT_REVEALED:String = "sru"; // received when a unit should have it's real type revealed
		public static const SV_COMBAT_SCREEN:String = "scs"; // received when a combat happens
		public static const SV_TURN_TIMEOUT:String = "stt"; // received when you or your opponent's turn times out
		public static const SV_GAME_OVER:String = "sgo"; // received when the game is over
		public static const SV_CHAT_MESSAGE:String = "scm"; // received when a chat message is received

		public static const CL_PLACE_UNIT:String = "cpu"; // sent when a unit is placed on the grid
		public static const CL_PLAYER_READY:String = "cpr"; // sent when the player has placed the units and is ready to play
		public static const CL_MOVE_UNIT:String = "cmu"; // sent when a unit move
		public static const CL_CHAT_MESSAGE:String = "ccm"; // sent when a chat message is sent

	}
}
