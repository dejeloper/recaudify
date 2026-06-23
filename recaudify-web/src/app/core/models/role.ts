import { Permission } from './permission';

export type { Permission };

export interface Role {
  id: number;
  name: string;
  guard_name: string;
  permissions: Permission[];
}
