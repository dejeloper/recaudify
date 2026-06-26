import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { AuthService } from '@core/services/auth.service';
import { AdminDashboard } from './admin-dashboard';

async function setup(permissions: string[]) {
  const auth = { hasPermission: (p: string) => permissions.includes(p) };

  await TestBed.configureTestingModule({
    imports: [AdminDashboard],
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: AuthService, useValue: auth },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(AdminDashboard);
  fixture.detectChanges();
  return fixture.componentInstance as any;
}

describe('AdminDashboard', () => {
  it('exposes permission flags from AuthService', async () => {
    const comp = await setup(['usuarios.ver', 'catalogos.ver']);

    expect(comp.canSeeUsers()).toBe(true);
    expect(comp.canSeeCatalogs()).toBe(true);
    expect(comp.canSeeRoles()).toBe(false);
    expect(comp.canSeeAccessLog()).toBe(false);
  });
});
