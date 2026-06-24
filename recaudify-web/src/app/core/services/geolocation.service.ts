import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class GeolocationService {
  request(): Observable<GeolocationCoordinates> {
    return new Observable((observer) => {
      if (!navigator.geolocation) {
        observer.error(new Error('GEOLOCATION_UNSUPPORTED'));
        return;
      }

      navigator.geolocation.getCurrentPosition(
        (pos) => {
          observer.next(pos.coords);
          observer.complete();
        },
        () => observer.error(new Error('GEOLOCATION_DENIED')),
        { timeout: 10_000 },
      );
    });
  }

  getPermissionState(): Promise<PermissionState> {
    if (!navigator.permissions) return Promise.resolve('prompt');
    return navigator.permissions.query({ name: 'geolocation' }).then((result) => result.state);
  }
}
