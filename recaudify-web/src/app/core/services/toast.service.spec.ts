import { ToastService } from '@core/services/toast.service';

describe('ToastService', () => {
  let service: ToastService;

  beforeEach(() => {
    service = new ToastService();
    vi.useFakeTimers();
  });

  afterEach(() => vi.useRealTimers());

  it('starts with no toasts', () => {
    expect(service.toasts()).toEqual([]);
  });

  it('adds toasts with the correct type', () => {
    service.success('ok');
    service.error('boom');
    service.warning('careful');
    service.info('fyi');

    const types = service.toasts().map((t) => t.type);
    expect(types).toEqual(['success', 'error', 'warning', 'info']);
    expect(service.toasts()[0].message).toBe('ok');
  });

  it('dismiss removes a toast by id', () => {
    service.success('ok');
    const id = service.toasts()[0].id;

    service.dismiss(id);

    expect(service.toasts()).toHaveLength(0);
  });

  it('auto-dismisses after the given duration', () => {
    service.success('ok', 3000);
    expect(service.toasts()).toHaveLength(1);

    vi.advanceTimersByTime(3000);

    expect(service.toasts()).toHaveLength(0);
  });

  it('does not auto-dismiss when duration is 0', () => {
    service.info('persist', 0);

    vi.advanceTimersByTime(10000);

    expect(service.toasts()).toHaveLength(1);
  });
});
