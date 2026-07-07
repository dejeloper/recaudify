import { provideZonelessChangeDetection, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { Role } from '@core/interfaces/role.interface';
import { RolesService } from '@core/services/roles.service';
import { Roles } from './roles';

const sample: Role = { id: 1, name: 'admin', guard_name: 'api', permissions: [] };

async function setup() {
  const service = {
    items: signal<Role[]>([]),
    trashed: signal<Role[]>([]),
    loading: signal(false),
    loadingTrashed: signal(false),
    showTrashed: signal(false),
    load: vi.fn(),
    toggleTrashed: vi.fn(),
    remove: vi.fn().mockReturnValue(of(undefined)),
    restoreItem: vi.fn().mockReturnValue(of(undefined)),
  };

  await TestBed.configureTestingModule({
    imports: [Roles],
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: RolesService, useValue: service },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(Roles);
  fixture.detectChanges();
  return { comp: fixture.componentInstance as any, service };
}

describe('Roles (list)', () => {
  it('loads roles on init', async () => {
    const { service } = await setup();
    expect(service.load).toHaveBeenCalled();
  });

  it('toggleTrashed delegates to the service', async () => {
    const { comp, service } = await setup();
    comp.toggleTrashed();
    expect(service.toggleTrashed).toHaveBeenCalled();
  });

  it('delete calls remove when confirmed', async () => {
    const { comp, service } = await setup();
    vi.spyOn(window, 'confirm').mockReturnValue(true);

    comp.delete(sample);

    expect(service.remove).toHaveBeenCalledWith(sample);
  });

  it('delete does nothing when not confirmed', async () => {
    const { comp, service } = await setup();
    vi.spyOn(window, 'confirm').mockReturnValue(false);

    comp.delete(sample);

    expect(service.remove).not.toHaveBeenCalled();
  });

  it('restore delegates to the service', async () => {
    const { comp, service } = await setup();
    comp.restore(sample);
    expect(service.restoreItem).toHaveBeenCalledWith(sample);
  });
});
