export interface MenuItem {
  id: number;
  parent_id: number | null;
  label: string;
  icons: string[] | null;
  route: string | null;
  permission: string | null;
  order: number;
  is_active: boolean;
  children?: MenuItem[];
}
