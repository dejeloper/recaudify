import { NavGroup, NavItem } from '@core/interfaces/nav.interface';

export type { NavGroup, NavItem };

export const NAV_GROUPS: NavGroup[] = [
  {
    key: 'users',
    label: 'Usuarios',
    items: [
      {
        label: 'Usuarios',
        icons: [
          'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        ],
        route: '/admin/users',
        permission: 'usuarios.ver',
      },
      {
        label: 'Roles',
        icons: [
          'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        ],
        route: '/admin/roles',
        permission: 'roles.ver',
      },
      {
        label: 'Permisos',
        icons: [
          'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
        ],
        route: '/admin/permissions',
        permission: 'permisos.ver',
      },
      {
        label: 'Horarios',
        icons: ['M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        route: '/admin/schedules',
        permission: 'horarios.ver',
      },
    ],
  },
  {
    key: 'crm',
    label: 'CRM',
    items: [
      {
        label: 'Clientes',
        icons: ['M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
        route: '#',
      },
      {
        label: 'Pedidos',
        icons: [
          'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        ],
        route: '#',
      },
    ],
  },
  {
    key: 'catalogs',
    label: 'Catálogos',
    items: [
      {
        label: 'Productos',
        icons: ['M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        route: '/admin/products',
        permission: 'catalogos.ver',
      },
    ],
  },
  {
    key: 'settings',
    label: 'Configuración',
    items: [
      {
        label: 'Parámetros',
        icons: [
          'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
          'M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        ],
        route: '/admin/parameters',
        permission: 'parametros.ver',
      },
    ],
  },
];
