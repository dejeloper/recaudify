import { provideZonelessChangeDetection, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { Permission } from '@core/interfaces/permission.interface';
import { PermissionsService } from '@core/services/permissions.service';
import { Permissions } from './permissions';

const sample: Permission = { id: 1, name: 'users.create', guard_name: 'api' };

async function setup() {
  const service = {
    items: signal<Permission[]>([]),
    meta: signal(null),
    trashed: signal<Permission[]>([]),
    loading: signal(false),
    loadingTrashed: signal(false),
    showTrashed: signal(false),
    grouped: signal<{ module: string; perms: Permission[] }[]>([]),
    groupedTrashed: signal<{ module: string; perms: Permission[] }[]>([]),
    load: vi.fn(),
    goToPage: vi.fn(),
    toggleTrashed: vi.fn(),
    actionLabel: vi.fn((name: string) => name.split('.')[1] ?? name),
    remove: vi.fn().mockReturnValue(of(undefined)),
    restoreItem: vi.fn().mockReturnValue(of(undefined)),
  };

  await TestBed.configureTestingModule({
    imports: [Permissions],
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: PermissionsService, useValue: service },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(Permissions);
  fixture.detectChanges();
  return { comp: fixture.componentInstance as any, service };
}

describe('Permissions (list)', () => {
  it('loads permissions on init', async () => {
    const { service } = await setup();
    expect(service.load).toHaveBeenCalled();
  });

  it('toggleTrashed delegates to the service', async () => {
    const { comp, service } = await setup();
    comp.toggleTrashed();
    expect(service.toggleTrashed).toHaveBeenCalled();
  });

  it('actionLabel delegates to the service', async () => {
    const { comp, service } = await setup();
    expect(comp.actionLabel('users.create')).toBe('create');
    expect(service.actionLabel).toHaveBeenCalledWith('users.create');
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
