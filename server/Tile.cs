using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace Campjam12 {
	public class Tile {

		public const int TYPE_GRASS = 1;
		public const int TYPE_TALL_GRASS = 2;
		public const int TYPE_DIRT = 3;
		public const int TYPE_WATER = 4;

		public int row = 0;
		public int column = 0;
		public int type = 0;

		public TileGrid grid;
		public Unit currentUnit;

		public Tile(int type, int col, int row, TileGrid grid) {
			this.type = type;
			this.row = row;
			this.column = col;
			this.grid = grid;
		}

		public bool hasUnit() {
			return (currentUnit != null);
		}

		public List<Tile> getAttackingNeighbors() {

			List<Tile> neighbors = new List<Tile>();

			int nx = column;
			int ny = row - 1;
			if(grid.ContainsKey(nx) && grid[nx].ContainsKey(ny)) {
				neighbors.Add(grid[nx][ny]);
			}
			
			int sx = column;
			int sy = row + 1;
			if(grid.ContainsKey(sx) && grid[sx].ContainsKey(sy)) {
				neighbors.Add(grid[sx][sy]);
			}
			
			int ex = column + 1;
			int ey = row;
			if(grid.ContainsKey(ex) && grid[ex].ContainsKey(ey)) {
				neighbors.Add(grid[ex][ey]);
			}

			int wx = column - 1;
			int wy = row;
			if(grid.ContainsKey(wx) && grid[wx].ContainsKey(wy)) {
				neighbors.Add(grid[wx][wy]);
			}

			return neighbors;

		}

	}
}
