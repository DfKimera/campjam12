package dialogs {
	
	import flash.display.MovieClip;
	import flash.events.Event;


	public class ErrorDialog extends MovieClip {

		public function ErrorDialog() {
			this.okBtn.addEventListener("click", onClose);
		}

		private function onClose(ev:Event):void {
			UI.clearError();
		}

		public function setMessage(msg:String):void {
			this.errorMsgFld.text = msg;
		}

	}
	
}
