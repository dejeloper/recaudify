export interface LoginAudit {
  id: number;
  username: string;
  user: { id: number; name: string } | null;
  status: 'success' | 'failed';
  reason: string | null; // invalid_credentials | inactive | out_of_schedule | null
  ip_address: string | null;
  os: { name: string; version: string } | null;
  device_type: 'mobile' | 'tablet' | 'desktop' | null;
  geolocation: { latitude: number; longitude: number; accuracy: number | null } | null;
  logged_at: string;
}

export interface LoginAuditFilters {
  status?: 'success' | 'failed';
  user_id?: number;
}
