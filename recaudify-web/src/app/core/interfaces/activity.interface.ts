export interface ActivityChange {
  field: string;
  old: unknown;
  new: unknown;
}

export interface Activity {
  id: number;
  log_name: string | null;
  event: string; // created | updated | deleted | restored
  description: string; // verbo en español: creó / actualizó / ...
  model: string | null; // ej. "Product"
  model_label: string | null; // ej. "producto"
  subject: { id: number | null; label: string | null };
  causer: { id: number; name: string } | null;
  changes: ActivityChange[];
  created_at: string;
}

export interface ActivityFilters {
  model?: string;
  subject_id?: number;
  causer_id?: number;
}
