export interface UserSession {
  id: number;
  user?: { id: number; name: string };
  ip_address: string | null;
  os: { name: string; version: string } | null;
  device_type: 'mobile' | 'tablet' | 'desktop' | null;
  last_used_at: string | null;
  created_at: string;
  expires_at: string;
  is_current?: boolean;
}
