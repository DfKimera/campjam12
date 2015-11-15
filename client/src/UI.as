package {
	import dialogs.ErrorDialog;

	import flash.display.DisplayObject;

	import org.osflash.signals.Signal;

	import ui.Connect;
	import ui.GameScreen;
	import ui.Loading;
	import ui.Lobby;
	import ui.WaitingForPlayer;

	public class UI {

		public static var connect:Connect = new Connect();
		public static var lobby:Lobby = new Lobby();
		public static var waitingForPlayer:WaitingForPlayer = new WaitingForPlayer();
		public static var loading:Loading = new Loading();
		public static var gameScreen:GameScreen = new GameScreen();

		public static var errorDialog:ErrorDialog = new ErrorDialog();

		public static var hasInteraction:Signal = new Signal();
		private static var interactionCallback:Function = null;

		public static function open(ui:DisplayObject, interactionCallback:Function = null):void {
			if(Game.currentUI) {
				Game.uiContainer.removeChild(Game.currentUI);
				Game.currentUI = null;
			}

			UI.hasInteraction.removeAll();

			if(UI.interactionCallback is Function) {
				UI.interactionCallback = null;
			}

			Game.currentUI = ui;
			Game.uiContainer.addChild(ui);

			UI.centralize(Game.currentUI);

			if(interactionCallback is Function) {
				UI.interactionCallback = interactionCallback;
				UI.hasInteraction.add(UI.interactionCallback);
			}
		}

		public static function centralize(elm:DisplayObject):void {
			elm.x = Game.stage.stageWidth / 2;
			elm.y = Game.stage.stageHeight / 2;
		}

		public static function error(msg:String):void {
			errorDialog.setMessage(msg);
			Game.uiContainer.addChild(errorDialog);
			UI.centralize(errorDialog);
		}

		public static function clearError():void {
			Game.uiContainer.removeChild(errorDialog);
		}

	}
}
