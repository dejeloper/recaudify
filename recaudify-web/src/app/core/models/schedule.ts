export interface Schedule {
  id: number;
  user_id: number;
  day_of_week: number;
  day_name: string;
  start_time: string;
  end_time: string;
}
