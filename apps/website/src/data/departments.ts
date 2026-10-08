export type DepartmentIconName = 'packaging' | 'field' | 'reindeer' | 'audit' | 'customer'

export interface Department {
  key: DepartmentIconName
  name: string
  description: string
  // 各部門用不同背景色區隔，皆維持深色以確保白字對比
  background: string
}

// 文案來源：docs/website.md「五大業務」
export const DEPARTMENTS: Department[] = [
  {
    key: 'packaging',
    name: '禮物包裝部',
    description: '以每分鐘 4,200 份的速度，確保每份禮物都有完整的外觀。',
    background: '#0B2545',
  },
  {
    key: 'field',
    name: '外勤機動部',
    description: '在各種口徑的煙囪中，提供安全、迅速、不留痕跡的入戶服務。',
    background: '#1D4E6B',
  },
  {
    key: 'reindeer',
    name: '馴鹿管理部',
    description: '負責運輸動力的調度與養護。馴鹿之法律定位，本公司持續與主管機關溝通中。',
    background: '#2F5D50',
  },
  {
    key: 'audit',
    name: '乖寶寶稽核部',
    description: '依據嚴謹的內部標準，確保每一份禮物送到正確的對象。',
    background: '#4B3F72',
  },
  {
    key: 'customer',
    name: '客戶體驗部',
    description: '傾聽每一位客戶的聲音，並以最快速度將其列入處理佇列。',
    background: '#5A2A3B',
  },
]
