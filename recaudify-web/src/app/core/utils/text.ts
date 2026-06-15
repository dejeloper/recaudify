export const lower = (value: string): string => value.trim().toLowerCase();

export const upper = (value: string): string => value.trim().toUpperCase();

export const capitalize = (value: string): string => {
  const trimmed = value.trim();
  return trimmed.charAt(0).toUpperCase() + trimmed.slice(1).toLowerCase();
};

export const titleCase = (value: string): string =>
  value
    .trim()
    .split(/\s+/)
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
    .join(' ');
