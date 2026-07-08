export interface Permission {
  id: number;
  name: string;
  guard_name: string;
}

export interface PermissionFilters {
  search?: string;
}
