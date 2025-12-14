import React, { useEffect, useMemo, useState } from 'react';
import { BrowserRouter, Routes, Route, Navigate, useNavigate } from 'react-router-dom';
import Dashboard from './Dashboard';
import LoginPage from './LoginPage';
import { getStoredSession, persistSession, clearSession } from './session';

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

  const apiUrl = useMemo(() => import.meta.env.VITE_API_URL || 'http://localhost:8000', []);

  return (
    <Routes>
      <Route
        path="/login"
        element={<LoginPage onLogin={handleLogin} apiUrl={apiUrl} existingSession={session} />}
      />
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
