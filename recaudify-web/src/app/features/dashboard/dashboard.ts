import { Component, inject, OnInit } from '@angular/core';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.html',
})
export class Dashboard implements OnInit {
  auth = inject(AuthService);

  ngOnInit() {
    if (!this.auth.currentUser()) {
      this.auth.me().subscribe();
    }
  }

  logout() {
    this.auth.logout().subscribe();
  }
}
