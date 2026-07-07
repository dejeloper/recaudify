export interface LoginConfig {
  geolocalization_login: boolean;
  login_field: 'username' | 'email';
  password_policy: PasswordPolicyConfig;
}

export interface PasswordPolicyConfig {
  min_length: number;
  require_uppercase: boolean;
  require_numbers: boolean;
  require_symbols: boolean;
}
