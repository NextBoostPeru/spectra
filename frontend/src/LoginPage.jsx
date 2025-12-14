import React, { useEffect, useState } from 'react';

function StatusMessage({ tone, message }) {
  if (!tone) return null;
  return <div className={`status status--${tone}`}>{message}</div>;
}

export default function LoginPage({ onLogin, apiUrl, existingSession }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [status, setStatus] = useState(null);

  useEffect(() => {
    if (existingSession) {
      setStatus({ tone: 'info', message: 'Ya tienes una sesión activa. Serás redirigido al dashboard.' });
    }
  }, [existingSession]);

  const handleSubmit = async (event) => {
    event.preventDefault();
    setStatus(null);
    setLoading(true);

    try {
      const response = await fetch(`${apiUrl}/api/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password }),
      });

      const payload = await response.json();
      if (!response.ok) {
        throw new Error(payload?.message || 'No se pudo iniciar sesión');
      }

      onLogin({ token: payload.token, user: payload.user });
    } catch (error) {
      setStatus({ tone: 'error', message: error.message });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="app-shell">
      <div className="card">
        <div className="card__header">
          <div className="logo">Spectra</div>
          <div className="title-group">
            <p className="eyebrow">Acceso</p>
            <h1 className="heading">Inicia sesión en la plataforma</h1>
            <p className="muted">Conecta con la API en {apiUrl}</p>
          </div>
        </div>

        <form className="form" onSubmit={handleSubmit}>
          <label className="field">
            <span className="field__label">Correo</span>
            <input
              type="email"
              autoComplete="email"
              placeholder="tucorreo@empresa.com"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />
          </label>

          <label className="field">
            <span className="field__label">Contraseña</span>
            <input
              type="password"
              autoComplete="current-password"
              placeholder="••••••••"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </label>

          {status && <StatusMessage tone={status.tone} message={status.message} />}

          <button type="submit" className="button" disabled={loading}>
            {loading ? 'Ingresando…' : 'Ingresar'}
          </button>
        </form>
      </div>
    </div>
  );
}
