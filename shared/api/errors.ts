// 後端回應失敗時丟出，讓畫面能顯示後端給的訊息並判斷是否為 401
export class ApiError extends Error {
  status: number
  // 驗證失敗（422）時各欄位的錯誤訊息，鍵為欄位名稱
  errors: Record<string, string[]>

  constructor(status: number, message: string, errors: Record<string, string[]> = {}) {
    super(message)
    this.status = status
    this.errors = errors
  }
}
