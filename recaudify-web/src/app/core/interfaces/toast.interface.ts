export type ToastType = 'success' | 'error' | 'warning' | 'info';
export type ToastPosition = 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | 9;
export type ToastSize = 'sm' | 'md' | 'lg';

export interface Toast {
  id: string;
  message: string;
  type: ToastType;
}

export interface SizeStyle {
  container: string;
  card: string;
  icon: string;
  closeIcon: string;
  text: string;
}
