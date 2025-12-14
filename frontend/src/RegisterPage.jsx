import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import StatusMessage from './components/StatusMessage';

export default function RegisterPage({ apiUrl }) {
  const [fullName, setFullName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [loading, setLoading] = useState(false);
  const [status, setStatus] = useState(null);
  const navigate = useNavigate();

  const handleSubmit = async (event) => {
    event.preventDefault();
    setStatus(null);

    if (password !== confirm) {
      setStatus({ tone: 'error', message: 'Las contraseñas no coinciden' });
      return;
    }

    setLoading(true);
    try {
      const response = await fetch(`${apiUrl}/api/register`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ full_name: fullName, email, password }),
      });

      const payload = await response.json();
      if (!response.ok) {
        throw new Error(payload?.error || payload?.message || 'No se pudo registrar');
      }

      setStatus({ tone: 'success', message: 'Administrador creado. Ahora puedes iniciar sesión.' });
      setTimeout(() => navigate('/login'), 900);
    } catch (error) {
      setStatus({ tone: 'error', message: error.message });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="page">
      <div className="hero-panel">
        <span className="hero-panel__badge">
          <i className="bi bi-person-gear" aria-hidden="true" />
          Alta rápida
        </span>
        <h1 className="hero-panel__title">Crear administrador temporal</h1>
        <ul className="hero-panel__list">
          <li>
            <span className="hero-panel__icon">
              <i className="bi bi-shield-check" aria-hidden="true" />
            </span>
            <div>
              <strong>Acceso completo</strong>
              <p>El usuario generado tendrá rol global para configurar el entorno.</p>
            </div>
          </li>
          <li>
            <span className="hero-panel__icon">
              <i className="bi bi-x-circle" aria-hidden="true" />
            </span>
            <div>
              <strong>Puedes eliminarlo luego</strong>
              <p>La ruta de registro es temporal y puede deshabilitarse tras el arranque.</p>
            </div>
          </li>
        </ul>
      </div>

      <div className="form-panel">
        <div className="form-shell">
          <Link className="brand-mark" to="/login" aria-label="Volver al inicio de sesión">
            <i className="bi bi-stars" aria-hidden="true" />
            <span>Spectra</span>
          </Link>

          <div className="form-header">
            <h1>Registrar administrador</h1>
            <p>Usa este formulario solo para el arranque inicial.</p>
          </div>

          <div className="form-card" role="form">
            {status && <StatusMessage tone={status.tone} message={status.message} />}

            <form className="form-grid" onSubmit={handleSubmit}>
              <div className="field">
                <label htmlFor="fullName">Nombre completo</label>
                <input
                  id="fullName"
                  type="text"
                  placeholder="Nombre y apellido"
                  value={fullName}
                  onChange={(e) => setFullName(e.target.value)}
                  required
                />
              </div>

              <div className="field">
                <label htmlFor="email">Correo electrónico</label>
                <input
                  id="email"
                  type="email"
                  autoComplete="email"
                  placeholder="admin@empresa.com"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                />
              </div>

              <div className="field">
                <label htmlFor="password">Contraseña</label>
                <input
                  id="password"
                  type="password"
                  autoComplete="new-password"
                  placeholder="Mínimo 8 caracteres"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                  minLength={8}
                />
              </div>

              <div className="field">
                <label htmlFor="confirm">Confirmar contraseña</label>
                <input
                  id="confirm"
                  type="password"
                  autoComplete="new-password"
                  placeholder="Repite la contraseña"
                  value={confirm}
                  onChange={(e) => setConfirm(e.target.value)}
                  required
                  minLength={8}
                />
              </div>

              <button type="submit" className="button" disabled={loading}>
                {loading ? 'Creando…' : 'Crear administrador'}
              </button>
            </form>

            <div className="supporting-row">
              <span>API: {apiUrl}</span>
              <Link to="/login">Ir al login</Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
