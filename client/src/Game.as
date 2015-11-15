package {

	import com.facebook.graph.Facebook;
	import com.facebook.graph.data.FacebookAuthResponse;

	import flash.display.DisplayObject;

	import flash.display.MovieClip;
	import flash.display.Sprite;
	import flash.display.Stage;
	import flash.display.StageAlign;
	import flash.display.StageScaleMode;
	import flash.utils.setTimeout;

	import playerio.Client;
	import playerio.Connection;

	[SWF(backgroundColor="0x060E01",width=1000,height=700,frameRate=30)]
	public class Game extends Sprite {

		public static const FB_APP_ID:String = "468620166495976";

		public static var instance:Game;

		public static var net:Networking;
		public static var stage:Stage;
		public static var grid:TileGrid;
		public static var world:WorldController;

		public static var amIHosting:Boolean = false;

		public static var playerName:String = "Guest";

		public static var facebookIsInitialized:Boolean = false;
		public static var facebookSession:FacebookAuthResponse;
		public static var facebookProfile:Object = null;

		public static var uiBackground:UIBackground;
		public static var scene:MovieClip;
		public static var unitsContainer:MovieClip = new MovieClip();
		public static var uiContainer:MovieClip;
		public static var currentUI:DisplayObject = null;

	    public function Game() {

		    Game.instance = this;

		    stage.scaleMode = StageScaleMode.NO_SCALE;
		    stage.align = StageAlign.TOP_LEFT;

		    Game.stage = stage;

		    this.initializeDebugger();
		    this.initializeGrid();
		    this.initializeRenderer();
		    this.initializeNetworking();

		    UI.open(UI.connect, onFacebookConnect);

	    }

		// ------------------------------------------------------------------------------------------------------------
		// Initializers
		// ------------------------------------------------------------------------------------------------------------

		public function initializeGrid():void {
			Game.grid = new TileGrid();
		}

		public function initializeRenderer():void {
			Game.uiBackground = new UIBackground();
			Game.stage.addChild(Game.uiBackground);
			UI.centralize(Game.uiBackground);

			Game.scene = new MovieClip();
			Game.stage.addChild(scene);

			Game.scene.addChild(grid);
			Game.scene.addChild(unitsContainer);

			Game.uiContainer = new MovieClip();
			Game.stage.addChild(Game.uiContainer);
		}

		public function initializeNetworking():void {
			Game.net = new Networking();
		}

		public function initializeDebugger():void {
			Debugger.initialize();
		}

		// ------------------------------------------------------------------------------------------------------------
		// Facebook authentication
		// ------------------------------------------------------------------------------------------------------------

		private function onFacebookConnect():void {
			if(Game.facebookIsInitialized) {
				loginWithFacebook();
			} else {
				initializeFacebook();
			}
		}

		public function initializeFacebook():void {
			Debugger.trace("FB Init...");
			Facebook.init(Game.FB_APP_ID, onFacebookInit, {cookie: true, status: true});
		}

		private function onFacebookInit(session:Object, fail:Object):void {
			Debugger.trace("FB Init OK! ");
			Debugger.trace(session);

			Game.facebookIsInitialized = true;

			setTimeout(function ():void {
				loginWithFacebook();
			}, 200);

		}

		public function loginWithFacebook():void {
			Facebook.login(onFacebookLogin, {scope: "email, user_about_me, friends_about_me, user_games_activity, publish_actions"});
		}

		public function onFacebookLogin(response:Object, fail:Object):void {

			if(!response) {
				UI.error("Could not login, please try again!");
				return;
			}

			var auth:FacebookAuthResponse = FacebookAuthResponse(response);
			Game.facebookSession = auth;

			getPlayerFacebookProfile(joinLobby);

		}

		public function getPlayerFacebookProfile(callback:Function):void {
			Facebook.api("/me", function (result:Object,  fail:Object):void {
				Game.facebookProfile = result;
				Game.playerName = Game.facebookProfile.name;
				if(callback is Function) {
					callback.call(Game.instance, result);
				}
			});
		}

		// ------------------------------------------------------------------------------------------------------------
		// Lobby & Matchmaking
		// ------------------------------------------------------------------------------------------------------------

		public function joinLobby(playerProfile:Object):void {

			if(!playerProfile) {
				UI.error("Failed to get player's Facebook profile!");
				return;
			}

			Game.net.hasConnected.add(onJoinedLobby);
			Game.net.connectToServer(facebookSession.uid, facebookSession.accessToken);
		}

		private function onJoinedLobby(client:Client):void {
			UI.open(UI.lobby, onLobbyInteraction);

			Game.net.hasHostedRoom.add(onRoomCreate);
			Game.net.hasJoinedRoom.add(onRoomJoin);

		}

		private function onLobbyInteraction(action:String, roomID:String):void {
			if(action == "create") {
				Debugger.trace("Hosting room: "+roomID);
				Game.net.createRoom(roomID, onRoomCreate);
			} else if(action == "join") {
				Debugger.trace("Joining room: "+roomID);
				Game.net.joinRoom(roomID, onRoomJoin);
			} else {
				UI.error("Unknown lobby action: "+action);
			}
		}

		private function onRoomCreate(conn:Connection):void {
			Debugger.trace("Room created, waiting for another player");
			Game.amIHosting = true;
			UI.open(UI.waitingForPlayer);
			this.prepareWorldController();
		}

		private function onRoomJoin(conn:Connection):void {
			Debugger.trace("Room joined, preparing game...");
			UI.open(UI.loading);
			this.prepareWorldController();
		}


		private function prepareWorldController():void {
			Debugger.trace("World is ready");
			world = new WorldController();
		}

	}
}
