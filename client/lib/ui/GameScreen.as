﻿package ui {

	import flash.display.MovieClip;
	import flash.events.Event;
	import flash.events.TimerEvent;
	import flash.net.URLRequest;
	import flash.utils.Timer;
	import flash.utils.setTimeout;

	public class GameScreen extends MovieClip {

		public var world:WorldController;

		public var playerIsHost:Boolean = false;
		public var canPlaceUnits:Boolean = false;
		public var canMoveUnits:Boolean = false;
		public var unitPortraitsReady:Boolean = false;

		public var timeLeft:int = 30;
		public var timer:Timer = new Timer(1000);

		public var selectedPortrait:UnitPortrait = null;
		public var selectedUnit:Unit = null;

		public var hostPortraits:Array;
		public var guestPortraits:Array;

		public var hostPortraitFillIndex:int = 0;
		public var guestPortraitFillIndex:int = 0;

		public function activate(world:WorldController):void {

			playerIsHost = Game.amIHosting;

			hostPortraits = [hportrait1,hportrait2,hportrait3,hportrait4,hportrait5,hportrait6,hportrait7,hportrait8,hportrait9,hportrait10,hportrait11,hportrait12,hportrait13];
			guestPortraits = [gportrait1,gportrait2,gportrait3,gportrait4,gportrait5,gportrait6,gportrait7,gportrait8,gportrait9,gportrait10,gportrait11,gportrait12,gportrait13];

			timer.addEventListener(TimerEvent.TIMER, onTimerTick);

			battleScreen.visible = false;
			gameOverScreen.visible = false;
			hostUnitAvatar.visible = false;
			guestUnitAvatar.visible = false;

			this.world = world;

		}

		public function allowTilePlacement():void {
			var portraitList:Array;
			var restrictedList:Array;

			if(playerIsHost) {
				portraitList = hostPortraits;
				restrictedList = guestPortraits;
			} else {
				portraitList = guestPortraits;
				restrictedList = hostPortraits
			}

			for(var i in portraitList) {
				UnitPortrait(portraitList[i]).allowPlacement();
			}

			for(var j in restrictedList) {
				UnitPortrait(restrictedList[j]).restrictPlacement();
			}

			hideReadyDisplays();
			hideReadyButton();
			yourTurnDisplay.visible = false;
			opponentTurnDisplay.visible = false;
			stopTimer();
			placeYourUnitsDisplay.visible = true;
			waitForOpponentDisplay.visible = false;
			readyBtn.addEventListener("click", onReady);

		}

		public function restrictTilePlacement():void {

			for(var i in hostPortraits) {
				UnitPortrait(hostPortraits[i]).restrictPlacement();
			}

			for(var j in guestPortraits) {
				UnitPortrait(guestPortraits[j]).restrictPlacement();
			}

		}

		public function addUnitToPortraits(unit:Unit):void {

			if(unit.isOwnedByHost) {

				if(!UnitPortrait(hostPortraits[hostPortraitFillIndex])) {
					return;
				}

				UnitPortrait(hostPortraits[hostPortraitFillIndex]).attachToUnit(unit);
				hostPortraitFillIndex++;

			} else {

				if(!UnitPortrait(guestPortraits[guestPortraitFillIndex])) {
					return;
				}

				UnitPortrait(guestPortraits[guestPortraitFillIndex]).attachToUnit(unit);
				guestPortraitFillIndex++;

			}

		}

		public function dropPortrait(tile:Tile):void {

			if(!selectedPortrait) {
				return;
			}

			if(tile.hasUnit()) {
				return;
			}

			selectedPortrait.hasBeenPlaced = true;
			selectedPortrait.isSelected = false;

			world.placeUnitOnTile(selectedPortrait.targetUnit, tile);

			selectedPortrait = null;

		}

		public function showReadyButton():void {
			readyBtn.visible = true;
		}

		public function hideReadyButton():void {
			readyBtn.visible = false;
		}

		public function hideReadyDisplays():void {
			hostReadyDisplay.visible = false;
			guestReadyDisplay.visible = false;
		}

		private function onReady(ev:Event):void {
			hideReadyButton();
			restrictTilePlacement();

			canPlaceUnits = false;

			if(Game.amIHosting) {
				hostReadyDisplay.visible = true;
			} else {
				guestReadyDisplay.visible = true;
			}

			placeYourUnitsDisplay.visible = false;
			waitForOpponentDisplay.visible = true;

			world.playerIsReady();
		}

		public function resetUnitPortraits():void {
			hideReadyDisplays();
			hideReadyButton();

			if(unitPortraitsReady) {
				return;
			}

			for(var i in hostPortraits) {
				UnitPortrait(hostPortraits[i]).alpha = 1;
			}

			for(var j in guestPortraits) {
				UnitPortrait(guestPortraits[j]).alpha = 1;
			}

			unitPortraitsReady = true;

		}

		public function startTimer(length:int):void {
			turnTimeLabel.visible = true;
			turnTimeLeft.visible = true;
			this.timeLeft = length;
			timer.start();
		}

		public function stopTimer():void {
			turnTimeLabel.visible = false;
			turnTimeLeft.visible = false;
			timer.stop();
		}

		private function onTimerTick(ev:TimerEvent):void {
			timeLeft--;

			if(timeLeft <= 0) {
				timeLeft = 0;
				timer.stop();
			}

			turnTimeLeft.text = timeLeft+" sec";
		}

		public function allowUnitMovement():void {
			canMoveUnits = true;
			for(var i in world.units) {
				var unit:Unit = Unit(world.units[i]);

				if(unit.isMine) {
					unit.allowMovement();
				} else {
					unit.restrictMovement();
				}
			}
		}

		public function restrictUnitMovement():void {
			canMoveUnits = false;
			//deselectUnit();
			for(var i in world.units) {
				var unit:Unit = Unit(world.units[i]);
				unit.restrictMovement();
			}
		}

		public function selectUnit(unit:Unit):void {
			deselectUnit();

			selectedUnit = unit;
			selectedUnit.isSelected = true;
			selectedUnit.selectionAura.visible = true;
		}

		public function deselectUnit():void {
			if(selectedUnit) {
				selectedUnit.isSelected = false;
				selectedUnit.selectionAura.visible = false;
				selectedUnit = null;
			}
		}

		public function moveSelectedToTile(tile:Tile):void {
			world.moveUnitToTile(selectedUnit, tile);
			selectedUnit.isSelected = false;
			selectedUnit.selectionAura.visible = false;
		}

		public function reloadPlayers():void {

			var hostAvatarURL:String = "http://graph.facebook.com/"+world.hostPlayer.uid+"/picture?type=square";
			var guestAvatarURL:String = "http://graph.facebook.com/"+world.guestPlayer.uid+"/picture?type=square";

			hostPlayerAvatar.load(new URLRequest(hostAvatarURL));
			guestPlayerAvatar.load(new URLRequest(guestAvatarURL));

			hostPlayerNameFld.text = world.hostPlayer.name;
			guestPlayerNameFld.text = world.guestPlayer.name;

		}

		public function showBattleScreen(hostUnit:Unit, hostTile:Tile, hostVictory:Boolean, guestUnit:Unit, guestTile:Tile, guestVictory:Boolean):void {

			battleScreen.hostTileBackground.gotoAndStop(1);
			battleScreen.hostUnitProfile.gotoAndStop(hostUnit.getProfileFrame(hostVictory));

			battleScreen.guestTileBackground.gotoAndStop(1);
			battleScreen.guestUnitProfile.gotoAndStop(guestUnit.getProfileFrame(guestVictory));

			battleScreen.visible = true;
			setTimeout(function ():void {
				battleScreen.visible = false;
			}, 5000);

		}

		public function showHostUnitAvatar(unit:Unit):void {
			hostUnitAvatar.unitName.text = unit.getName();
			hostUnitAvatar.unitLevel.text = "lvl. "+unit.type;
			var avatarFrame:int = unit.type;
			if(unit.type == 0) {
				avatarFrame = 8;
			}

			hostUnitAvatar.unitAvatar.gotoAndStop(avatarFrame);

			hostUnitAvatar.visible = true;
		}

		public function hideHostUnitAvatar() {
			hostUnitAvatar.visible = false;
		}

		public function showGuestUnitAvatar(unit:Unit):void {
			guestUnitAvatar.unitName.text = unit.getName();
			guestUnitAvatar.unitLevel.text = "lvl. "+unit.type;

			var avatarFrame:int = unit.type;
			if(unit.type == 0) {
				avatarFrame = 8;
			}

			guestUnitAvatar.unitAvatar.gotoAndStop(avatarFrame);
			guestUnitAvatar.visible = true;
		}

		public function hideGuestUnitAvatar() {
			guestUnitAvatar.visible = false;
		}

	}
	
}
