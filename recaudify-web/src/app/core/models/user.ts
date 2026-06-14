export interface User {
  id: number;
  name: string;
  email: string | null;
  roles: string[];
  permissions: string[];
}
