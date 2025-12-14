import React, { useEffect, useMemo, useState } from 'react';
import { BrowserRouter, Routes, Route, Navigate, useNavigate } from 'react-router-dom';
import Dashboard from './Dashboard';
import LoginPage from './LoginPage';
import RegisterPage from './RegisterPage';
import { getStoredSession, persistSession, clearSession } from './session';

function resolveApiUrl() {
  if (window?.location?.hostname?.includes('nextboostperu.com')) {
    // Fuerza HTTPS en el entorno desplegado para evitar redirecciones de CORS.
    return 'https://appspectra.nextboostperu.com';
  }

  if (import.meta.env.VITE_API_URL) return import.meta.env.VITE_API_URL;

  return 'http://localhost:8000';
}

function AppRoutes() {
  const [session, setSession] = useState(getStoredSession);
  const navigate = useNavigate();

  useEffect(() => {
    persistSession(session);
  }, [session]);

  const handleLogin = (payload) => {
    setSession(payload);
    navigate('/dashboard', { replace: true });
  };

  const handleLogout = () => {
    clearSession();
    setSession(null);
    navigate('/login', { replace: true });
  };

  const apiUrl = useMemo(() => resolveApiUrl(), []);

  return (
    <Routes>
      <Route
        path="/login"
        element={<LoginPage onLogin={handleLogin} apiUrl={apiUrl} existingSession={session} />}
      />
      <Route path="/register" element={<RegisterPage apiUrl={apiUrl} />} />
      <Route
        path="/dashboard"
        element={
          session ? (
            <Dashboard session={session} onLogout={handleLogout} apiUrl={apiUrl} />
          ) : (
            <Navigate to="/login" replace />
          )
        }
      />
      <Route path="*" element={<Navigate to={session ? '/dashboard' : '/login'} replace />} />
    </Routes>
  );
}

export default function App() {
  return (
    <BrowserRouter>
      <AppRoutes />
    </BrowserRouter>
  );
}
