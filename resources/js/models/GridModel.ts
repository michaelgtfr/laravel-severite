export default class GridModel {
  grid
  xMax = 0
  yMax = 0
  constructor() {
    this.grid = {}
  }

  exist(x, y) {
    return !!this.grid[y] && !!this.grid[y][x]
  }

  blockPosition(x, y, cle) {
    if (!this.grid[y]) {
      this.grid[y] = []
    }

    this.grid[y][x] = cle

    if (x > this.xMax) {
      this.xMax = x
    }

    if (y > this.yMax) {
      this.yMax = y
    }
  }

  getGrid() {
    return this.grid
  }

  getXSize() {
    return this.xMax
  }

  getYSize() {
    return this.yMax
  }
}
