export interface NavItem {
  label: string;
  icons: string[];
  route: string;
  permission?: string;
}

export interface NavGroup {
  key: string;
  label: string;
  items: NavItem[];
}
