package ui {

	import fl.controls.Button;
	import fl.controls.TextInput;
	import flash.display.MovieClip;
	import flash.events.MouseEvent;


	public class Lobby extends MovieClip {

		public function Lobby() {
			createRoomBtn.addEventListener(MouseEvent.CLICK, onCreateRoom);
			joinRoomBtn.addEventListener(MouseEvent.CLICK, onJoinRoom);
		}

		private function onCreateRoom(ev:MouseEvent):void {
			var roomID:String = createRoomIdFld.text;
			UI.hasInteraction.dispatch("create", roomID);
		}

		private function onJoinRoom(ev:MouseEvent):void {
			var roomID:String = joinRoomIdFld.text;
			UI.hasInteraction.dispatch("join", roomID);
		}
	}
	
}
