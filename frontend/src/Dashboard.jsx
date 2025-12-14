import React, { useMemo } from 'react';

const brand = '#006d71';

export default function Dashboard({ session, onLogout }) {
  const { user } = session;

  const summaries = useMemo(
    () => [
      { label: 'Estado', value: 'Activo' },
      { label: 'Rol', value: user?.platform_role || 'N/D' },
      { label: 'Correo', value: user?.email },
    ],
    [user]
  );

  return (
    <div className="app-shell app-shell--dashboard">
      <header className="dash-header">
        <div>
          <p className="eyebrow">Panel</p>
          <h1 className="heading">Bienvenido, {user?.full_name || 'Usuario'}</h1>
        </div>
        <button className="secondary" onClick={onLogout}>
          Cerrar sesión
        </button>
      </header>

      <section className="dash-grid">
        <div className="dash-card">
          <p className="muted">Resumen rápido</p>
          <div className="badges">
            {summaries.map((item) => (
              <span key={item.label} className="badge" style={{ color: brand }}>
                <strong>{item.label}:</strong> {item.value}
              </span>
            ))}
          </div>
        </div>

        <div className="dash-card">
          <p className="muted">Próximos pasos</p>
          <ul className="tasks">
            <li>Conecta más módulos al backend.</li>
            <li>Reutiliza el token para consumir endpoints protegidos.</li>
            <li>Personaliza este dashboard según tus necesidades.</li>
          </ul>
        </div>
      </section>
    </div>
  );
}
