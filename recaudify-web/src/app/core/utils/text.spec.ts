import {capitalize, lower, titleCase, upper} from '@core/utils/text';

describe('lower', () => {
  it('converts to lowercase', () => expect(lower('ADMIN')).toBe('admin'));
  it('trims whitespace', () => expect(lower('  Admin  ')).toBe('admin'));
  it('handles already lowercase', () => expect(lower('user')).toBe('user'));
  it('handles mixed case', () => expect(lower('UsEr.NaMe')).toBe('user.name'));
});

describe('upper', () => {
  it('converts to uppercase', () => expect(upper('admin')).toBe('ADMIN'));
  it('trims whitespace', () => expect(upper('  admin  ')).toBe('ADMIN'));
  it('handles already lowercase', () => expect(upper('user')).toBe('USER'));
  it('handles mixed case', () => expect(upper('UsEr.NaMe')).toBe('USER.NAME'));
});

describe('capitalize', () => {
  it('capitalizes first letter and lowercases the rest', () => {
    expect(capitalize('hola')).toBe('Hola');
    expect(capitalize('HOLA')).toBe('Hola');
    expect(capitalize('hOLA')).toBe('Hola');
  });
  it('trims whitespace', () => expect(capitalize('  hola  ')).toBe('Hola'));
});

describe('titleCase', () => {
  it('capitalizes each word', () => expect(titleCase('juan perez')).toBe('Juan Perez'));
  it('handles extra spaces', () => expect(titleCase('  juan   perez  ')).toBe('Juan Perez'));
  it('lowercases uppercase input', () => expect(titleCase('JUAN PEREZ')).toBe('Juan Perez'));
});
