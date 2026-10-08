export type ValueIconName = 'punctual' | 'precise' | 'delight'

// 文案來源：docs/website.md「首頁 / 關於我們」

export const MILESTONES = [
  { year: '1823', event: '極地物流成立，完成第一次年度配送。' },
  { year: '1887', event: '導入名單審核制度，成立乖寶寶稽核部前身。' },
  { year: '1912', event: '成立馴鹿管理部，建立動力單元調度制度。馴鹿之法律定位，持續與主管機關溝通中。' },
  { year: '1939', event: '01 號動力單元加入車隊，搭載前導照明模組，夜間視野大幅提升。' },
  { year: '1987', event: '成立客戶體驗部，開始受理客戶來信，並列入處理佇列。' },
  { year: '2010', event: '導入精靈管理系統，全年度人力調度進入系統化管理。' },
  { year: '2025', event: '年配送禮物數達 22.3 億份，準時送達率 99.97%，連續 202 年正成長。宣布「北風計畫」。' },
]

export const CORE_VALUES: { icon: ValueIconName; title: string; description: string }[] = [
  { icon: 'punctual', title: '準時', description: '每年 12/24 是唯一交貨期限，沒有任何延期的可能。' },
  { icon: 'precise', title: '精準', description: '確保每一份禮物送到正確的對象。' },
  { icon: 'delight', title: '驚喜滿滿', description: '讓每一份禮物，都在孩子最期待的時刻出現。' },
]
