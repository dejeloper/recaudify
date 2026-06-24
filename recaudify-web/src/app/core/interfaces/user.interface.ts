export interface CurrentShift {
  is_within_schedule: boolean;
  show_status: boolean;
  day_of_week: number;
  start_time: string | null;
  end_time: string | null;
  remaining_minutes: number | null;
}

export interface User {
  id: number;
  name: string;
  username: string;
  email: string | null;
  active: boolean;
  roles: string[];
  permissions: string[];
  current_shift?: CurrentShift;
  shift_status_enabled?: boolean;
  shift_countdown_enabled?: boolean;
  ip_address?: string;
}

export interface UserPayload extends Record<string, unknown> {
  name: string;
  username: string;
  email: string | null;
  password?: string;
  password_confirmation?: string;
  role: string | null;
}
