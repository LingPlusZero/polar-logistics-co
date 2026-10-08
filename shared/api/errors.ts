// 後端回應失敗時丟出，讓畫面能顯示後端給的訊息並判斷是否為 401
export class ApiError extends Error {
  status: number

  constructor(status: number, message: string) {
    super(message)
    this.status = status
  }
}
