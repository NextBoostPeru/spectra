import React from 'react';
import Tenants from './Tenants';

export default function Dashboard({ session, onLogout, apiUrl }) {
  const { user } = session;

  return (
    <div className="app-shell app-shell--dashboard">
      <header className="dash-header">
        <div>
          <p className="eyebrow">Panel</p>
          <h1 className="heading">Hola, {user?.full_name || 'Usuario'}</h1>
          <p className="muted">Gestiona tenants, contactos y accesos de soporte desde un solo lugar.</p>
        </div>
        <div className="actions">
          <span className="badge">{user?.platform_role || 'Rol N/D'}</span>
          <button className="secondary" onClick={onLogout}>
            Cerrar sesión
          </button>
        </div>
      </header>

      <Tenants apiUrl={apiUrl} />
    </div>
  );
}
