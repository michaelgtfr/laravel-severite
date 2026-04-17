export interface IdentityCard {
  globalMetrics: Record<string, any> & { key: string }
  parentFunction: string[]
  callgraph: { xPosition: number; yPosition: number }
}
