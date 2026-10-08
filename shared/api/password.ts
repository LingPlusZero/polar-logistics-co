// 密碼規則（docs/admin.md）：至少 12 字元，且各一個大寫、小寫、數字、特殊符號
// 後端 LoginRequest 有同樣的規則，兩邊需一起改
export const PASSWORD_MIN_LENGTH = 12

export const PASSWORD_RULES = [
  { label: `至少 ${PASSWORD_MIN_LENGTH} 個字元`, test: (value: string) => value.length >= PASSWORD_MIN_LENGTH },
  { label: '大寫英文字母', test: (value: string) => /[A-Z]/.test(value) },
  { label: '小寫英文字母', test: (value: string) => /[a-z]/.test(value) },
  { label: '數字', test: (value: string) => /[0-9]/.test(value) },
  { label: '特殊符號', test: (value: string) => /[^A-Za-z0-9]/.test(value) },
]

export const isValidPassword = (value: string) => PASSWORD_RULES.every((rule) => rule.test(value))
