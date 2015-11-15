using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace Campjam12 {

	public class Unit {

		public int id = 0;

		public int column = 0;
		public int row = 0;
		public int type = 0;

		public bool isAlive = true;

		public int range = 1;
		public bool canWalkOnWater = false;
		public bool canSwapWithUnits = false;
		public bool canHideOnTallGrass = false;
		public bool canPlaceWeb = false;
		public bool hasFreeMovement = false;
		public bool isFlag = false;

		public Player owner;
		public Tile currentTile;

		public void reloadSkills() {
			canHideOnTallGrass = (type == 1);
			range = (type == 2) ? 2 : 1;
			hasFreeMovement = (type == 3);
			canPlaceWeb = (type == 4);
			canSwapWithUnits = (type == 5);
			canWalkOnWater = (type == 6);

			isFlag = (type == 7);
		}

		public void moveTo(int targetCol, int targetRow) {
			this.column = targetCol;
			this.row = targetRow;
		}

		public void placeOnTile(Tile tile) {
			this.currentTile = tile;
			tile.currentUnit = this;
			this.moveTo(tile.column, tile.row);
		}

		public bool attack(Unit enemy) {

			if(this.type == 1 && enemy.type == 6) {
				return true;
			}

			if(this.type == 6 && enemy.type == 1) {
				return false;
			}

			return (this.type >= enemy.type);

		}

		public void kill() {
			this.isAlive = false;
			if(this.currentTile != null) {
				this.currentTile.currentUnit = null;
			}
			this.currentTile = null;
			this.column = -1;
			this.row = -1;
		}

	}

}
