import { User } from '@core/interfaces/user.interface';

export interface LoginResponse {
  token: string;
  token_type: string;
  expires_in: number;
  user: User;
}
