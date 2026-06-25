export interface LoginAudit {
  user_id: number;
  session_id: string;
  logged_at: string;
  ip_address: string | null;
  user_agent: string;
  os: { name: string; version: string };
  device_type: 'mobile' | 'tablet' | 'desktop';
  geolocation: { latitude: number; longitude: number; accuracy: number } | null;
}
