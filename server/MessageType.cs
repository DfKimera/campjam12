using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace Campjam12 {

	public class MessageType {

		public const string SV_GAME_PREPARE = "sgp";
		public const string SV_OPPONENT_READY = "sor";
		public const string SV_YOUR_TURN = "syt";
		public const string SV_OPPONENT_TURN = "sot";
		public const string SV_UNIT_MOVED = "sum";
		public const string SV_UNIT_PLACED = "sup";
		public const string SV_UNIT_REVEALED = "sru";
		public const string SV_COMBAT_SCREEN = "scs";
		public const string SV_TURN_TIMEOUT = "stt";
		public const string SV_GAME_OVER = "sgo";
		public const string SV_CHAT_MESSAGE = "scm";

		public const string CL_PLACE_UNIT = "cpu";
		public const string CL_PLAYER_READY = "cpr";
		public const string CL_MOVE_UNIT = "cmu";
		public const string CL_CHAT_MESSAGE = "ccm";

	}
}
