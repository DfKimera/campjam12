package ui {
	
	import flash.display.MovieClip;
	import flash.display.SimpleButton;
	import flash.events.Event;


	public class Connect extends MovieClip {

		public var connectWithFacebookBtn:SimpleButton;
		
		public function Connect() {
			this.connectWithFacebookBtn.addEventListener("click", onConnectBtnClick);
		}

		public function onConnectBtnClick(ev:Event):void {
			UI.hasInteraction.dispatch();
		}

	}
	
}
