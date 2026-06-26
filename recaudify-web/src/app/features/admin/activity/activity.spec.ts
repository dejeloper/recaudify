import { provideZonelessChangeDetection, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { ActivitiesService } from '@core/services/activities.service';
import { ActivityFeed } from './activity';

async function setup() {
  const service = {
    items: signal([]),
    meta: signal(null),
    loading: signal(false),
    loadingMore: signal(false),
    load: vi.fn(),
    loadMore: vi.fn(),
    hasMore: vi.fn().mockReturnValue(false),
  };

  await TestBed.configureTestingModule({
    imports: [ActivityFeed],
    providers: [
      provideZonelessChangeDetection(),
      { provide: ActivitiesService, useValue: service },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(ActivityFeed);
  fixture.detectChanges();
  return { comp: fixture.componentInstance as any, service };
}

describe('ActivityFeed', () => {
  it('loads activity on init and delegates loadMore', async () => {
    const { comp, service } = await setup();
    expect(service.load).toHaveBeenCalled();

    comp.loadMore();
    expect(service.loadMore).toHaveBeenCalled();
  });

  it('fieldLabel translates known fields and falls back', async () => {
    const { comp } = await setup();
    expect(comp.fieldLabel('Product', 'value')).toBe('Valor');
    expect(comp.fieldLabel('Product', 'desconocido')).toBe('desconocido');
    expect(comp.fieldLabel(null, 'x')).toBe('x');
  });

  it('formatValue handles booleans, nulls and money', async () => {
    const { comp } = await setup();
    expect(comp.formatValue('active', true)).toBe('Sí');
    expect(comp.formatValue('active', false)).toBe('No');
    expect(comp.formatValue('name', null)).toBe('—');
    expect(comp.formatValue('value', 350000)).toMatch(/^\$\s/);
    expect(comp.formatValue('name', 'Biblia')).toBe('Biblia');
  });

  it('eventClasses returns a color per event', async () => {
    const { comp } = await setup();
    expect(comp.eventClasses('created').dot).toContain('green');
    expect(comp.eventClasses('deleted').dot).toContain('red');
    expect(comp.eventClasses('otro').dot).toContain('gray');
  });
});
