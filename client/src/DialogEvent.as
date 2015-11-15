package {
	import flash.events.Event;

	public class DialogEvent extends Event {

		public static const DIALOG_OK:String = "dlgOK";
		public static const DIALOG_YES:String = "dlgYes";
		public static const DIALOG_NO:String = "dlgNo";

		public var data:Object = null;

		public function DialogEvent(type:String, opts:Object, bubbles:Boolean = false, cancelable:Boolean = false) {
			this.data = opts;
			super(type, bubbles, cancelable);
		}

	}
}
