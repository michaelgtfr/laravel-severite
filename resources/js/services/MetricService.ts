export class MetricService {
  static theHighestInObject(data: object, param: string) {
    return Object.entries(data).reduce(
      (acc, [key, value]) => (value[param] > data[acc][param] ? key : acc),
      Object.keys(data)[0],
    )
  }
}
