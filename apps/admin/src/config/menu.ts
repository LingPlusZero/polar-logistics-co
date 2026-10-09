import type { Permission } from '@shared/api'
import type { Component } from 'vue'

export interface MenuItem {
  label: string
  path: string
  permission?: Permission
  // 頁面完成後再補上；未提供時顯示「建置中」頁
  component?: () => Promise<Component>
}

export interface MenuGroup {
  label: string
  children: MenuItem[]
}

// 選單結構來源：docs/admin.md「選單」。路由也由這份設定產生，新增頁面只需改這裡
export const MENU: (MenuItem | MenuGroup)[] = [
  {
    label: '請假',
    children: [
      {
        label: '請假申請',
        path: '/leave/apply',
        permission: 'leave.apply',
        component: () => import('../views/LeaveApplyView.vue'),
      },
      {
        label: '請假審核',
        path: '/leave/review',
        permission: 'leave.review',
        component: () => import('../views/LeaveReviewView.vue'),
      },
    ],
  },
  {
    label: '我要申訴',
    path: '/complaint',
    permission: 'complaint.file',
    component: () => import('../views/ComplaintFileView.vue'),
  },
  {
    label: '精靈管理',
    children: [
      {
        label: '精靈名冊',
        path: '/elves',
        permission: 'elf.roster',
        component: () => import('../views/ElfRosterView.vue'),
      },
      {
        label: '精靈請假紀錄',
        path: '/elves/leave-records',
        permission: 'elf.leave',
        component: () => import('../views/LeaveRecordsView.vue'),
      },
      {
        label: '精靈被申訴紀錄',
        path: '/elves/complaints',
        permission: 'elf.complaint',
        component: () => import('../views/ComplaintRecordsView.vue'),
      },
      {
        label: '精靈出勤紀錄',
        path: '/elves/attendance',
        permission: 'elf.attendance',
        component: () => import('../views/AttendanceView.vue'),
      },
    ],
  },
  { label: '動力單位管理', path: '/reindeer', permission: 'reindeer.manage' },
  { label: '職缺管理', path: '/career', permission: 'career.manage' },
]

export const isGroup = (item: MenuItem | MenuGroup): item is MenuGroup => 'children' in item

export const flattenMenu = (): MenuItem[] =>
  MENU.flatMap((item) => (isGroup(item) ? item.children : [item]))

// 依權限過濾，群組內沒有任何可見項目時整個群組隱藏
export function filterMenu(can: (permission: Permission) => boolean): (MenuItem | MenuGroup)[] {
  const visible = (item: MenuItem) => !item.permission || can(item.permission)

  return MENU.flatMap((item): (MenuItem | MenuGroup)[] => {
    if (!isGroup(item)) {
      return visible(item) ? [item] : []
    }

    const children = item.children.filter(visible)
    return children.length > 0 ? [{ ...item, children }] : []
  })
}
