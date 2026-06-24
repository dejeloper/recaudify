import { Permission } from './permission.interface';

export interface Role {
  id: number;
  name: string;
  guard_name: string;
  permissions: Permission[];
}
