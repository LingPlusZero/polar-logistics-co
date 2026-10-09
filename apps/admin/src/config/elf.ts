import type { EditableElfStatus, ElfRank } from '@shared/api'

// 下拉選單選項；職級順序即晉升順序（docs/brand.md），狀態來源 docs/admin.md「精靈名冊」。「請假」由請假單決定，不能手動選
export const ELF_RANKS: ElfRank[] = ['實習精靈', '正式精靈', '資深精靈', '部長', '副聖誕老人']
export const ELF_STATUSES: EditableElfStatus[] = ['正常', '可能失蹤']
