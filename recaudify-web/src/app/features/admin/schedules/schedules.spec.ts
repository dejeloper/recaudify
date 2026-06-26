import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of, throwError } from 'rxjs';
import { UsersService } from '@core/services/users.service';
import { Schedules } from './schedules';

async function setup(getAll = of([{ id: 1, name: 'Ana', username: 'ana', roles: ['agente'] }])) {
  const usersService = { getAll: vi.fn().mockReturnValue(getAll) };

  await TestBed.configureTestingModule({
    imports: [Schedules],
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: UsersService, useValue: usersService },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(Schedules);
  fixture.detectChanges();
  return { comp: fixture.componentInstance as any, usersService };
}

describe('Schedules (users list)', () => {
  it('loads users on init', async () => {
    const { comp, usersService } = await setup();
    expect(usersService.getAll).toHaveBeenCalled();
    expect(comp.users()).toHaveLength(1);
    expect(comp.loading()).toBe(false);
  });

  it('stops loading on error', async () => {
    const { comp } = await setup(throwError(() => new Error('x')));
    expect(comp.loading()).toBe(false);
  });
});
