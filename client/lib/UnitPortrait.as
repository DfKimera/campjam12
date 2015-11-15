package {
	
	import flash.display.MovieClip;
	import flash.events.Event;
	import flash.events.MouseEvent;
	import flash.geom.Point;

	public class UnitPortrait extends MovieClip {

		public var isSelected:Boolean = false;
		public var canBePlaced:Boolean = false;
		public var hasBeenPlaced:Boolean = false;

		public var targetUnit:Unit = null;

		
		public function UnitPortrait() {
			this.deathMark.visible = false;
			this.addEventListener(MouseEvent.CLICK, onClick)
		}

		public function attachToUnit(unit:Unit):void {
			this.targetUnit = unit;
			unit.portrait = this;
			this.gotoAndStop(unit.type + 1);
		}

		public function allowPlacement():void {
			this.canBePlaced = true;
			this.buttonMode = true;
		}

		public function restrictPlacement():void {
			this.canBePlaced = false;
			this.buttonMode = false;
			this.alpha = 0.1;
		}

		private function onClick(ev:MouseEvent):void {

			if(!canBePlaced) {
				return;
			}

			if(UI.gameScreen.selectedPortrait) {
				UI.gameScreen.selectedPortrait.alpha = 1;
			}

			UI.gameScreen.selectedPortrait = this;
			UI.gameScreen.selectedPortrait.alpha = 0.5;

		}

		public function onUnitKilled():void {
			this.deathMark.visible = true;
		}

	}

}
