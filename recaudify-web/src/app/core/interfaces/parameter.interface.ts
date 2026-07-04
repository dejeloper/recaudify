export interface Parameter {
  id: number;
  type: ParameterType;
  type_label: string;
  key: string;
  value: string;
  typed_value: unknown;
  cast: string;
  description: string | null;
  is_editable: boolean;
  updated_at: string;
}

export const PARAMETER_TYPES = [
  'authentication',
  'application',
  'business',
  'configuration',
  'notification',
  'security',
] as const;

export type ParameterType = (typeof PARAMETER_TYPES)[number];

export const PARAMETER_TYPE_LABELS: Record<ParameterType, string> = {
  authentication: 'Autenticación',
  application: 'Aplicación',
  business: 'Negocio',
  configuration: 'Configuración',
  notification: 'Notificaciones',
  security: 'Seguridad',
};

export const PARAMETER_TYPE_COLORS: Record<ParameterType, string> = {
  authentication: 'text-orange-700 bg-orange-50',
  application: 'text-purple-700 bg-purple-50',
  business: 'text-blue-700 bg-blue-50',
  configuration: 'text-gray-700 bg-gray-100',
  notification: 'text-teal-700 bg-teal-50',
  security: 'text-rose-700 bg-rose-50',
};
