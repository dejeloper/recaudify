export interface User {
  id: number;
  name: string;
  username: string;
  email: string | null;
  roles: string[];
  permissions: string[];
}
