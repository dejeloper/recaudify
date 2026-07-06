import { inject, Injectable, signal } from '@angular/core';
import { EMPTY, catchError, tap } from 'rxjs';
import { User, UserPayload } from '@core/interfaces/user.interface';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Injectable({ providedIn: 'root' })
export class UsersService {
  private readonly api = inject(ApiService);
  private readonly toast = inject(ToastService);

  readonly items = signal<User[]>([]);
  readonly disabled = signal<User[]>([]);
  readonly loading = signal(false);
  readonly loadingDisabled = signal(false);
  readonly showDisabled = signal(false);

  load(): void {
    this.loading.set(true);
    this.showDisabled.set(false);
    this.disabled.set([]);
    this.getAll().subscribe({
      next: (list) => {
        this.items.set(list);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  search(term: string): void {
    this.loading.set(true);
    const request = term.trim() ? this.searchByTerm(term.trim()) : this.getAll();
    request.subscribe({
      next: (list) => {
        this.items.set(list);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  toggleDisabled(): void {
    const next = !this.showDisabled();
    this.showDisabled.set(next);
    if (next && this.disabled().length === 0) {
      this.loadingDisabled.set(true);
      this.getDisabled().subscribe({
        next: (list) => {
          this.disabled.set(list);
          this.loadingDisabled.set(false);
        },
        error: () => this.loadingDisabled.set(false),
      });
    }
  }

  remove(user: User) {
    return this.delete(user.id).pipe(
      tap(() => {
        const removed = this.items().find((u) => u.id === user.id)!;
        this.items.update((list) => list.filter((u) => u.id !== user.id));
        this.disabled.update((list) => [removed, ...list]);
        this.toast.success(`Usuario "${user.name}" desactivado.`);
      }),
      catchError(() => {
        this.toast.error('No se pudo desactivar el usuario.');
        return EMPTY;
      }),
    );
  }

  restoreItem(user: User) {
    return this.restore(user.id).pipe(
      tap(() => {
        this.disabled.update((list) => list.filter((u) => u.id !== user.id));
        this.items.update((list) => [...list, user].sort((a, b) => a.name.localeCompare(b.name)));
        this.toast.success(`Usuario "${user.name}" activado.`);
      }),
      catchError(() => {
        this.toast.error('No se pudo activar el usuario.');
        return EMPTY;
      }),
    );
  }

  roleLabel(user: User): string {
    return user.roles[0] ?? '—';
  }

  getAll() {
    return this.api.get<User[]>('users');
  }
  searchByTerm(term: string) {
    return this.api.get<User[]>('users', `search/${encodeURIComponent(term)}`);
  }
  getDisabled() {
    return this.api.get<User[]>('users', 'disabled');
  }
  getById(id: number) {
    return this.api.get<User>('users', String(id));
  }
  create(payload: UserPayload) {
    return this.api.post<User>('users', undefined, payload);
  }
  update(id: number, payload: Partial<UserPayload>) {
    return this.api.put<User>('users', String(id), payload);
  }
  delete(id: number) {
    return this.api.delete('users', String(id));
  }
  restore(id: number) {
    return this.api.post<void>('users', `${id}/restore`);
  }
  resetPassword(id: number) {
    return this.api.post<{ password: string }>('users', `${id}/reset-password`);
  }
}
