package {
	import com.demonsters.debugger.MonsterDebugger;

	public class Debugger {

		public static function initialize():void {
			MonsterDebugger.initialize(Game.instance);
		}

		public static function trace(msg:*):void {
			MonsterDebugger.trace(Debugger, msg);
		}

	}
}
