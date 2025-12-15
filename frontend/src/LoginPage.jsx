import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import StatusMessage from './components/StatusMessage';

export default function LoginPage({ onLogin, apiUrl, existingSession }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [status, setStatus] = useState(null);

  useEffect(() => {
    if (existingSession) {
      setStatus({ tone: 'info', message: 'Sesión activa detectada. Te llevaremos al panel.' });
    }
  }, [existingSession]);

  const handleSubmit = async (event) => {
    event.preventDefault();
    setStatus(null);
    setLoading(true);

    try {
      const response = await fetch(`${apiUrl}/api/login`, {
        method: 'POST',
        body: new URLSearchParams({ email, password }),
      });

      const payload = await response
        .json()
        .catch(() => ({ message: 'Respuesta inesperada del servidor' }));
      if (!response.ok) {
        throw new Error(payload?.error || payload?.message || 'No se pudo iniciar sesión');
      }

      onLogin({ token: payload.token, user: payload.user });
      setStatus({ tone: 'success', message: 'Acceso concedido, redirigiendo…' });
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
          <i className="bi bi-shield-lock" aria-hidden="true" />
          Seguridad Spectra
        </span>
        <h1 className="hero-panel__title">Control centralizado para tu operación</h1>
        <ul className="hero-panel__list">
          <li>
            <span className="hero-panel__icon">
              <i className="bi bi-person-check" aria-hidden="true" />
            </span>
            <div>
              <strong>Acceso autenticado</strong>
              <p>Protege el panel de gestión con credenciales verificadas.</p>
            </div>
          </li>
          <li>
            <span className="hero-panel__icon">
              <i className="bi bi-speedometer" aria-hidden="true" />
            </span>
            <div>
              <strong>Monitoreo en tiempo real</strong>
              <p>Ingresa para consultar métricas clave y flujo operativo.</p>
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
            <h1>Inicia sesión</h1>
            <p>Autentícate con tu correo corporativo para entrar al panel.</p>
          </div>

          <div className="form-card" role="form">
            {status && <StatusMessage tone={status.tone} message={status.message} />}

            <form className="form-grid" onSubmit={handleSubmit}>
              <div className="field">
                <label htmlFor="email">Correo electrónico</label>
                <input
                  id="email"
                  type="email"
                  autoComplete="email"
                  placeholder="tucorreo@empresa.com"
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
                  autoComplete="current-password"
                  placeholder="••••••••"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                />
              </div>

              <button type="submit" className="button" disabled={loading}>
                {loading ? 'Ingresando…' : 'Entrar'}
              </button>
            </form>

            <div className="supporting-row">
              <span>API: {apiUrl}</span>
              <Link to="/register">Crear admin temporal</Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
