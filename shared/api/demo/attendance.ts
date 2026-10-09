import type { Attendance } from '../types'

// Demo 的出勤紀錄不存快照，而是依「今天」往前產生，畫面上永遠有最近的資料。
// 規則與後端 AttendanceSeeder 完全一致（同樣的雜湊、時間範圍與失蹤精靈），改一邊要改另一邊

const DAYS = 14

// 失蹤精靈最後出勤日距今幾天
const LAST_SEEN_DAYS_AGO: Record<string, number> = {
  E010: 7,
  E012: 4,
  E015: 10,
  E018: 8,
}

// 與 PHP crc32() 相同的演算法，才能產生跟後端一樣的「隨機」時間
const CRC_TABLE = Array.from({ length: 256 }, (_, n) => {
  let c = n
  for (let k = 0; k < 8; k++) {
    c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1
  }
  return c >>> 0
})

const crc32 = (text: string): number => {
  let crc = 0xffffffff
  for (const byte of new TextEncoder().encode(text)) {
    crc = CRC_TABLE[(crc ^ byte) & 0xff] ^ (crc >>> 8)
  }
  return (crc ^ 0xffffffff) >>> 0
}

const pad = (value: number) => String(value).padStart(2, '0')

const dateString = (date: Date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`

const timeString = (date: Date) => `${dateString(date)} ${pad(date.getHours())}:${pad(date.getMinutes())}`

export function generateAttendance(elves: { number: string; name: string }[], now = new Date()): Attendance[] {
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const addDays = (date: Date, days: number) =>
    new Date(date.getFullYear(), date.getMonth(), date.getDate() + days)

  const firstDay = addDays(today, -DAYS)
  const records: Attendance[] = []

  // 與後端相同，依精靈編號排序後依序給 id
  const sorted = [...elves].sort((a, b) => a.number.localeCompare(b.number))

  for (const elf of sorted) {
    const lastSeen = addDays(today, -(LAST_SEEN_DAYS_AGO[elf.number] ?? 1))

    for (let day = firstDay; day <= lastSeen; day = addDays(day, 1)) {
      // 週六、週日不上班
      if (day.getDay() === 0 || day.getDay() === 6) {
        continue
      }

      const key = `${elf.number}|${dateString(day)}`
      const startHash = crc32(`${key}|in`)
      const workHash = crc32(`${key}|work`)

      const clockIn = new Date(day.getFullYear(), day.getMonth(), day.getDate(), 8, (startHash % 19) * 5)
      const workMinutes = workHash % 100 < 18 ? 420 + (workHash % 11) * 5 : 480 + (workHash % 31) * 5
      const clockOut = new Date(clockIn.getTime() + workMinutes * 60_000)

      records.push({
        id: records.length + 1,
        elfNumber: elf.number,
        elfName: elf.name,
        clockIn: timeString(clockIn),
        clockOut: timeString(clockOut),
        workMinutes,
      })
    }
  }

  return records
}
